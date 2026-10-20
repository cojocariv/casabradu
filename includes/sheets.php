<?php

declare(strict_types=1);

function sheets_webhook_url(): string
{
    if (!defined('GOOGLE_SHEETS_WEBHOOK_URL')) {
        return '';
    }

    return trim((string) GOOGLE_SHEETS_WEBHOOK_URL);
}

function sheets_webhook_secret(): string
{
    if (!defined('GOOGLE_SHEETS_WEBHOOK_SECRET')) {
        return '';
    }

    return trim((string) GOOGLE_SHEETS_WEBHOOK_SECRET);
}

function sheets_enabled(): bool
{
    return sheets_webhook_url() !== '';
}

function sheets_log(string $message): void
{
    $dir = dirname(__DIR__) . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    @file_put_contents($dir . '/sheets.log', $line, FILE_APPEND | LOCK_EX);
}

/**
 * Append a site order to Google Sheet tab "de pe site".
 * Failures are logged but do not block checkout.
 */
function sheets_append_order(array $order, array $user): bool
{
    $url = sheets_webhook_url();
    if ($url === '') {
        sheets_log('skip: GOOGLE_SHEETS_WEBHOOK_URL is empty');

        return false;
    }

    require_once __DIR__ . '/factura.php';
    $table = invoice_table($order['linii'] ?? []);

    $products = [];
    $qtyParts = [];
    $boxes = 0;
    foreach ($order['linii'] ?? [] as $line) {
        if (!is_array($line)) {
            continue;
        }
        $title = (string) ($line['titlu'] ?? '');
        $qty = (int) ($line['cantitate'] ?? 0);
        if ($qty < 1) {
            continue;
        }
        $products[] = $title . ' × ' . $qty;
        $qtyParts[] = (string) $qty;
        if (preg_match('/cuti[ie]/ui', $title) === 1) {
            $boxes += $qty;
        }
    }

    $total = $table['total'] === null
        ? 'la confirmare'
        : rtrim(rtrim(number_format(((int) $table['total']) / 100, 2, '.', ''), '0'), '.');
    $tva = $table['tva'] === null
        ? ''
        : rtrim(rtrim(number_format(((int) $table['tva']) / 100, 2, '.', ''), '0'), '.');
    $stamp = strtotime((string) ($order['la'] ?? '')) ?: time();

    $payload = [
        'secret' => sheets_webhook_secret(),
        'sheet' => 'de pe site',
        'row' => [
            date('d.m.Y H:i', $stamp),
            (string) ($order['id'] ?? ''),
            sheets_company_from_user($user, $order),
            (string) ($user['nume'] ?? ''),
            (string) ($user['telefon'] ?? ''),
            (string) ($user['email'] ?? ''),
            implode('; ', $products),
            $boxes > 0 ? (string) $boxes : '',
            implode('+', $qtyParts),
            $total,
            $tva,
            (string) ($order['adresa'] ?? ''),
            (string) ($order['nota'] ?? ''),
            (string) ($order['status'] ?? 'inregistrata'),
            'site',
        ],
    ];

    $ok = sheets_post_json($url, $payload);
    sheets_log(($ok ? 'ok' : 'fail') . ' order=' . ($order['id'] ?? '') . ' url=' . sheets_url_hint($url));

    return $ok;
}

function sheets_company_from_user(array $user, array $order): string
{
    $requisites = trim((string) ($order['rechizite'] ?? $user['rechizite'] ?? ''));
    if ($requisites === '') {
        return (string) ($user['nume'] ?? '');
    }

    $first = preg_split('/\R/u', $requisites) ?: [];
    $line = trim((string) ($first[0] ?? ''));

    return $line !== '' ? $line : (string) ($user['nume'] ?? '');
}

function sheets_url_hint(string $url): string
{
    if (preg_match('#/macros/s/([A-Za-z0-9_-]{8,})/#', $url, $m) === 1) {
        return '…/' . substr($m[1], 0, 12) . '…';
    }

    return 'set';
}

function sheets_post_json(string $url, array $payload): bool
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($body)) {
        sheets_log('fail: json_encode');

        return false;
    }

    if (!function_exists('curl_init')) {
        sheets_log('fail: curl missing');

        return false;
    }

    $ch = curl_init($url);
    if ($ch === false) {
        sheets_log('fail: curl_init');

        return false;
    }

    $opts = [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        // Apps Script returns 302; keep POST body on redirect (critical).
        CURLOPT_POSTREDIR => defined('CURL_REDIR_POST_ALL') ? CURL_REDIR_POST_ALL : 7,
        CURLOPT_SSL_VERIFYPEER => true,
    ];
    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if (!is_string($response)) {
        sheets_log('fail: curl error=' . $err . ' http=' . $code);

        return false;
    }

    $trimmed = trim($response);
    $decoded = json_decode($trimmed, true);
    if (is_array($decoded) && ($decoded['ok'] ?? false) === true) {
        return true;
    }

    // Some deployments return plain text after redirect; treat 2xx + empty/"ok" as success.
    if ($code >= 200 && $code < 300 && ($trimmed === '' || stripos($trimmed, 'ok') !== false)) {
        return true;
    }

    sheets_log(
        'fail: http=' . $code
        . ' body=' . substr(preg_replace('/\s+/', ' ', $trimmed) ?? '', 0, 180)
        . ' err=' . $err
        . ' final=' . substr($finalUrl, 0, 80)
    );

    return false;
}

/** Manual test helper used by tools/test-sheets.php */
function sheets_send_test_row(): bool
{
    return sheets_append_order(
        [
            'id' => 'TEST-' . date('ymd-His'),
            'la' => date('c'),
            'status' => 'test',
            'adresa' => 'Test adresă',
            'nota' => 'Test webhook Google Sheets',
            'rechizite' => 'Test Companie SRL',
            'linii' => [
                ['titlu' => 'Cialde, Cutii', 'cantitate' => 1, 'pret' => null],
            ],
        ],
        [
            'nume' => 'Test Client',
            'telefon' => '+37360000000',
            'email' => 'test@casabradu.md',
            'rechizite' => 'Test Companie SRL',
        ]
    );
}
