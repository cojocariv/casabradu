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

/**
 * Append a site order to Google Sheet tab "de pe site".
 * Failures are silent so checkout is never blocked.
 */
function sheets_append_order(array $order, array $user): bool
{
    $url = sheets_webhook_url();
    if ($url === '') {
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

    return sheets_post_json($url, $payload);
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

function sheets_post_json(string $url, array $payload): bool
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($body)) {
        return false;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return is_string($response) && $code >= 200 && $code < 300;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);

    return is_string($response);
}
