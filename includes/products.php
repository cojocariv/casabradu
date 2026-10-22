<?php

declare(strict_types=1);

function product_catalog(): array
{
    return product_apply_prices(product_catalog_items());
}

function product_price_rows(): array
{
    $rows = [];
    foreach (product_catalog_items() as $item) {
        $options = $item['options'] ?? [];
        if (is_array($options) && $options !== []) {
            foreach ($options as $option) {
                if (!is_array($option)) {
                    continue;
                }
                $rows[] = [
                    'slug' => (string) $item['slug'],
                    'optiune' => (string) ($option['key'] ?? ''),
                    'produs' => (string) $item['title'],
                    'varianta' => (string) ($option['label'] ?? ''),
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
        $options = $item['options'] ?? [];
        if (is_array($options) && $options !== []) {
            foreach ($options as $optionIndex => $option) {
                if (!is_array($option)) {
                    continue;
                }
                $key = (string) $item['slug'] . ':' . (string) ($option['key'] ?? '');
                if (array_key_exists($key, $saved)) {
                    $items[$index]['options'][$optionIndex]['pret'] = $saved[$key];
                }
            }
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
        'image' => 'assets/img/produs-cialde.jpg?v=2',
        'alt' => 'Cialdă de cafea Casa Bradu Caffè',
        'short' => 'Porții de cafea cu aromă și raportul ideal dintre Arabica și Robusta.',
        'long' => 'Cialdele Casa Bradu păstrează aroma amestecului nostru, fără râșniță și fără cântar. Arabica și Robusta stau într-un raport ideal: finețe și dulceață, alături de corp și cremă.',
        'points' => [
            'Aromă deschisă, porție după porție',
            'Raport ideal între Arabica și Robusta',
            'Același amestec ca în capsule și boabe',
        ],
        'choice' => 'Bucăți sau cutii',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți', 'pret' => null],
            ['key' => 'cutie', 'label' => 'Cutii', 'pret' => null],
        ],
    ],
    [
        'slug' => 'capsule',
        'title' => 'Capsule',
        'kicker' => 'Pentru acasă și birou',
        'meta' => 'Import Italia',
        'image' => 'assets/img/produs-capsule.jpg',
        'alt' => 'Capsule de cafea aurii și brune, așezate pe in',
        'short' => 'Același amestec, în capsule: aromă deschisă, Arabica și Robusta în raport ideal.',
        'long' => 'Capsulele Casa Bradu aduc din Italia aroma cafelei noastre, curat și rapid. Arabica deschide gustul, Robusta îi dă corp. Raportul dintre ele rămâne ideal, acasă sau la birou.',
        'points' => [
            'Aroma amestecului, fără râșniță',
            'Raport ideal între Arabica și Robusta',
            'Potrivite pentru acasă și pentru birou',
        ],
        'choice' => 'Bucăți sau cutii',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți', 'pret' => null],
            ['key' => 'cutie', 'label' => 'Cutii', 'pret' => null],
        ],
    ],
    [
        'slug' => 'boabe',
        'title' => 'Cafea boabe',
        'kicker' => 'Pentru râșniță',
        'meta' => 'Import Italia',
        'image' => 'assets/img/produs-boabe.jpg',
        'alt' => 'Boabe de cafea prăjite într-un bol ceramic',
        'short' => 'Boabe întregi, cu aroma amestecului: Arabica și Robusta în raport ideal.',
        'long' => 'Cafeaua boabe Casa Bradu este amestecul nostru în forma lui întreagă. O macini când o prepari, ca aroma să rămână deschisă. Raportul dintre Arabica și Robusta este ideal: finețe, corp și cremă.',
        'points' => [
            'Aromă proaspătă, măcinată înainte de ceașcă',
            'Pentru espresso, moka sau filtru',
            'Raport ideal între Arabica și Robusta',
        ],
        'choice' => 'Cantitate',
        'options' => [
            ['key' => '250', 'label' => '250 g', 'pret' => null],
            ['key' => '500', 'label' => '500 g', 'pret' => null],
            ['key' => '1000', 'label' => '1 kg', 'pret' => null],
        ],
    ],
    [
        'slug' => 'cialde-deka',
        'title' => 'Cialde Deka',
        'kicker' => 'Monodoze',
        'meta' => 'Import Italia',
        'image' => 'assets/img/produs-cialde.jpg?v=2',
        'alt' => 'Cialde Deka Casa Bradu Caffè',
        'short' => 'Cialde Deka: cafea decofeinizată, în monodoze, cu aromă blândă.',
        'long' => 'Cialdele Deka Casa Bradu sunt pentru cine vrea cafea fără cofeină, dar cu aromă. Porție după porție, fără râșniță, potrivite acasă sau la birou.',
        'points' => [
            'Cafea decofeinizată, în cialde',
            'Aromă blândă, porție după porție',
            'Livrare în toată Republica Moldova',
        ],
        'choice' => 'Bucăți sau cutii',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți', 'pret' => null],
            ['key' => 'cutie', 'label' => 'Cutii', 'pret' => null],
        ],
    ],
    [
        'slug' => 'cialde-intenso',
        'title' => 'Cialde Intenso',
        'kicker' => 'Monodoze',
        'meta' => 'Import Italia',
        'image' => 'assets/img/produs-cialde.jpg?v=2',
        'alt' => 'Cialde Intenso Casa Bradu Caffè',
        'short' => 'Cialde Intenso: corp plin și cremă densă, în monodoze.',
        'long' => 'Cialdele Intenso Casa Bradu aduc un espresso mai intens, cu corp și cremă. Pregătire rapidă, fără râșniță, pentru cine preferă un gust mai puternic.',
        'points' => [
            'Gust intens, cremă densă',
            'Monodoze practice',
            'Livrare în toată Republica Moldova',
        ],
        'choice' => 'Bucăți sau cutii',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți', 'pret' => null],
            ['key' => 'cutie', 'label' => 'Cutii', 'pret' => null],
        ],
    ],
    [
        'slug' => 'capsule-ginseng',
        'title' => 'Capsule Ginseng Nespresso',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/produs-capsule.jpg',
        'alt' => 'Capsule Ginseng compatibile Nespresso',
        'short' => 'Capsule Ginseng, compatibile Nespresso: cafea cu notă de ginseng.',
        'long' => 'Capsulele Ginseng Casa Bradu sunt compatibile cu aparatele Nespresso. Aduc o ceașcă aromată, cu notă de ginseng, rapid și fără râșniță.',
        'points' => [
            'Compatibile Nespresso',
            'Notă de ginseng',
            'Livrare în toată Republica Moldova',
        ],
        'choice' => 'Bucăți sau cutii',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți', 'pret' => null],
            ['key' => 'cutie', 'label' => 'Cutii', 'pret' => null],
        ],
    ],
    [
        'slug' => 'capsule-nocciliolino',
        'title' => 'Capsule Nocciliolino Nespresso',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/produs-capsule.jpg',
        'alt' => 'Capsule Nocciliolino compatibile Nespresso',
        'short' => 'Capsule Nocciliolino, compatibile Nespresso: aromă cu notă de alune.',
        'long' => 'Capsulele Nocciliolino Casa Bradu sunt compatibile cu aparatele Nespresso. O ceașcă dulce-aromă, cu notă de alune, pregătită rapid acasă sau la birou.',
        'points' => [
            'Compatibile Nespresso',
            'Notă de alune',
            'Livrare în toată Republica Moldova',
        ],
        'choice' => 'Bucăți sau cutii',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți', 'pret' => null],
            ['key' => 'cutie', 'label' => 'Cutii', 'pret' => null],
        ],
    ],
    [
        'slug' => 'capsule-tramonto',
        'title' => 'Capsule Tramonto Nespresso',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/produs-capsule.jpg',
        'alt' => 'Capsule Tramonto compatibile Nespresso',
        'short' => 'Capsule Tramonto, compatibile Nespresso: amestec echilibrat pentru espresso.',
        'long' => 'Capsulele Tramonto Casa Bradu sunt compatibile cu aparatele Nespresso. Un espresso echilibrat, cu aromă deschisă, pentru fiecare zi.',
        'points' => [
            'Compatibile Nespresso',
            'Amestec echilibrat',
            'Livrare în toată Republica Moldova',
        ],
        'choice' => 'Bucăți sau cutii',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți', 'pret' => null],
            ['key' => 'cutie', 'label' => 'Cutii', 'pret' => null],
        ],
    ],
    [
        'slug' => 'capsule-tramonto-grani',
        'title' => 'Capsule Tramonto Grani',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/produs-capsule.jpg',
        'alt' => 'Capsule Tramonto Grani Casa Bradu Caffè',
        'short' => 'Capsule Tramonto Grani: varianta cu profil mai intens din seria Tramonto.',
        'long' => 'Capsulele Tramonto Grani Casa Bradu completează seria Tramonto cu un profil mai intens. Compatibile cu aparatele Nespresso, practice pentru acasă și birou.',
        'points' => [
            'Compatibile Nespresso',
            'Profil Tramonto, variantă Grani',
            'Livrare în toată Republica Moldova',
        ],
        'choice' => 'Bucăți sau cutii',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți', 'pret' => null],
            ['key' => 'cutie', 'label' => 'Cutii', 'pret' => null],
        ],
    ],
    [
        'slug' => 'pahare',
        'title' => 'Pahare de unică folosință',
        'kicker' => 'Pentru cafea',
        'meta' => 'Consumabile',
        'image' => 'assets/img/produs-pahare.jpg',
        'alt' => 'Pahare de unică folosință pentru cafea',
        'short' => 'Pahare de unică folosință, potrivite pentru cafea la birou sau la evenimente.',
        'long' => 'Pahare de unică folosință pentru cafea Casa Bradu: practice pentru birou, pentru meeting-uri și pentru cine servește cafea în fiecare zi. Le comandă împreună cu cialdele, capsulele sau boabele.',
        'points' => [
            'Pentru cafea la birou și evenimente',
            'Comandă împreună cu cafeaua',
            'Livrare în toată Republica Moldova',
        ],
        'pret' => null,
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
        'kicker' => 'În comodat',
        'meta' => 'În comodat',
        'image' => 'assets/img/aparat-cafea.jpg',
        'alt' => 'Aparat compact de cafea, cu o ceașcă de espresso',
        'short' => 'Aparate în comodat, pentru cialde, capsule și boabe.',
        'long' => 'Oferim în comodat aparate de cafea pentru cialde, pentru capsule și pentru boabe. Le potrivim cu aroma cafelei Casa Bradu, acasă sau la birou, și livrăm în toată Republica Moldova.',
        'points' => [
            'Pentru cialde',
            'Pentru capsule',
            'Pentru boabe',
        ],
    ],
    ];
}

return product_catalog();
