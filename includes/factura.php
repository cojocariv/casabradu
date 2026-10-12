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
