<?php

declare(strict_types=1);

function google_client_id(): string
{
    if (!defined('GOOGLE_CLIENT_ID')) {
        return '';
    }

    return trim((string) GOOGLE_CLIENT_ID);
}

function google_enabled(): bool
{
    return google_client_id() !== '';
}

function google_nonce(): string
{
    $nonce = $_SESSION['google_nonce'] ?? '';
    if (!is_string($nonce) || strlen($nonce) !== 32) {
        $nonce = bin2hex(random_bytes(16));
        $_SESSION['google_nonce'] = $nonce;
    }

    return $nonce;
}

function auth_error_set(string $message): void
{
    $_SESSION['auth_error'] = $message;
}

function auth_error_pull(): string
{
    $message = (string) ($_SESSION['auth_error'] ?? '');
    unset($_SESSION['auth_error']);

    return $message;
}

function google_b64url(string $data): string
{
    $data = strtr($data, '-_', '+/');
    $pad = strlen($data) % 4;
    if ($pad !== 0) {
        $data .= str_repeat('=', 4 - $pad);
    }
    $decoded = base64_decode($data, true);

    return is_string($decoded) ? $decoded : '';
}

function google_asn1_len(string $data): string
{
    $length = strlen($data);
    if ($length < 128) {
        return chr($length);
    }
    $long = ltrim(pack('N', $length), "\x00");

    return chr(0x80 | strlen($long)) . $long;
}

function google_asn1_int(string $data): string
{
    if ($data !== '' && (ord($data[0]) & 0x80) !== 0) {
        $data = "\x00" . $data;
    }

    return "\x02" . google_asn1_len($data) . $data;
}

function google_asn1_seq(string $data): string
{
    return "\x30" . google_asn1_len($data) . $data;
}

function google_jwk_pem(string $modulus, string $exponent): string
{
    $rsa = google_asn1_seq(google_asn1_int($modulus) . google_asn1_int($exponent));
    $algorithm = hex2bin('300d06092a864886f70d0101010500');
    $bit = "\x00" . $rsa;
    $public = google_asn1_seq($algorithm . "\x03" . google_asn1_len($bit) . $bit);

    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($public), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

function google_http_get(string $url): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($body) && $code === 200) {
                return $body;
            }
        }
    }

    $body = @file_get_contents($url);

    return is_string($body) ? $body : '';
}

function google_certs(): array
{
    $cache = dirname(__DIR__) . '/storage/google-certs.json';
    if (is_file($cache) && (time() - (int) filemtime($cache)) < 3600) {
        $cached = json_decode((string) file_get_contents($cache), true);
        if (is_array($cached) && isset($cached['keys']) && is_array($cached['keys'])) {
            return $cached['keys'];
        }
    }

    $raw = google_http_get('https://www.googleapis.com/oauth2/v3/certs');
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || !isset($decoded['keys']) || !is_array($decoded['keys'])) {
        return [];
    }

    @file_put_contents($cache, $raw, LOCK_EX);

    return $decoded['keys'];
}

function google_token_claims(string $token, array $keys, string $clientId, string $nonce): array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3 || $clientId === '') {
        return ['ok' => false];
    }

    $header = json_decode(google_b64url($parts[0]), true);
    $claims = json_decode(google_b64url($parts[1]), true);
    $signature = google_b64url($parts[2]);
    if (!is_array($header) || !is_array($claims) || $signature === '') {
        return ['ok' => false];
    }
    if (($header['alg'] ?? '') !== 'RS256' || !is_string($header['kid'] ?? null)) {
        return ['ok' => false];
    }

    $pem = '';
    foreach ($keys as $key) {
        if (!is_array($key) || ($key['kid'] ?? '') !== $header['kid']) {
            continue;
        }
        if (($key['kty'] ?? '') !== 'RSA' || !is_string($key['n'] ?? null) || !is_string($key['e'] ?? null)) {
            return ['ok' => false];
        }
        $pem = google_jwk_pem(google_b64url($key['n']), google_b64url($key['e']));
        break;
    }
    if ($pem === '') {
        return ['ok' => false, 'error' => 'kid'];
    }

    $valid = openssl_verify($parts[0] . '.' . $parts[1], $signature, $pem, OPENSSL_ALGO_SHA256);
    if ($valid !== 1) {
        return ['ok' => false];
    }

    $issuer = (string) ($claims['iss'] ?? '');
    if ($issuer !== 'https://accounts.google.com' && $issuer !== 'accounts.google.com') {
        return ['ok' => false];
    }

    $audience = $claims['aud'] ?? '';
    $audiences = is_array($audience) ? $audience : [$audience];
    if (!in_array($clientId, $audiences, true)) {
        return ['ok' => false];
    }
    if (!hash_equals($nonce, (string) ($claims['nonce'] ?? ''))) {
        return ['ok' => false];
    }

    $now = time();
    $expires = (int) ($claims['exp'] ?? 0);
    $issued = (int) ($claims['iat'] ?? 0);
    if ($expires < ($now - 120) || $issued > ($now + 120)) {
        return ['ok' => false];
    }

    $verified = $claims['email_verified'] ?? false;
    if ($verified !== true && $verified !== 'true' && $verified !== 1) {
        return ['ok' => false, 'error' => 'email'];
    }

    $email = text_line((string) ($claims['email'] ?? ''), 120);
    $sub = text_line((string) ($claims['sub'] ?? ''), 255);
    $name = text_line((string) ($claims['name'] ?? ''), 80);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/^[A-Za-z0-9_-]{1,255}$/', $sub) !== 1) {
        return ['ok' => false];
    }

    return [
        'ok' => true,
        'sub' => $sub,
        'email' => $email,
        'name' => $name,
    ];
}

function google_verify_id_token(string $token): array
{
    $nonce = (string) ($_SESSION['google_nonce'] ?? '');
    unset($_SESSION['google_nonce']);
    if (strlen($token) < 20 || strlen($token) > 4000 || strlen($nonce) !== 32) {
        return ['ok' => false];
    }

    $keys = google_certs();
    if ($keys === []) {
        return ['ok' => false, 'error' => 'certs'];
    }

    $claims = google_token_claims($token, $keys, google_client_id(), $nonce);
    if (($claims['ok'] ?? false) || ($claims['error'] ?? '') !== 'kid') {
        return $claims;
    }

    google_certs_refresh();

    return google_token_claims($token, google_certs(), google_client_id(), $nonce);
}

function google_certs_refresh(): void
{
    $cache = dirname(__DIR__) . '/storage/google-certs.json';
    if (is_file($cache)) {
        @unlink($cache);
    }
}
