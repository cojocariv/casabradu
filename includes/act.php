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
