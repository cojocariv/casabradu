<?php

declare(strict_types=1);

function oferta_default_lines(): array
{
    $lines = [];
    foreach (product_price_rows() as $row) {
        $item = product_by_slug($row['slug']);
        $choice = is_array($item) ? product_choice($item, $row['optiune']) : null;
        $pret = is_array($choice) ? ($choice['pret'] ?? null) : null;
        $image = '';
        if (is_array($item)) {
            $offerImage = trim((string) ($item['offer_image'] ?? ''));
            $image = $offerImage !== '' ? $offerImage : (string) ($item['image'] ?? '');
        }
        $lines[] = [
            'slug' => product_price_field($row),
            'titlu' => $row['produs'] . ($row['varianta'] !== '' ? ', ' . $row['varianta'] : ''),
            'grup' => $row['produs'],
            'varianta' => $row['varianta'],
            'image' => $image,
            'cantitate' => '',
            'pret' => is_numeric($pret) && (float) $pret > 0 ? product_price_input($pret) : '',
        ];
    }

    return $lines;
}

function oferta_parse_lines(array $posted): array
{
    $titles = $posted['titlu'] ?? [];
    $qtys = $posted['cantitate'] ?? [];
    $prets = $posted['pret'] ?? [];
    $slugs = $posted['slug'] ?? [];
    $images = $posted['image'] ?? [];
    if (!is_array($titles) || !is_array($qtys) || !is_array($prets)) {
        return ['error' => 'Completează produsele din ofertă.', 'lines' => []];
    }

    $lines = [];
    foreach ($titles as $index => $title) {
        $qty = (int) ($qtys[$index] ?? 0);
        if ($qty < 1) {
            continue;
        }
        if ($qty > 99999) {
            return ['error' => 'Cantitatea maximă pe linie este 99999.', 'lines' => []];
        }
        $title = trim((string) $title);
        if ($title === '') {
            return ['error' => 'Fiecare linie selectată trebuie să aibă denumirea produsului.', 'lines' => []];
        }
        $parsed = product_parse_price(trim((string) ($prets[$index] ?? '')));
        if ($parsed === 'invalid') {
            return ['error' => 'Scrie prețul în lei, de exemplu 12,50.', 'lines' => []];
        }
        $slug = (string) ($slugs[$index] ?? '');
        $image = (string) ($images[$index] ?? '');
        if ($image === '' && $slug !== '') {
            $base = explode(':', $slug, 2)[0];
            $item = product_by_slug($base);
            if (is_array($item)) {
                $offerImage = trim((string) ($item['offer_image'] ?? ''));
                $image = $offerImage !== '' ? $offerImage : (string) ($item['image'] ?? '');
            }
        }
        $lines[] = [
            'slug' => $slug !== '' ? $slug : 'manual',
            'titlu' => function_exists('mb_substr') ? mb_substr($title, 0, 120) : substr($title, 0, 120),
            'cantitate' => $qty,
            'pret' => $parsed,
            'image' => $image,
        ];
    }

    return ['lines' => $lines];
}

function oferta_table(array $lines): array
{
    require_once __DIR__ . '/factura.php';

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
            'image' => (string) ($line['image'] ?? ''),
        ];
    }

    return [
        'rows' => $rows,
        'total' => $complete && $rows !== [] ? $total : null,
        'tva' => ($complete && $rows !== [] && $total > 0) ? (int) round($total * 20 / 120) : null,
    ];
}

function oferta_pdf(array $meta, array $table): string
{
    require_once __DIR__ . '/pdf.php';
    require_once __DIR__ . '/factura.php';

    $margin = 36.0;
    $pageW = 595.0;
    $pageH = 842.0;
    $contentW = $pageW - (2 * $margin);
    $rowH = 52.0;
    $cols = [
        'nr' => 26.0,
        'foto' => 48.0,
        'titlu' => 236.0,
        'cant' => 48.0,
        'pret' => 78.0,
        'suma' => 87.0,
    ];
    $tableW = array_sum($cols);
    $tableX = $margin;
    $forest = [0.110, 0.231, 0.188];
    $cream = [0.969, 0.961, 0.945];
    $soft = [0.945, 0.937, 0.918];

    $images = [];
    $logo = pdf_logo_image();
    $logoName = null;
    if ($logo !== null) {
        $images[] = $logo;
        $logoName = 'Im1';
    }

    $imageMap = [];
    foreach (($table['rows'] ?? []) as $index => $row) {
        $img = pdf_image_from_path((string) ($row['image'] ?? ''));
        if ($img === null) {
            continue;
        }
        $images[] = $img;
        $imageMap[(int) $index] = 'Im' . count($images);
    }

    $rows = is_array($table['rows'] ?? null) ? $table['rows'] : [];
    $pages = [];
    $offset = 0;
    $pageNo = 0;

    do {
        $pageNo++;
        $ops = [];

        $ops[] = pdf_fill_rect(0, $pageH - 88, $pageW, 88, $forest[0], $forest[1], $forest[2]);
        $ops[] = pdf_fill_rect(0, $pageH - 92, $pageW, 4, 0.82, 0.74, 0.58);

        $logoW = 0.0;
        $logoH = 0.0;
        if ($logoName !== null && $logo !== null) {
            $logoH = 48.0;
            $logoW = $logoH * ($logo['width'] / max(1, $logo['height']));
            if ($logoW > 130) {
                $logoW = 130.0;
                $logoH = $logoW * ($logo['height'] / max(1, $logo['width']));
            }
            $ops[] = sprintf(
                'q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q',
                $logoW,
                $logoH,
                $margin,
                $pageH - 20 - $logoH,
                $logoName
            );
        }

        // Text block centered in the free space to the right of the logo
        $textLeft = $margin + ($logoW > 0 ? $logoW + 18 : 0);
        $textRight = $pageW - $margin;
        $textCenter = ($textLeft + $textRight) / 2;
        $title = 'OFERTA COMERCIALA';
        $tagline = 'Cafea artizanala import Italia, aromata si fina';
        $ops[] = sprintf(
            'q 1 1 1 rg BT /F2 16 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
            $textCenter - (pdf_text_width($title, 16) / 2),
            $pageH - 32,
            pdf_escape($title)
        );
        $ops[] = sprintf(
            'q 0.93 0.89 0.82 rg BT /F1 11 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
            $textCenter - (pdf_text_width(SITE_NAME, 11) / 2),
            $pageH - 50,
            pdf_escape(SITE_NAME)
        );
        $ops[] = sprintf(
            'q 0.85 0.80 0.70 rg BT /F1 8 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
            $textCenter - (pdf_text_width($tagline, 8) / 2),
            $pageH - 66,
            pdf_escape($tagline)
        );

        $y = $pageH - 112;
        $ops[] = pdf_text($margin, $y, 'Nr. ' . pdf_clip((string) ($meta['numar'] ?? ''), 28), 11, 'F2');
        $ops[] = pdf_text_right($pageW - $margin, $y, 'Data: ' . (string) ($meta['data'] ?? ''), 10, 'F1');
        $y -= 15;

        $client = trim((string) ($meta['client'] ?? ''));
        if ($client !== '') {
            $ops[] = pdf_text($margin, $y, 'Pentru: ' . pdf_clip($client, 62), 10, 'F1');
            $y -= 13;
        }
        $contact = trim((string) ($meta['contact'] ?? ''));
        if ($contact !== '') {
            $ops[] = pdf_text($margin, $y, 'Contact: ' . pdf_clip($contact, 62), 9, 'F1');
            $y -= 13;
        }
        $valid = trim((string) ($meta['valabilitate'] ?? ''));
        if ($valid !== '') {
            $ops[] = pdf_text($margin, $y, 'Valabila pana la: ' . pdf_clip($valid, 40), 9, 'F1');
            $y -= 13;
        }

        if ($pageNo === 1) {
            $intro = trim((string) ($meta['intro'] ?? ''));
            if ($intro === '') {
                $intro = 'Va propunem cafea artizanala Casa Bradu Caffe, import Italia, aromata si fina.';
            }
            foreach (pdf_wrap($intro, 88, 3) as $introLine) {
                $ops[] = pdf_text($margin, $y, $introLine, 9, 'F1');
                $y -= 12;
            }
            $y -= 3;
        } else {
            $ops[] = pdf_text($margin, $y, 'Continuare oferta — pagina ' . $pageNo, 9, 'F1');
            $y -= 15;
        }

        $headerH = 22.0;
        $headerY = $y - $headerH;
        $ops[] = pdf_fill_rect($tableX, $headerY, $tableW, $headerH, $forest[0], $forest[1], $forest[2]);
        $x = $tableX;
        foreach (
            [
                ['nr', 'Nr'],
                ['foto', 'Foto'],
                ['titlu', 'Produs'],
                ['cant', 'Cant.'],
                ['pret', 'Pret, lei'],
                ['suma', 'Suma, lei'],
            ] as $header
        ) {
            [$key, $label] = $header;
            $w = $cols[$key];
            $ty = $headerY + 7;
            if ($key === 'pret' || $key === 'suma') {
                $tx = $x + $w - 4 - pdf_text_width($label, 8);
            } elseif ($key === 'nr' || $key === 'foto' || $key === 'cant') {
                $tx = $x + ($w / 2) - (pdf_text_width($label, 8) / 2);
            } else {
                $tx = $x + 4;
            }
            $ops[] = sprintf(
                'q 1 1 1 rg BT /F2 8 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
                $tx,
                $ty,
                pdf_escape($label)
            );
            $x += $w;
        }
        $y = $headerY;

        $isLastPageGuess = true;
        $drawn = 0;
        $remaining = count($rows) - $offset;
        while ($offset < count($rows)) {
            $needFooter = ($offset + 1 >= count($rows));
            $bottomLimit = $needFooter ? 130.0 : 68.0;
            if ($y - $rowH < $bottomLimit && $drawn > 0) {
                $isLastPageGuess = false;
                break;
            }

            $row = $rows[$offset];
            $y -= $rowH;
            $bg = ($offset % 2 === 0) ? $cream : $soft;
            $ops[] = pdf_fill_rect($tableX, $y, $tableW, $rowH, $bg[0], $bg[1], $bg[2]);
            $ops[] = pdf_rect($tableX, $y, $tableW, $rowH, false, 0.4);

            $x = $tableX;
            $cells = [
                'nr' => (string) ($offset + 1),
                'titlu' => pdf_clip((string) ($row['titlu'] ?? ''), 40),
                'cant' => (string) (int) ($row['cantitate'] ?? 0),
                'pret' => lei($row['pret'] ?? null),
                'suma' => lei($row['suma'] ?? null),
            ];
            foreach ($cols as $key => $w) {
                $ops[] = pdf_line($x, $y, $x, $y + $rowH, 0.35);
                if ($key === 'foto') {
                    if (isset($imageMap[$offset])) {
                        $box = 38.0;
                        $ops[] = sprintf(
                            'q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q',
                            $box,
                            $box,
                            $x + (($w - $box) / 2),
                            $y + (($rowH - $box) / 2),
                            $imageMap[$offset]
                        );
                    }
                } elseif ($key === 'cant' || $key === 'pret' || $key === 'suma') {
                    $ops[] = pdf_text_right($x + $w - 4, $y + 20, $cells[$key], 9, $key === 'suma' ? 'F2' : 'F1');
                } elseif ($key === 'nr') {
                    $ops[] = pdf_text_centered($x + ($w / 2), $y + 20, $cells[$key], 9, 'F1');
                } else {
                    $ops[] = pdf_text($x + 4, $y + 20, $cells[$key], 9, 'F1');
                }
                $x += $w;
            }
            $ops[] = pdf_line($tableX + $tableW, $y, $tableX + $tableW, $y + $rowH, 0.35);

            $offset++;
            $drawn++;
        }

        $isLast = $offset >= count($rows);
        if ($isLast) {
            $y -= 24;
            $ops[] = pdf_fill_rect($margin, $y - 6, $contentW, 28, $forest[0], $forest[1], $forest[2]);
            $total = ($table['total'] ?? null) === null ? 'la confirmare' : lei($table['total']) . ' lei';
            $ops[] = sprintf(
                'q 1 1 1 rg BT /F2 12 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
                $margin + 10,
                $y + 4,
                pdf_escape('Total oferta: ' . $total)
            );
            if (($table['tva'] ?? null) !== null) {
                $tvaLabel = 'Inclusiv TVA: ' . lei($table['tva']) . ' lei';
                $ops[] = sprintf(
                    'q 0.90 0.86 0.78 rg BT /F1 9 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
                    $pageW - $margin - 10 - pdf_text_width($tvaLabel, 9),
                    $y + 4,
                    pdf_escape($tvaLabel)
                );
            }
            $y -= 28;
            $ops[] = pdf_text($margin, $y, 'Preturile sunt in lei. Oferta poate fi confirmata telefonic sau pe email.', 8, 'F1');
            $y -= 12;
            $ops[] = pdf_text($margin, $y, 'Contact: ' . SITE_EMAIL, 8, 'F1');
            if (SITE_PHONE !== '') {
                $y -= 11;
                $ops[] = pdf_text($margin, $y, 'Telefon: ' . SITE_PHONE, 8, 'F1');
            }
            $y -= 18;
            $ops[] = pdf_text($margin, $y, 'Cu stima,', 9, 'F1');
            $y -= 12;
            $ops[] = pdf_text($margin, $y, COMPANY_LEGAL, 9, 'F2');
        }

        $ops[] = pdf_fill_rect(0, 0, $pageW, 28, $forest[0], $forest[1], $forest[2]);
        $ops[] = sprintf(
            'q 0.90 0.86 0.78 rg BT /F1 8 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
            $margin,
            10,
            pdf_escape(SITE_DOMAIN . '  ·  ' . SITE_EMAIL)
        );
        $pageLabel = 'Pagina ' . $pageNo;
        $ops[] = sprintf(
            'q 0.90 0.86 0.78 rg BT /F1 8 Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET Q',
            $pageW - $margin - pdf_text_width($pageLabel, 8),
            10,
            pdf_escape($pageLabel)
        );

        $pages[] = pdf_ops($ops);
        unset($remaining, $isLastPageGuess);

        if ($isLast || $pageNo > 20) {
            break;
        }
    } while (true);

    return pdf_assemble($pages, $images);
}
