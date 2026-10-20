<?php

declare(strict_types=1);

function lei(?int $bani): string
{
    if ($bani === null) {
        return 'la confirmare';
    }

    $sign = $bani < 0 ? '-' : '';
    $bani = abs($bani);

    return $sign . number_format(intdiv($bani, 100), 0, ',', ' ') . ',' . str_pad((string) ($bani % 100), 2, '0', STR_PAD_LEFT);
}

function invoice_table(array $lines): array
{
    $rows = [];
    $complete = true;
    $total = 0;

    foreach ($lines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $qty = (int) ($line['cantitate'] ?? 0);
        if ($qty < 1) {
            continue;
        }
        $pret = $line['pret'] ?? null;
        $pretBani = (is_numeric($pret) && (float) $pret > 0) ? (int) round(((float) $pret) * 100) : null;
        $sum = $pretBani === null ? null : $pretBani * $qty;
        if ($sum === null) {
            $complete = false;
        } else {
            $total += $sum;
        }
        $rows[] = [
            'titlu' => (string) ($line['titlu'] ?? ''),
            'cantitate' => $qty,
            'pret' => $pretBani,
            'suma' => $sum,
        ];
    }

    return [
        'rows' => $rows,
        'total' => $complete && $rows !== [] ? $total : null,
        'tva' => $complete && $rows !== [] ? (int) round($total * 20 / 120) : null,
    ];
}

function invoice_message(array $order, string $date, array $payer, array $table): string
{
    $lines = '';
    foreach ($table['rows'] as $index => $row) {
        $lines .= ($index + 1) . '. ' . $row['titlu']
            . '  × ' . $row['cantitate']
            . '  preț ' . lei($row['pret'])
            . '  suma ' . lei($row['suma']) . "\n";
    }

    $body = "Cont de plată nr. " . ($order['id'] ?? '') . " din " . $date . "\n\n"
        . "Furnizor: " . COMPANY_LEGAL . "\n"
        . "IDNO: " . COMPANY_IDNO . "\n"
        . "TVA: " . COMPANY_TVA . "\n"
        . "Bancă: " . COMPANY_BANK . "\n"
        . "IBAN: " . COMPANY_IBAN . "\n\n"
        . "Plătitor: " . ($payer['nume'] ?? '') . "\n";
    if (($payer['idno'] ?? '') !== '') {
        $body .= "IDNO: " . $payer['idno'] . "\n";
    }
    if (($payer['tva'] ?? '') !== '') {
        $body .= "TVA: " . $payer['tva'] . "\n";
    }
    if (($payer['adresa'] ?? '') !== '') {
        $body .= "Adresă: " . $payer['adresa'] . "\n";
    }
    $body .= "\n" . $lines . "\n"
        . "Total de plată: " . ($table['total'] === null ? 'la confirmare' : lei($table['total']) . ' lei') . "\n";
    if ($table['tva'] !== null) {
        $body .= "Inclusiv TVA: " . lei($table['tva']) . " lei\n";
    }
    if (($order['adresa'] ?? '') !== '') {
        $body .= "\nAdresă de livrare: " . $order['adresa'] . "\n";
    }
    if (($order['email'] ?? '') !== '') {
        $body .= "Email client: " . $order['email'] . "\n";
    }

    return $body;
}

function send_invoice_mail(array $order, string $date, array $payer, array $table, string $clientEmail, array $user = []): bool
{
    $clientEmail = str_replace(["\r", "\n"], '', $clientEmail);
    if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        site_mail_status('În contul personal nu este indicat un email.');

        return false;
    }
    if (($order['email'] ?? '') === '') {
        $order['email'] = $clientEmail;
    }

    require_once __DIR__ . '/pdf.php';
    $pdf = invoice_pdf($order, $date, $payer, $table);
    $filename = 'cont-de-plata-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($order['id'] ?? 'casa-bradu')) . '.pdf';

    $sent = site_mail(
        $clientEmail,
        'Cont de plată ' . ($order['id'] ?? ''),
        invoice_message($order, $date, $payer, $table)
            . "\n\nGăsești contul de plată și în atașamentul PDF.",
        SITE_ORDERS_EMAIL,
        '',
        SITE_ORDERS_EMAIL,
        [
            [
                'name' => $filename,
                'type' => 'application/pdf',
                'data' => $pdf,
            ],
        ]
    );

    if ($sent && function_exists('sheets_append_order')) {
        if ($user === []) {
            $user = [
                'nume' => (string) ($order['nume'] ?? $payer['nume'] ?? ''),
                'telefon' => (string) ($order['telefon'] ?? ''),
                'email' => $clientEmail,
                'rechizite' => (string) ($order['rechizite'] ?? ''),
            ];
        } elseif (($user['email'] ?? '') === '') {
            $user['email'] = $clientEmail;
        }
        sheets_append_order($order, $user);
    }

    return $sent;
}
