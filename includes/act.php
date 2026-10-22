<?php

declare(strict_types=1);

function act_unit_for_line(array $line): string
{
    $slug = strtolower((string) ($line['slug'] ?? ''));
    $title = (string) ($line['titlu'] ?? '');
    if (strpos($slug, '1000') !== false || preg_match('/\b1\s*kg\b/iu', $title) === 1) {
        return 'kg';
    }
    if (preg_match('/\b(250|500)\s*g\b/iu', $title) === 1 || preg_match('/:(250|500)$/', $slug) === 1) {
        return 'buc.';
    }
    if (strpos($slug, 'cutie') !== false || preg_match('/cuti/iu', $title) === 1) {
        return 'cutie';
    }

    return 'buc.';
}

function act_lei_words(int $bani): string
{
    if ($bani < 0) {
        return 'minus ' . act_lei_words(abs($bani));
    }

    $lei = intdiv($bani, 100);
    $frac = $bani % 100;
    $leiPart = act_int_words($lei);
    $leiWord = act_lei_plural($lei);
    if ($frac === 0) {
        return $leiPart . ' ' . $leiWord;
    }

    return $leiPart . ' ' . $leiWord . ' și ' . act_int_words($frac) . ' ' . act_bani_plural($frac);
}

function act_lei_plural(int $n): string
{
    $n = abs($n) % 100;
    if ($n === 1) {
        return 'leu';
    }
    if ($n >= 2 && $n <= 19) {
        return 'lei';
    }

    return 'de lei';
}

function act_bani_plural(int $n): string
{
    $n = abs($n) % 100;
    if ($n === 1) {
        return 'ban';
    }
    if ($n >= 2 && $n <= 19) {
        return 'bani';
    }

    return 'de bani';
}

function act_int_words(int $n): string
{
    if ($n === 0) {
        return 'zero';
    }

    static $units = [
        1 => 'unu', 2 => 'doi', 3 => 'trei', 4 => 'patru', 5 => 'cinci',
        6 => 'șase', 7 => 'șapte', 8 => 'opt', 9 => 'nouă', 10 => 'zece',
        11 => 'unsprezece', 12 => 'doisprezece', 13 => 'treisprezece', 14 => 'paisprezece',
        15 => 'cincisprezece', 16 => 'șaisprezece', 17 => 'șaptesprezece',
        18 => 'optsprezece', 19 => 'nouăsprezece',
    ];
    static $tens = [
        2 => 'douăzeci', 3 => 'treizeci', 4 => 'patruzeci', 5 => 'cincizeci',
        6 => 'șaizeci', 7 => 'șaptezeci', 8 => 'optzeci', 9 => 'nouăzeci',
    ];

    if ($n < 0) {
        return 'minus ' . act_int_words(abs($n));
    }
    if ($n < 20) {
        return $units[$n];
    }
    if ($n < 100) {
        $t = intdiv($n, 10);
        $u = $n % 10;
        if ($u === 0) {
            return $tens[$t];
        }
        $unit = $u === 1 ? 'unu' : ($u === 2 ? 'doi' : $units[$u]);

        return $tens[$t] . ' și ' . $unit;
    }
    if ($n < 1000) {
        $h = intdiv($n, 100);
        $r = $n % 100;
        if ($h === 1) {
            $head = 'o sută';
        } elseif ($h === 2) {
            $head = 'două sute';
        } else {
            $head = $units[$h] . ' sute';
        }

        return $r === 0 ? $head : $head . ' ' . act_int_words($r);
    }
    if ($n < 1000000) {
        $th = intdiv($n, 1000);
        $r = $n % 1000;
        if ($th === 1) {
            $head = 'o mie';
        } elseif ($th === 2) {
            $head = 'două mii';
        } elseif ($th < 20) {
            $head = act_int_words($th) . ' mii';
        } else {
            $head = act_int_words($th) . ' de mii';
        }

        return $r === 0 ? $head : $head . ' ' . act_int_words($r);
    }

    $m = intdiv($n, 1000000);
    $r = $n % 1000000;
    if ($m === 1) {
        $head = 'un milion';
    } elseif ($m < 20) {
        $head = act_int_words($m) . ' milioane';
    } else {
        $head = act_int_words($m) . ' de milioane';
    }

    return $r === 0 ? $head : $head . ' ' . act_int_words($r);
}

function act_receiver_from_order(array $order): array
{
    $requisites = (string) ($order['rechizite'] ?? '');

    return [
        'nume' => requisite_value($requisites, 'Denumirea companiei') !== ''
            ? requisite_value($requisites, 'Denumirea companiei')
            : (string) ($order['nume'] ?? ''),
        'idno' => requisite_value($requisites, 'IDNO'),
        'tva' => requisite_value($requisites, 'Cod TVA'),
        'adresa' => requisite_value($requisites, 'Adresa juridică'),
        'iban' => requisite_value($requisites, 'IBAN și banca'),
        'contact' => (string) ($order['nume'] ?? ''),
        'telefon' => (string) ($order['telefon'] ?? ''),
        'email' => (string) ($order['email'] ?? ''),
        'livrare' => (string) ($order['adresa'] ?? ''),
    ];
}

function act_supplier(): array
{
    return [
        'nume' => COMPANY_LEGAL,
        'idno' => COMPANY_IDNO,
        'tva' => COMPANY_TVA,
        'iban' => COMPANY_IBAN,
        'banca' => COMPANY_BANK,
        'email' => SITE_ORDERS_EMAIL,
        'adresa' => defined('COMPANY_ADDRESS') ? (string) COMPANY_ADDRESS : 'Republica Moldova',
    ];
}

function act_fill(string $value, int $width = 32): string
{
    $value = trim($value);
    if ($value === '' || $value === '-') {
        return str_repeat('_', max(8, $width));
    }

    return $value;
}

function act_pdf(array $order, string $date, string $place, array $supplier, array $receiver, array $table): string
{
    require_once __DIR__ . '/pdf.php';

    $margin = 40.0;
    $pageW = 595.0;
    $pageH = 842.0;
    $contentW = $pageW - (2 * $margin);
    $y = $pageH - 36.0;
    $ops = [];
    $logo = pdf_logo_image();
    $hasLogo = $logo !== null;

    if ($hasLogo) {
        $logoH = 48.0;
        $logoW = $logoH * ($logo['width'] / max(1, $logo['height']));
        if ($logoW > 140) {
            $logoW = 140.0;
            $logoH = $logoW * ($logo['height'] / max(1, $logo['width']));
        }
        $logoX = ($pageW - $logoW) / 2;
        $ops[] = sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /Im1 Do Q', $logoW, $logoH, $logoX, $y - $logoH);
        $y -= $logoH + 8;
    }

    $ops[] = pdf_text_centered($pageW / 2, $y, 'ACT DE PREDARE-PRIMIRE A MARFURILOR', 12, 'F2');
    $y -= 14;
    $ops[] = pdf_text_centered(
        $pageW / 2,
        $y,
        'nr. ' . ($order['id'] ?? '') . ' din ' . $date . ', ' . $place,
        10,
        'F1'
    );
    $y -= 12;
    $ops[] = pdf_line($margin, $y, $pageW - $margin, $y, 1);
    $y -= 16;

    $colMid = $margin + ($contentW / 2) + 10;
    $leftY = $y;
    $ops[] = pdf_text($margin, $leftY, 'Predator (Furnizor)', 9, 'F2');
    $leftY -= 12;
    $ops[] = pdf_text($margin, $leftY, pdf_clip((string) $supplier['nume'], 42), 9, 'F1');
    $leftY -= 11;
    $ops[] = pdf_text($margin, $leftY, 'IDNO: ' . $supplier['idno'], 9, 'F1');
    $leftY -= 11;
    $ops[] = pdf_text($margin, $leftY, 'TVA: ' . $supplier['tva'], 9, 'F1');
    $leftY -= 11;
    $ops[] = pdf_text($margin, $leftY, 'Adresa: ' . pdf_clip(act_fill((string) $supplier['adresa'], 28), 40), 9, 'F1');
    $leftY -= 11;
    $ops[] = pdf_text($margin, $leftY, 'IBAN: ' . $supplier['iban'], 8, 'F1');
    $leftY -= 11;
    $ops[] = pdf_text($margin, $leftY, 'Banca: ' . pdf_clip((string) $supplier['banca'], 36), 9, 'F1');

    $rightY = $y;
    $ops[] = pdf_text($colMid, $rightY, 'Primitor (Cumparator)', 9, 'F2');
    $rightY -= 12;
    $ops[] = pdf_text($colMid, $rightY, pdf_clip(act_fill((string) $receiver['nume'], 34), 42), 9, 'F1');
    $rightY -= 11;
    $ops[] = pdf_text($colMid, $rightY, 'IDNO: ' . act_fill((string) $receiver['idno'], 22), 9, 'F1');
    $rightY -= 11;
    $ops[] = pdf_text($colMid, $rightY, 'Cod TVA: ' . act_fill((string) ($receiver['tva'] ?? ''), 20), 9, 'F1');
    $rightY -= 11;
    $ops[] = pdf_text($colMid, $rightY, 'Adresa: ' . pdf_clip(act_fill((string) $receiver['adresa'], 28), 40), 9, 'F1');
    $rightY -= 11;
    $ops[] = pdf_text($colMid, $rightY, 'IBAN: ' . pdf_clip(act_fill((string) ($receiver['iban'] ?? ''), 28), 40), 8, 'F1');
    $rightY -= 11;
    $ops[] = pdf_text($colMid, $rightY, 'Contact: ' . pdf_clip(act_fill((string) ($receiver['contact'] ?? ''), 26), 40), 9, 'F1');
    $rightY -= 11;
    $ops[] = pdf_text($colMid, $rightY, 'Telefon: ' . act_fill(
        (($receiver['telefon'] ?? '') === '-' ? '' : (string) ($receiver['telefon'] ?? '')),
        18
    ), 9, 'F1');

    $y = min($leftY, $rightY) - 14;
    $ops[] = pdf_line($margin, $y, $pageW - $margin, $y, 0.7);
    $y -= 14;

    $ops[] = pdf_text($margin, $y, 'In temeiul comenzii / contului nr. ' . ($order['id'] ?? '') . ' din ' . $date . ',', 9, 'F1');
    $y -= 11;
    $ops[] = pdf_text($margin, $y, 'Predatorul a predat, iar Primitorul a primit marfurile enumerate mai jos.', 9, 'F1');
    $y -= 11;
    $ops[] = pdf_text($margin, $y, 'Locul predarii: ' . pdf_clip(act_fill((string) ($receiver['livrare'] ?? ''), 48), 70), 9, 'F1');
    $y -= 11;
    $ops[] = pdf_text($margin, $y, 'Data predarii efective: ____________________  Ora: ________', 9, 'F1');
    $y -= 16;

    $cols = [
        'nr' => 24.0,
        'titlu' => 220.0,
        'um' => 36.0,
        'cant' => 40.0,
        'pret' => 85.0,
        'suma' => 90.0,
    ];
    $rowH = 18.0;
    $tableX = $margin;
    $tableW = array_sum($cols);
    $headerY = $y - $rowH;
    $ops[] = pdf_fill_rect($tableX, $headerY, $tableW, $rowH, 0.95, 0.94, 0.91);
    $ops[] = pdf_rect($tableX, $headerY, $tableW, $rowH, false, 0.8);

    $x = $tableX;
    $headers = [
        ['nr', 'Nr.'],
        ['titlu', 'Denumirea marfurilor'],
        ['um', 'U.M.'],
        ['cant', 'Cant.'],
        ['pret', 'Pret, lei'],
        ['suma', 'Valoare'],
    ];
    foreach ($headers as $header) {
        [$key, $label] = $header;
        $w = $cols[$key];
        $ops[] = pdf_line($x, $headerY, $x, $headerY + $rowH, 0.6);
        if ($key === 'cant' || $key === 'pret' || $key === 'suma') {
            $ops[] = pdf_text_right($x + $w - 3, $headerY + 5, $label, 8, 'F2');
        } elseif ($key === 'nr' || $key === 'um') {
            $ops[] = pdf_text_centered($x + ($w / 2), $headerY + 5, $label, 8, 'F2');
        } else {
            $ops[] = pdf_text($x + 3, $headerY + 5, $label, 8, 'F2');
        }
        $x += $w;
    }
    $ops[] = pdf_line($tableX + $tableW, $headerY, $tableX + $tableW, $headerY + $rowH, 0.6);

    $y = $headerY;
    $rows = $table['rows'] ?? [];
    $orderLines = $order['linii'] ?? [];
    foreach ($rows as $index => $row) {
        $y -= $rowH;
        if ($y < 210) {
            break;
        }
        $line = is_array($orderLines[$index] ?? null) ? $orderLines[$index] : [];
        $ops[] = pdf_rect($tableX, $y, $tableW, $rowH, false, 0.6);
        $x = $tableX;
        $pret = lei($row['pret'] ?? null);
        $suma = lei($row['suma'] ?? null);
        $cells = [
            'nr' => (string) ($index + 1),
            'titlu' => pdf_clip((string) ($row['titlu'] ?? ''), 38),
            'um' => act_unit_for_line($line),
            'cant' => (string) (int) ($row['cantitate'] ?? 0),
            'pret' => $pret === 'la confirmare' ? '______' : $pret,
            'suma' => $suma === 'la confirmare' ? '______' : $suma,
        ];
        foreach ($cols as $key => $w) {
            $ops[] = pdf_line($x, $y, $x, $y + $rowH, 0.5);
            if ($key === 'cant' || $key === 'pret' || $key === 'suma') {
                $ops[] = pdf_text_right($x + $w - 3, $y + 5, $cells[$key], 8, 'F1');
            } elseif ($key === 'nr' || $key === 'um') {
                $ops[] = pdf_text_centered($x + ($w / 2), $y + 5, $cells[$key], 8, 'F1');
            } else {
                $ops[] = pdf_text($x + 3, $y + 5, $cells[$key], 8, 'F1');
            }
            $x += $w;
        }
        $ops[] = pdf_line($tableX + $tableW, $y, $tableX + $tableW, $y + $rowH, 0.5);
    }

    // blank rows for handwritten extra lines
    for ($blank = 0; $blank < 2; $blank++) {
        $y -= $rowH;
        if ($y < 190) {
            break;
        }
        $ops[] = pdf_rect($tableX, $y, $tableW, $rowH, false, 0.5);
        $x = $tableX;
        foreach ($cols as $w) {
            $ops[] = pdf_line($x, $y, $x, $y + $rowH, 0.4);
            $x += $w;
        }
        $ops[] = pdf_line($tableX + $tableW, $y, $tableX + $tableW, $y + $rowH, 0.4);
    }

    $y -= 16;
    $totalBani = $table['total'] ?? null;
    $total = $totalBani === null ? '____________________ lei' : lei($totalBani) . ' lei';
    $ops[] = pdf_text($margin, $y, 'Valoarea totala: ' . $total, 10, 'F2');
    $y -= 12;
    if (is_int($totalBani)) {
        $ops[] = pdf_text($margin, $y, 'In litere: ' . pdf_clip(act_lei_words($totalBani), 78), 9, 'F1');
        $y -= 11;
    } else {
        $ops[] = pdf_text($margin, $y, 'In litere: ________________________________________________', 9, 'F1');
        $y -= 11;
    }
    if (($table['tva'] ?? null) !== null) {
        $ops[] = pdf_text($margin, $y, 'Inclusiv TVA: ' . lei($table['tva']) . ' lei', 9, 'F1');
        $y -= 12;
    }

    $ops[] = pdf_text($margin, $y, 'Marfurile au fost predate in stare corespunzatoare. Primitorul confirma receptia', 8, 'F1');
    $y -= 10;
    $ops[] = pdf_text($margin, $y, 'si declara ca nu are pretentii, in afara rezervelor de mai jos.', 8, 'F1');
    $y -= 13;
    $note = trim((string) ($order['nota'] ?? ''));
    if ($note !== '' && stripos($note, 'Act de predare') === false) {
        $ops[] = pdf_text($margin, $y, 'Rezerve / mentiuni: ' . pdf_clip($note, 62), 9, 'F1');
        $y -= 11;
        $ops[] = pdf_text($margin, $y, '________________________________________________________________', 9, 'F1');
    } else {
        $ops[] = pdf_text($margin, $y, 'Rezerve / mentiuni (completați cu pixul, dacă e cazul):', 9, 'F1');
        $y -= 12;
        $ops[] = pdf_text($margin, $y, '________________________________________________________________', 9, 'F1');
        $y -= 12;
        $ops[] = pdf_text($margin, $y, '________________________________________________________________', 9, 'F1');
    }
    $y -= 12;
    $ops[] = pdf_text($margin, $y, 'Actul este intocmit in doua exemplare, cate unul pentru fiecare parte.', 8, 'F1');
    $y -= 22;

    $signW = ($contentW - 20) / 2;
    $leftX = $margin;
    $rightX = $margin + $signW + 20;

    $ops[] = pdf_text($leftX, $y, 'Predat marfuri', 9, 'F2');
    $ops[] = pdf_text($rightX, $y, 'Primit marfuri', 9, 'F2');
    $y -= 12;
    $ops[] = pdf_text($leftX, $y, pdf_clip((string) $supplier['nume'], 34), 8, 'F1');
    $ops[] = pdf_text($rightX, $y, pdf_clip(act_fill((string) $receiver['nume'], 30), 34), 8, 'F1');
    $y -= 14;
    $ops[] = pdf_text($leftX, $y, 'Nume, prenume: ____________________', 8, 'F1');
    $ops[] = pdf_text($rightX, $y, 'Nume, prenume: ____________________', 8, 'F1');
    $y -= 16;
    $ops[] = pdf_text($leftX, $y, 'Functia: ___________________________', 8, 'F1');
    $ops[] = pdf_text($rightX, $y, 'Functia: ___________________________', 8, 'F1');
    $y -= 18;
    $ops[] = pdf_text($leftX, $y, 'Semnatura: _________________________', 8, 'F1');
    $ops[] = pdf_text($rightX, $y, 'Semnatura: _________________________', 8, 'F1');
    $y -= 16;
    $ops[] = pdf_text($leftX, $y, 'L.S.', 9, 'F2');
    $ops[] = pdf_text($rightX, $y, 'L.S.', 9, 'F2');
    $y -= 14;
    $ops[] = pdf_text($leftX, $y, 'Data: __________', 8, 'F1');
    $ops[] = pdf_text($rightX, $y, 'Data: __________', 8, 'F1');

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
    $pdf .= "xref\n0 " . $count . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . $count . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref . "\n%%EOF";

    return $pdf;
}
