<?php

declare(strict_types=1);

function pdf_latin(string $text): string
{
    $map = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ş' => 'S', 'Ț' => 'T', 'Ţ' => 'T',
        'à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 'í' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ù' => 'u', 'ú' => 'u',
        'À' => 'A', 'Á' => 'A', 'È' => 'E', 'É' => 'E', 'Ì' => 'I', 'Í' => 'I',
        'Ò' => 'O', 'Ó' => 'O', 'Ù' => 'U', 'Ú' => 'U',
        '«' => '"', '»' => '"', '–' => '-', '—' => '-', '…' => '...', '€' => 'EUR',
        '№' => 'Nr.', '·' => '-', "\xc2\xa0" => ' ',
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

function pdf_wrap(string $text, int $maxChars, int $maxLines = 3): array
{
    $text = trim(preg_replace('/\s+/u', ' ', pdf_latin($text)) ?? '');
    if ($text === '') {
        return [];
    }
    $words = explode(' ', $text);
    $lines = [];
    $current = '';
    foreach ($words as $word) {
        if ($word === '') {
            continue;
        }
        $candidate = $current === '' ? $word : $current . ' ' . $word;
        if (strlen($candidate) <= $maxChars) {
            $current = $candidate;
            continue;
        }
        if ($current !== '') {
            $lines[] = $current;
            if (count($lines) >= $maxLines) {
                return $lines;
            }
        }
        if (strlen($word) > $maxChars) {
            $chunk = substr($word, 0, $maxChars);
            $lines[] = $chunk;
            $current = '';
            if (count($lines) >= $maxLines) {
                return $lines;
            }
        } else {
            $current = $word;
        }
    }
    if ($current !== '' && count($lines) < $maxLines) {
        $lines[] = $current;
    }

    return $lines;
}

function pdf_logo_image(): ?array
{
    return pdf_image_from_path('assets/img/logo.jpg');
}

function pdf_image_from_path(string $relative): ?array
{
    $relative = str_replace('\\', '/', $relative);
    $relative = preg_replace('/[?#].*$/', '', $relative) ?? $relative;
    $relative = ltrim($relative, '/');
    if ($relative === '' || strpos($relative, '..') !== false) {
        return null;
    }
    $path = dirname(__DIR__) . '/' . $relative;
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    $info = @getimagesizefromstring($raw);
    if (!is_array($info)) {
        return null;
    }
    $type = (int) ($info[2] ?? 0);
    $width = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    if ($width < 1 || $height < 1) {
        return null;
    }

    if ($type === IMAGETYPE_JPEG) {
        return [
            'filter' => 'DCTDecode',
            'data' => $raw,
            'width' => $width,
            'height' => $height,
        ];
    }

    if ($type === IMAGETYPE_PNG) {
        if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
            $src = @imagecreatefromstring($raw);
            if ($src !== false) {
                if (function_exists('imagepalettetotruecolor')) {
                    @imagepalettetotruecolor($src);
                }
                if (function_exists('imagealphablending')) {
                    imagealphablending($src, true);
                }
                $canvas = imagecreatetruecolor($width, $height);
                if ($canvas !== false) {
                    $white = imagecolorallocate($canvas, 255, 255, 255);
                    imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
                    imagecopy($canvas, $src, 0, 0, 0, 0, $width, $height);
                    imagedestroy($src);
                    ob_start();
                    imagejpeg($canvas, null, 82);
                    $jpeg = ob_get_clean();
                    imagedestroy($canvas);
                    if (is_string($jpeg) && $jpeg !== '') {
                        return [
                            'filter' => 'DCTDecode',
                            'data' => $jpeg,
                            'width' => $width,
                            'height' => $height,
                        ];
                    }
                } else {
                    imagedestroy($src);
                }
            }
        }

        $rgb = pdf_png_rgb($raw);
        if ($rgb !== null) {
            $rgb = pdf_rgb_scale($rgb, 160);
            $compressed = gzcompress($rgb['data'], 6);
            if ($compressed === false) {
                return null;
            }

            return [
                'filter' => 'FlateDecode',
                'data' => $compressed,
                'width' => $rgb['width'],
                'height' => $rgb['height'],
            ];
        }
    }

    return null;
}

function pdf_rgb_scale(array $rgb, int $maxSide): array
{
    $width = (int) $rgb['width'];
    $height = (int) $rgb['height'];
    $data = (string) $rgb['data'];
    if ($width < 1 || $height < 1 || $maxSide < 16) {
        return $rgb;
    }
    $scale = min(1.0, $maxSide / max($width, $height));
    if ($scale >= 0.999) {
        return $rgb;
    }
    $newW = max(1, (int) round($width * $scale));
    $newH = max(1, (int) round($height * $scale));
    $out = '';
    for ($y = 0; $y < $newH; $y++) {
        $srcY = min($height - 1, (int) floor($y * $height / $newH));
        for ($x = 0; $x < $newW; $x++) {
            $srcX = min($width - 1, (int) floor($x * $width / $newW));
            $o = ($srcY * $width + $srcX) * 3;
            $out .= $data[$o] . $data[$o + 1] . $data[$o + 2];
        }
    }

    return [
        'width' => $newW,
        'height' => $newH,
        'data' => $out,
    ];
}

function pdf_png_rgb(string $png): ?array
{
    if (strlen($png) < 24 || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
        return null;
    }
    $pos = 8;
    $width = 0;
    $height = 0;
    $bitDepth = 0;
    $colorType = -1;
    $idat = '';
    $lenAll = strlen($png);
    while ($pos + 8 <= $lenAll) {
        $len = unpack('N', substr($png, $pos, 4));
        if ($len === false) {
            return null;
        }
        $chunkLen = (int) $len[1];
        $type = substr($png, $pos + 4, 4);
        $data = substr($png, $pos + 8, $chunkLen);
        if (strlen($data) !== $chunkLen) {
            return null;
        }
        if ($type === 'IHDR') {
            $hdr = unpack('Nwidth/Nheight/Cbit/Ccolor/Ccomp/Cfilter/Cinterlace', $data);
            if ($hdr === false) {
                return null;
            }
            $width = (int) $hdr['width'];
            $height = (int) $hdr['height'];
            $bitDepth = (int) $hdr['bit'];
            $colorType = (int) $hdr['color'];
            if ((int) $hdr['interlace'] !== 0 || $bitDepth !== 8 || ($colorType !== 2 && $colorType !== 6)) {
                return null;
            }
        } elseif ($type === 'IDAT') {
            $idat .= $data;
        } elseif ($type === 'IEND') {
            break;
        }
        $pos += 12 + $chunkLen;
    }
    if ($width < 1 || $height < 1 || $idat === '') {
        return null;
    }
    $decoded = @gzuncompress($idat);
    if ($decoded === false) {
        $decoded = @gzinflate($idat);
    }
    if (!is_string($decoded) || $decoded === '') {
        return null;
    }

    $bpp = $colorType === 6 ? 4 : 3;
    $stride = $width * $bpp;
    $expect = ($stride + 1) * $height;
    if (strlen($decoded) < $expect) {
        return null;
    }

    $rgb = '';
    $prev = str_repeat("\0", $stride);
    $offset = 0;
    for ($y = 0; $y < $height; $y++) {
        $filter = ord($decoded[$offset]);
        $offset++;
        $row = substr($decoded, $offset, $stride);
        $offset += $stride;
        $out = '';
        for ($i = 0; $i < $stride; $i++) {
            $x = ord($row[$i]);
            $a = $i >= $bpp ? ord($out[$i - $bpp]) : 0;
            $b = ord($prev[$i]);
            $c = ($i >= $bpp) ? ord($prev[$i - $bpp]) : 0;
            if ($filter === 1) {
                $x = ($x + $a) & 255;
            } elseif ($filter === 2) {
                $x = ($x + $b) & 255;
            } elseif ($filter === 3) {
                $x = ($x + intdiv($a + $b, 2)) & 255;
            } elseif ($filter === 4) {
                $p = $a + $b - $c;
                $pa = abs($p - $a);
                $pb = abs($p - $b);
                $pc = abs($p - $c);
                if ($pa <= $pb && $pa <= $pc) {
                    $pr = $a;
                } elseif ($pb <= $pc) {
                    $pr = $b;
                } else {
                    $pr = $c;
                }
                $x = ($x + $pr) & 255;
            } elseif ($filter !== 0) {
                return null;
            }
            $out .= chr($x);
        }
        $prev = $out;
        if ($bpp === 3) {
            $rgb .= $out;
        } else {
            for ($i = 0; $i < $width; $i++) {
                $o = $i * 4;
                $alpha = ord($out[$o + 3]) / 255;
                $r = (int) round((ord($out[$o]) * $alpha) + (255 * (1 - $alpha)));
                $g = (int) round((ord($out[$o + 1]) * $alpha) + (255 * (1 - $alpha)));
                $bch = (int) round((ord($out[$o + 2]) * $alpha) + (255 * (1 - $alpha)));
                $rgb .= chr($r) . chr($g) . chr($bch);
            }
        }
    }

    return [
        'width' => $width,
        'height' => $height,
        'data' => $rgb,
    ];
}

function pdf_assemble(array $pageContents, array $images = []): string
{
    if ($pageContents === []) {
        $pageContents = [''];
    }

    $cleanImages = [];
    foreach ($images as $image) {
        if (!is_array($image) || empty($image['data'])) {
            continue;
        }
        $filter = (string) ($image['filter'] ?? 'DCTDecode');
        if ($filter !== 'DCTDecode' && $filter !== 'FlateDecode') {
            $filter = 'DCTDecode';
        }
        $cleanImages[] = [
            'data' => (string) $image['data'],
            'width' => max(1, (int) ($image['width'] ?? 1)),
            'height' => max(1, (int) ($image['height'] ?? 1)),
            'filter' => $filter,
        ];
    }

    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>'; // 1
    $objects[] = ''; // 2 pages — filled later
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>'; // 3
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'; // 4

    $xParts = [];
    foreach ($cleanImages as $index => $image) {
        $objNum = 5 + $index;
        $name = 'Im' . ($index + 1);
        $xParts[] = '/' . $name . ' ' . $objNum . ' 0 R';
        $objects[] = '<< /Type /XObject /Subtype /Image /Width ' . $image['width']
            . ' /Height ' . $image['height']
            . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /' . $image['filter'] . ' /Length '
            . strlen($image['data']) . " >>\nstream\n" . $image['data'] . "\nendstream";
    }

    $xObjectRes = $xParts === [] ? '' : (' /XObject << ' . implode(' ', $xParts) . ' >>');
    $pageKids = [];
    foreach ($pageContents as $content) {
        $pageObj = count($objects) + 1;
        $contentObj = $pageObj + 1;
        $pageKids[] = $pageObj . ' 0 R';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents '
            . $contentObj . ' 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >>'
            . $xObjectRes . ' >> >>';
        $objects[] = "<< /Length " . strlen((string) $content) . " >>\nstream\n" . $content . "endstream";
    }

    $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $pageKids) . '] /Count ' . count($pageKids) . ' >>';

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $count = count($objects) + 1;
    $pdf .= "xref\n0 " . $count . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . $count . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref . "\n%%EOF";

    return $pdf;
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
