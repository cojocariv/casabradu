<?php

declare(strict_types=1);

function pdf_latin(string $text): string
{
    $map = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ş' => 'S', 'Ț' => 'T', 'Ţ' => 'T',
        '«' => '"', '»' => '"', '–' => '-', '—' => '-', '…' => '...', '€' => 'EUR',
        '№' => 'Nr.',
    ];
    $text = strtr($text, $map);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if (is_string($converted) && $converted !== '') {
            return $converted;
        }
    }

    return preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '?', $text) ?? $text;
}

function pdf_escape(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], pdf_latin($text));
}

function invoice_pdf(array $order, string $date, array $payer, array $table): string
{
    $lines = [];
    $lines[] = 'BT';
    $lines[] = '/F1 16 Tf';
    $lines[] = '50 800 Td';
    $lines[] = '(' . pdf_escape(SITE_NAME) . ') Tj';
    $lines[] = '0 -28 Td';
    $lines[] = '/F2 14 Tf';
    $lines[] = '(' . pdf_escape('Cont de plată nr. ' . ($order['id'] ?? '') . ' din ' . $date) . ') Tj';
    $lines[] = '0 -22 Td';
    $lines[] = '/F1 10 Tf';
    $lines[] = '(' . pdf_escape('Furnizor: ' . COMPANY_LEGAL) . ') Tj';
    $lines[] = '0 -14 Td';
    $lines[] = '(' . pdf_escape('IDNO: ' . COMPANY_IDNO . '    TVA: ' . COMPANY_TVA) . ') Tj';
    $lines[] = '0 -14 Td';
    $lines[] = '(' . pdf_escape('Banca: ' . COMPANY_BANK) . ') Tj';
    $lines[] = '0 -14 Td';
    $lines[] = '(' . pdf_escape('IBAN: ' . COMPANY_IBAN) . ') Tj';
    $lines[] = '0 -20 Td';
    $lines[] = '(' . pdf_escape('Platitor: ' . ($payer['nume'] ?? '')) . ') Tj';
    if (($payer['idno'] ?? '') !== '') {
        $lines[] = '0 -14 Td';
        $lines[] = '(' . pdf_escape('IDNO: ' . $payer['idno']) . ') Tj';
    }
    if (($payer['tva'] ?? '') !== '') {
        $lines[] = '0 -14 Td';
        $lines[] = '(' . pdf_escape('TVA: ' . $payer['tva']) . ') Tj';
    }
    if (($payer['adresa'] ?? '') !== '') {
        $lines[] = '0 -14 Td';
        $lines[] = '(' . pdf_escape('Adresa: ' . $payer['adresa']) . ') Tj';
    }
    $lines[] = '0 -22 Td';
    $lines[] = '/F2 10 Tf';
    $lines[] = '(' . pdf_escape('ATENTIE') . ') Tj';
    $lines[] = '/F1 9 Tf';
    $lines[] = '0 -13 Td';
    $lines[] = '(' . pdf_escape('Inainte de a achita, contacteaza-ne si verifica disponibilitatea produselor.') . ') Tj';
    $lines[] = '0 -12 Td';
    $lines[] = '(' . pdf_escape('Contul este valabil 3 zile bancare. Livrarea se face dupa incasare.') . ') Tj';
    $lines[] = '0 -20 Td';
    $lines[] = '/F2 10 Tf';
    $lines[] = '(' . pdf_escape(sprintf('%-4s %-42s %6s %12s %12s', 'Nr.', 'Denumirea marfii', 'Cant.', 'Pret, lei', 'Suma, lei')) . ') Tj';
    $lines[] = '/F1 10 Tf';

    foreach ($table['rows'] as $index => $row) {
        $title = pdf_latin((string) ($row['titlu'] ?? ''));
        if (strlen($title) > 42) {
            $title = substr($title, 0, 39) . '...';
        }
        $pret = lei($row['pret'] ?? null);
        $suma = lei($row['suma'] ?? null);
        $lines[] = '0 -14 Td';
        $lines[] = '(' . pdf_escape(sprintf(
            '%-4s %-42s %6s %12s %12s',
            (string) ($index + 1),
            $title,
            (string) (int) ($row['cantitate'] ?? 0),
            $pret,
            $suma
        )) . ') Tj';
    }

    $lines[] = '0 -22 Td';
    $lines[] = '/F2 11 Tf';
    $total = ($table['total'] ?? null) === null ? 'la confirmare' : lei($table['total']) . ' lei';
    $lines[] = '(' . pdf_escape('Total de plata: ' . $total) . ') Tj';
    if (($table['tva'] ?? null) !== null) {
        $lines[] = '0 -14 Td';
        $lines[] = '/F1 10 Tf';
        $lines[] = '(' . pdf_escape('Inclusiv TVA: ' . lei($table['tva']) . ' lei') . ') Tj';
    }
    if (($order['email'] ?? '') !== '') {
        $lines[] = '0 -18 Td';
        $lines[] = '(' . pdf_escape('Email client: ' . $order['email']) . ') Tj';
    }
    $lines[] = '0 -28 Td';
    $lines[] = '(' . pdf_escape('Conducator: ____________________ / ____________________ /') . ') Tj';
    $lines[] = 'ET';

    $content = implode("\n", $lines);
    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
    $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>';
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref . "\n%%EOF";

    return $pdf;
}
