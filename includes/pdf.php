<?php

declare(strict_types=1);

function pdf_latin(string $text): string
{
    $map = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ş' => 'S', 'Ț' => 'T', 'Ţ' => 'T',
        '«' => '"', '»' => '"', '–' => '-', '—' => '-', '…' => '...', '€' => 'EUR',
        '№' => 'Nr.', "\xc2\xa0" => ' ',
    ];
    $text = strtr($text, $map);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'Windows-1252//IGNORE', $text);
        if (is_string($converted)) {
            return $converted;
        }
    }

    return preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $text) ?? $text;
}

function pdf_escape(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], pdf_latin($text));
}

function pdf_ops(array $ops): string
{
    return implode("\n", $ops) . "\n";
}

function pdf_text(float $x, float $y, string $text, float $size, string $font = 'F1'): string
{
    return sprintf(
        'BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET',
        $font,
        $size,
        $x,
        $y,
        pdf_escape($text)
    );
}

function pdf_text_width(string $text, float $size): float
{
    return strlen(pdf_latin($text)) * $size * 0.5;
}

function pdf_text_centered(float $centerX, float $y, string $text, float $size, string $font = 'F1'): string
{
    return pdf_text($centerX - (pdf_text_width($text, $size) / 2), $y, $text, $size, $font);
}

function pdf_text_right(float $rightX, float $y, string $text, float $size, string $font = 'F1'): string
{
    return pdf_text($rightX - pdf_text_width($text, $size), $y, $text, $size, $font);
}

function pdf_line(float $x1, float $y1, float $x2, float $y2, float $width = 0.6): string
{
    return sprintf('q %.2F w %.2F %.2F m %.2F %.2F l S Q', $width, $x1, $y1, $x2, $y2);
}

function pdf_rect(float $x, float $y, float $w, float $h, bool $fill = false, float $width = 0.6): string
{
    $paint = $fill ? 'B' : 'S';

    return sprintf('q %.2F w %.2F %.2F %.2F %.2F re %s Q', $width, $x, $y, $w, $h, $paint);
}

function pdf_fill_rect(float $x, float $y, float $w, float $h, float $r, float $g, float $b): string
{
    return sprintf(
        'q %.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f Q',
        $r,
        $g,
        $b,
        $x,
        $y,
        $w,
        $h
    );
}

function pdf_clip(string $text, int $max): string
{
    $text = pdf_latin($text);
    if (strlen($text) <= $max) {
        return $text;
    }

    return substr($text, 0, max(1, $max - 3)) . '...';
}

function pdf_logo_image(): ?array
{
    $path = dirname(__DIR__) . '/assets/img/logo.jpg';
    if (!is_file($path)) {
        return null;
    }
    $data = file_get_contents($path);
    if ($data === false || $data === '') {
        return null;
    }
    $info = @getimagesizefromstring($data);
    if (!is_array($info) || (int) ($info[2] ?? 0) !== IMAGETYPE_JPEG) {
        return null;
    }

    return [
        'data' => $data,
        'width' => (int) $info[0],
        'height' => (int) $info[1],
    ];
}

function invoice_pdf(array $order, string $date, array $payer, array $table): string
{
    $margin = 40.0;
    $pageW = 595.0;
    $pageH = 842.0;
    $contentW = $pageW - (2 * $margin);
    $y = $pageH - 36.0;
    $ops = [];
    $logo = pdf_logo_image();
    $hasLogo = $logo !== null;

    if ($hasLogo) {
        $logoH = 62.0;
        $logoW = $logoH * ($logo['width'] / max(1, $logo['height']));
        if ($logoW > 160) {
            $logoW = 160.0;
            $logoH = $logoW * ($logo['height'] / max(1, $logo['width']));
        }
        $logoX = ($pageW - $logoW) / 2;
        $ops[] = sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /Im1 Do Q', $logoW, $logoH, $logoX, $y - $logoH);
        $y -= $logoH + 10;
    }

    $ops[] = pdf_text_centered($pageW / 2, $y, SITE_NAME, 15, 'F2');
    $y -= 12;
    $ops[] = pdf_text_centered($pageW / 2, $y, 'CAFFE ARTIGIANALE', 8, 'F1');
    $y -= 14;
    $ops[] = pdf_line($margin, $y, $pageW - $margin, $y, 1);
    $y -= 20;

    $ops[] = pdf_text_centered(
        $pageW / 2,
        $y,
        'Cont de plata nr. ' . ($order['id'] ?? '') . ' din ' . $date,
        12,
        'F2'
    );
    $y -= 16;
    $ops[] = pdf_line($margin, $y, $pageW - $margin, $y, 0.7);
    $y -= 20;

    $colMid = $margin + ($contentW / 2) + 8;
    $leftY = $y;
    $ops[] = pdf_text($margin, $leftY, 'Furnizor', 9, 'F2');
    $leftY -= 13;
    $ops[] = pdf_text($margin, $leftY, COMPANY_LEGAL, 10, 'F1');
    $leftY -= 12;
    $ops[] = pdf_text($margin, $leftY, 'IDNO: ' . COMPANY_IDNO, 10, 'F1');
    $leftY -= 12;
    $ops[] = pdf_text($margin, $leftY, 'TVA: ' . COMPANY_TVA, 10, 'F1');
    $leftY -= 12;
    $ops[] = pdf_text($margin, $leftY, 'Banca: ' . COMPANY_BANK, 10, 'F1');
    $leftY -= 12;
    $ops[] = pdf_text($margin, $leftY, 'IBAN: ' . COMPANY_IBAN, 10, 'F1');

    $rightY = $y;
    $ops[] = pdf_text($colMid, $rightY, 'Platitor', 9, 'F2');
    $rightY -= 13;
    $ops[] = pdf_text($colMid, $rightY, pdf_clip((string) ($payer['nume'] ?? ''), 48), 10, 'F1');
    $rightY -= 12;
    if (($payer['idno'] ?? '') !== '') {
        $ops[] = pdf_text($colMid, $rightY, 'IDNO: ' . $payer['idno'], 10, 'F1');
        $rightY -= 12;
    }
    if (($payer['tva'] ?? '') !== '') {
        $ops[] = pdf_text($colMid, $rightY, 'TVA: ' . $payer['tva'], 10, 'F1');
        $rightY -= 12;
    }
    if (($payer['adresa'] ?? '') !== '') {
        $ops[] = pdf_text($colMid, $rightY, 'Adresa: ' . pdf_clip((string) $payer['adresa'], 42), 10, 'F1');
        $rightY -= 12;
    }

    $y = min($leftY, $rightY) - 16;
    $ops[] = pdf_line($margin, $y, $pageW - $margin, $y, 0.7);
    $y -= 18;

    $ops[] = pdf_text($margin, $y, 'ATENTIE', 10, 'F2');
    $y -= 13;
    $ops[] = pdf_text($margin, $y, 'Inainte de a achita, contacteaza-ne si verifica disponibilitatea produselor.', 9, 'F1');
    $y -= 12;
    $ops[] = pdf_text($margin, $y, 'Contul este valabil 3 zile bancare. Livrarea se face numai dupa incasare.', 9, 'F1');
    $y -= 20;

    $cols = [
        'nr' => 28.0,
        'titlu' => 250.0,
        'cant' => 50.0,
        'pret' => 93.5,
        'suma' => 93.5,
    ];
    $rowH = 20.0;
    $tableX = $margin;
    $tableW = array_sum($cols);

    $headerY = $y - $rowH;
    $ops[] = pdf_fill_rect($tableX, $headerY, $tableW, $rowH, 0.95, 0.94, 0.91);
    $ops[] = pdf_rect($tableX, $headerY, $tableW, $rowH, false, 0.8);

    $x = $tableX;
    $headers = [
        ['nr', 'Nr.'],
        ['titlu', 'Denumirea marfii'],
        ['cant', 'Cant.'],
        ['pret', 'Pret, lei'],
        ['suma', 'Suma, lei'],
    ];
    foreach ($headers as $header) {
        [$key, $label] = $header;
        $w = $cols[$key];
        $ops[] = pdf_line($x, $headerY, $x, $headerY + $rowH, 0.6);
        if ($key === 'cant' || $key === 'pret' || $key === 'suma') {
            $ops[] = pdf_text_right($x + $w - 4, $headerY + 6, $label, 9, 'F2');
        } elseif ($key === 'nr') {
            $ops[] = pdf_text_centered($x + ($w / 2), $headerY + 6, $label, 9, 'F2');
        } else {
            $ops[] = pdf_text($x + 4, $headerY + 6, $label, 9, 'F2');
        }
        $x += $w;
    }
    $ops[] = pdf_line($tableX + $tableW, $headerY, $tableX + $tableW, $headerY + $rowH, 0.6);

    $y = $headerY;
    $rows = $table['rows'] ?? [];
    if ($rows === []) {
        $y -= $rowH;
        $ops[] = pdf_rect($tableX, $y, $tableW, $rowH, false, 0.6);
        $ops[] = pdf_text($tableX + 4, $y + 6, 'Nu sunt produse pe acest cont.', 9, 'F1');
    }

    foreach ($rows as $index => $row) {
        $y -= $rowH;
        if ($y < 90) {
            break;
        }
        $ops[] = pdf_rect($tableX, $y, $tableW, $rowH, false, 0.6);
        $x = $tableX;
        $cells = [
            'nr' => (string) ($index + 1),
            'titlu' => pdf_clip((string) ($row['titlu'] ?? ''), 42),
            'cant' => (string) (int) ($row['cantitate'] ?? 0),
            'pret' => lei($row['pret'] ?? null),
            'suma' => lei($row['suma'] ?? null),
        ];
        foreach ($cols as $key => $w) {
            $ops[] = pdf_line($x, $y, $x, $y + $rowH, 0.5);
            $pad = 4.0;
            if ($key === 'cant' || $key === 'pret' || $key === 'suma') {
                $ops[] = pdf_text_right($x + $w - $pad, $y + 6, $cells[$key], 9, 'F1');
            } elseif ($key === 'nr') {
                $ops[] = pdf_text_centered($x + ($w / 2), $y + 6, $cells[$key], 9, 'F1');
            } else {
                $ops[] = pdf_text($x + $pad, $y + 6, $cells[$key], 9, 'F1');
            }
            $x += $w;
        }
        $ops[] = pdf_line($tableX + $tableW, $y, $tableX + $tableW, $y + $rowH, 0.5);
    }

    $y -= 22;
    $total = ($table['total'] ?? null) === null ? 'la confirmare' : lei($table['total']) . ' lei';
    $ops[] = pdf_text($margin, $y, 'Total de plata: ' . $total, 12, 'F2');
    if (($table['tva'] ?? null) !== null) {
        $y -= 14;
        $ops[] = pdf_text($margin, $y, 'Inclusiv TVA: ' . lei($table['tva']) . ' lei', 10, 'F1');
    }
    if (($order['email'] ?? '') !== '') {
        $y -= 14;
        $ops[] = pdf_text($margin, $y, 'Email client: ' . $order['email'], 10, 'F1');
    }

    $y -= 36;
    $ops[] = pdf_text($margin, $y, 'Administrator: ________________________ / Cojocari Tatiana /', 10, 'F1');

    $content = pdf_ops($ops);
    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';

    if ($hasLogo) {
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> /XObject << /Im1 7 0 R >> >> >>';
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /XObject /Subtype /Image /Width ' . $logo['width']
            . ' /Height ' . $logo['height']
            . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '
            . strlen($logo['data']) . " >>\nstream\n" . $logo['data'] . "\nendstream";
    } else {
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>';
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    }

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $count = count($objects) + 1;
    $pdf .= 'xref' . "\n0 " . $count . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . $count . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref . "\n%%EOF";

    return $pdf;
}
