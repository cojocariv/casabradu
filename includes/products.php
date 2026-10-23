<?php

declare(strict_types=1);

function product_catalog(): array
{
    return product_apply_prices(product_catalog_items());
}

function product_variant_defs(array $item): array
{
    $options = $item['options'] ?? [];
    if (!is_array($options) || $options === []) {
        return [];
    }
    $packs = $item['packs'] ?? [];
    if (!is_array($packs) || $packs === []) {
        $defs = [];
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }
            $key = (string) ($option['key'] ?? '');
            if (preg_match('/^[a-z0-9]{1,20}$/', $key) !== 1) {
                continue;
            }
            $defs[] = [
                'key' => $key,
                'label' => (string) ($option['label'] ?? ''),
                'flavor' => $key,
                'pack' => '',
                'image' => (string) ($option['image'] ?? ''),
                'offer_image' => (string) ($option['offer_image'] ?? ''),
            ];
        }

        return $defs;
    }

    $defs = [];
    foreach ($options as $option) {
        if (!is_array($option)) {
            continue;
        }
        $flavor = (string) ($option['key'] ?? '');
        $flavorLabel = (string) ($option['label'] ?? '');
        if (preg_match('/^[a-z0-9]{1,12}$/', $flavor) !== 1) {
            continue;
        }
        foreach ($packs as $pack) {
            if (!is_array($pack)) {
                continue;
            }
            $packKey = (string) ($pack['key'] ?? '');
            $packLabel = (string) ($pack['label'] ?? '');
            if (preg_match('/^[a-z0-9]{1,8}$/', $packKey) !== 1) {
                continue;
            }
            $key = $flavor . $packKey;
            if (preg_match('/^[a-z0-9]{1,20}$/', $key) !== 1) {
                continue;
            }
            $defs[] = [
                'key' => $key,
                'label' => trim($flavorLabel . ', ' . $packLabel, ', '),
                'flavor' => $flavor,
                'pack' => $packKey,
            ];
        }
    }

    return $defs;
}

function product_price_rows(): array
{
    $rows = [];
    foreach (product_catalog_items() as $item) {
        $defs = product_variant_defs($item);
        if ($defs !== []) {
            foreach ($defs as $def) {
                $rows[] = [
                    'slug' => (string) $item['slug'],
                    'optiune' => (string) $def['key'],
                    'produs' => (string) $item['title'],
                    'varianta' => (string) $def['label'],
                ];
            }
            continue;
        }
        $rows[] = [
            'slug' => (string) $item['slug'],
            'optiune' => '',
            'produs' => (string) $item['title'],
            'varianta' => '',
        ];
    }

    return $rows;
}

function product_price_field(array $row): string
{
    return $row['optiune'] === '' ? $row['slug'] : $row['slug'] . ':' . $row['optiune'];
}

function product_price_input($pret): string
{
    if (!is_numeric($pret) || (float) $pret <= 0) {
        return '';
    }

    return number_format((float) $pret, 2, ',', '');
}

function product_parse_price(string $raw): ?string
{
    $raw = str_replace(["\xc2\xa0", ' '], '', trim($raw));
    if ($raw === '') {
        return null;
    }
    $raw = str_replace(',', '.', $raw);
    if (preg_match('/^(?:0|[1-9]\d{0,6})(?:\.\d+)?$/', $raw) !== 1) {
        return 'invalid';
    }
    $value = round((float) $raw, 2);
    if ($value <= 0 || $value > 999999.99) {
        return 'invalid';
    }

    return number_format($value, 2, '.', '');
}

function product_saved_prices(): array
{
    static $prices = null;
    if (is_array($prices)) {
        return $prices;
    }
    $prices = [];
    try {
        $rows = db()->query('SELECT slug, optiune, pret FROM preturi');
        if ($rows === false) {
            return $prices;
        }
        foreach ($rows as $row) {
            $key = ((string) $row['optiune']) === '' ? (string) $row['slug'] : $row['slug'] . ':' . $row['optiune'];
            $prices[$key] = ($row['pret'] === null || $row['pret'] === '') ? null : (float) $row['pret'];
        }
    } catch (Throwable $e) {
        $prices = [];
    }

    return $prices;
}

function product_apply_prices(array $items): array
{
    $saved = product_saved_prices();
    foreach ($items as $index => $item) {
        if (!is_array($item)) {
            continue;
        }
        $defs = product_variant_defs($item);
        if ($defs !== []) {
            $variants = [];
            foreach ($defs as $def) {
                $key = (string) $item['slug'] . ':' . (string) $def['key'];
                $def['pret'] = array_key_exists($key, $saved) ? $saved[$key] : null;
                $variants[] = $def;
            }
            $items[$index]['variants'] = $variants;
            continue;
        }
        $key = (string) ($item['slug'] ?? '');
        if (array_key_exists($key, $saved)) {
            $items[$index]['pret'] = $saved[$key];
        }
    }

    return $items;
}

function product_save_prices(array $posted): string
{
    $values = [];
    foreach (product_price_rows() as $row) {
        $raw = trim((string) ($posted[product_price_field($row)] ?? ''));
        $parsed = product_parse_price($raw);
        if ($parsed === 'invalid') {
            return 'Scrie prețul în lei, de exemplu 12,50, sau lasă câmpul gol.';
        }
        $values[] = [$row['slug'], $row['optiune'], $parsed];
    }

    try {
        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO preturi (slug, optiune, pret) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE pret = ?'
        );
        foreach ($values as $value) {
            $stmt->execute([$value[0], $value[1], $value[2], $value[2]]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return db_failed()
            ? 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.'
            : 'Prețurile nu au putut fi salvate.';
    }

    return '';
}

function product_catalog_items(): array
{
    return [
    [
        'slug' => 'cialde',
        'title' => 'Cialde',
        'kicker' => 'Monodoze',
        'meta' => 'Import Italia',
        'image' => 'assets/img/Marfa/cialda.png',
        'offer_image' => 'assets/img/Marfa/cialde.png',
        'alt' => 'Cialde Casa Bradu Caffè',
        'short' => 'Cialde în monodoze: Deka și Intenso.',
        'long' => 'Cialdele Casa Bradu păstrează aroma amestecului nostru, fără râșniță și fără cântar. Alegi sortimentul — Deka sau Intenso — și ambalarea (bucăți sau cutii).',
        'points' => [
            'Monodoze practice, fără râșniță',
            'Două sortimente: Deka și Intenso',
            'Livrare în toată Republica Moldova',
        ],
        'choice' => 'Sortiment',
        'options' => [
            ['key' => 'deka', 'label' => 'Deka'],
            ['key' => 'intenso', 'label' => 'Intenso'],
        ],
        'pack_choice' => 'Ambalare',
        'packs' => [
            ['key' => 'bucata', 'label' => 'Bucăți'],
            ['key' => 'cutie', 'label' => 'Cutii'],
        ],
    ],
    [
        'slug' => 'capsule',
        'title' => 'Capsule',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/Marfa/niciola.png',
        'offer_image' => 'assets/img/Marfa/caps.png',
        'alt' => 'Capsule Niciola Casa Bradu Caffè',
        'short' => 'Capsule compatibile Nespresso: Niciola, Ginseng, Tramonto și Intenso.',
        'long' => 'Capsulele Casa Bradu sunt compatibile cu aparatele Nespresso. Alegi sortimentul — Niciola, Ginseng, Tramonto sau Intenso — și ambalarea (bucăți sau cutii).',
        'points' => [
            'Compatibile Nespresso',
            'Patru sortimente: Niciola, Ginseng, Tramonto, Intenso',
            'Potrivite pentru acasă și pentru birou',
        ],
        'choice' => 'Sortiment',
        'options' => [
            ['key' => 'niciola', 'label' => 'Niciola'],
            ['key' => 'ginseng', 'label' => 'Ginseng'],
            ['key' => 'tramonto', 'label' => 'Tramonto'],
            ['key' => 'intenso', 'label' => 'Intenso'],
        ],
        'pack_choice' => 'Ambalare',
        'packs' => [
            ['key' => 'bucata', 'label' => 'Bucăți'],
            ['key' => 'cutie', 'label' => 'Cutii'],
        ],
    ],
    [
        'slug' => 'boabe',
        'title' => 'Cafea boabe',
        'kicker' => 'Pentru râșniță',
        'meta' => '500 g',
        'image' => 'assets/img/Marfa/boabe.png',
        'offer_image' => 'assets/img/Marfa/boabe 500 gr.png',
        'alt' => 'Cafea boabe Casa Bradu Caffè, 500 g',
        'short' => 'Boabe întregi, pachet de 500 g: Intenso și Delicato.',
        'long' => 'Cafeaua boabe Casa Bradu este amestecul nostru în forma lui întreagă, în pachet de 500 g. Alegi sortimentul — Intenso sau Delicato — și o macini când o prepari, ca aroma să rămână deschisă. Raportul dintre Arabica și Robusta este ideal: finețe, corp și cremă.',
        'points' => [
            'Două sortimente: Intenso și Delicato',
            'Pachet de 500 g',
            'Pentru espresso, moka sau filtru',
        ],
        'choice' => 'Sortiment',
        'options' => [
            [
                'key' => 'intenso',
                'label' => 'Intenso',
                'image' => 'assets/img/Marfa/boabe.png',
                'offer_image' => 'assets/img/Marfa/boabe 500 gr.png',
            ],
            [
                'key' => 'delicato',
                'label' => 'Delicato',
                'image' => 'assets/img/Marfa/delicato.png',
                'offer_image' => 'assets/img/Marfa/delicato.png',
            ],
        ],
    ],
    [
        'slug' => 'pahare',
        'title' => 'Pahare de unică folosință',
        'kicker' => 'Pentru cafea',
        'meta' => 'Consumabile',
        'image' => 'assets/img/produs-pahare.jpg',
        'alt' => 'Pahare de unică folosință pentru cafea',
        'short' => 'Pahare de unică folosință: 100 ml și 166 ml.',
        'long' => 'Pahare de unică folosință pentru cafea Casa Bradu: practice pentru birou, pentru meeting-uri și pentru cine servește cafea în fiecare zi. Alegi volumul — 100 ml sau 166 ml — și le comanzi împreună cu cialdele, capsulele sau boabele.',
        'points' => [
            'Două volume: 100 ml și 166 ml',
            'Pentru cafea la birou și evenimente',
            'Comandă împreună cu cafeaua',
        ],
        'choice' => 'Asortiment',
        'options' => [
            ['key' => 'ml100', 'label' => '100 ml'],
            ['key' => 'ml166', 'label' => '166 ml'],
        ],
    ],
    [
        'slug' => 'betisoare',
        'title' => 'Bețișoare',
        'kicker' => 'Pentru amestecat',
        'meta' => 'Consumabile',
        'image' => 'assets/img/produs-betisoare.jpg',
        'alt' => 'Bețișoare pentru cafea',
        'short' => 'Bețișoare pentru amestecat cafeaua, la birou sau unde servești oaspeții.',
        'long' => 'Bețișoare pentru cafea Casa Bradu: simple, practice, potrivite lângă paharele de unică folosință. Le adaugi în comandă împreună cu cafeaua și cu paharele.',
        'points' => [
            'Pentru amestecat cafeaua',
            'Potrivite lângă pahare',
            'Livrare în toată Republica Moldova',
        ],
        'pret' => null,
    ],
    [
        'slug' => 'aparate',
        'title' => 'Aparate de cafea',
        'kicker' => 'Comodat și vânzare',
        'meta' => 'Comodat și vânzare',
        'image' => 'assets/img/aparat-cafea.jpg',
        'alt' => 'Aparat compact de cafea, cu o ceașcă de espresso',
        'short' => 'Oferim aparate în comodat și vindem aparate noi, pentru cialde, capsule și boabe.',
        'long' => 'Oferim aparate de cafea în comodat și vindem aparate noi — pentru cialde, pentru capsule și pentru boabe. Le potrivim cu aroma cafelei Casa Bradu, acasă sau la birou, și livrăm în toată Republica Moldova.',
        'points' => [
            'Aparate în comodat',
            'Aparate noi la vânzare',
            'Pentru cialde, capsule și boabe',
        ],
        'choice' => 'Tip',
        'options' => [
            ['key' => 'comodat', 'label' => 'În comodat'],
            ['key' => 'noi', 'label' => 'Aparate noi'],
        ],
    ],
    ];
}

return product_catalog();
