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
        'image' => 'assets/img/produs-cialde.jpg',
        'alt' => 'Stivă de cialde de cafea, cu câteva boabe alături',
        'short' => 'Cafea porționată în cialde, pentru un espresso constant, ceașcă după ceașcă.',
        'long' => 'Cialdele Casa Bradu sunt pentru cine vrea gustul cafelei artizanale fără râșniță și fără cântar. Fiecare porție păstrează amestecul nostru: un gust aromat și fin, în care Arabica și Robusta stau într-un raport echilibrat.',
        'points' => [
            'Porție egală, de fiecare dată',
            'Preparare rapidă, la aparat de cialde',
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
        'short' => 'Capsule cu același amestec artizanal, gata pentru espressorul de capsule.',
        'long' => 'Capsulele Casa Bradu aduc cafeaua din Italia într-un format curat și rapid. Le alegi pentru acasă sau pentru birou, când vrei espresso fără dozare, cu aroma și corpul amestecului nostru.',
        'points' => [
            'Espresso curat, fără râșniță',
            'Potrivite pentru acasă și pentru birou',
            'Gust aromat, cu corp echilibrat',
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
        'short' => 'Boabe întregi, de măcinat înainte de preparare, pentru espresso, moka sau filtru.',
        'long' => 'Cafeaua boabe Casa Bradu este amestecul nostru în forma lui întreagă. O macini când o prepari, ca aroma să rămână deschisă. Arabica aduce finețea, Robusta ține corpul și crema.',
        'points' => [
            'Măcinare proaspătă, înainte de ceașcă',
            'Pentru espresso, moka sau filtru',
            'Raport echilibrat între Arabica și Robusta',
        ],
        'choice' => 'Cantitate',
        'options' => [
            ['key' => '250', 'label' => '250 g', 'pret' => null],
            ['key' => '500', 'label' => '500 g', 'pret' => null],
            ['key' => '1000', 'label' => '1 kg', 'pret' => null],
        ],
    ],
    [
        'slug' => 'aparate',
        'title' => 'Aparate de cafea',
        'kicker' => 'Cialde și capsule',
        'meta' => 'Import',
        'image' => 'assets/img/aparat-cafea.jpg',
        'alt' => 'Aparat compact de cafea, cu o ceașcă de espresso',
        'short' => 'Aparate pentru cialde și aparate pentru capsule, alese împreună cu cafeaua.',
        'long' => 'Importăm aparate de cafea pentru cialde și pentru capsule, ca prepararea să țină pasul cu cafeaua. Spune-ne unde o folosești, acasă, la birou sau în serviciu, și îți propunem varianta potrivită.',
        'points' => [
            'Modele pentru cialde',
            'Modele pentru capsule',
            'Le potrivim cu cafeaua Casa Bradu',
        ],
    ],
    ];
}

return product_catalog();
