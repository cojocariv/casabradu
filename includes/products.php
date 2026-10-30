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
                'hover_image' => (string) ($option['hover_image'] ?? ''),
                'offer_image' => (string) ($option['offer_image'] ?? ''),
                'pret' => array_key_exists('pret', $option) ? $option['pret'] : null,
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
    if (!is_numeric($pret) || (float) $pret < 0) {
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
    if ($value < 0 || $value > 999999.99) {
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
                if (array_key_exists($key, $saved) && $saved[$key] !== null) {
                    $def['pret'] = $saved[$key];
                } elseif (!array_key_exists('pret', $def)) {
                    $def['pret'] = null;
                }
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
        'slug' => 'boabe-intenso',
        'cat' => 'boabe',
        'title' => 'Boabe Intenso',
        'kicker' => 'Pentru râșniță',
        'meta' => '500 g',
        'image' => 'assets/img/Marfa/boabe.png',
        'hover_image' => 'assets/img/Marfa/boabe 500 gr.png',
        'offer_image' => 'assets/img/Marfa/boabe 500 gr.png',
        'alt' => 'Cafea boabe Intenso Casa Bradu Caffè, 500 g',
        'short' => 'Boabe Intenso, 500 g: corpos, cremă densă — macini când prepari.',
        'long' => 'Boabele Intenso Casa Bradu, 500 g: amestec puternic și corpos. Le macini când le prepari, ca aroma să rămână deschisă.',
        'details' => [
            'Intenso: amestec puternic și corpos, cu note de cacao amărui, alună prăjită și final de condimente calde. Cremă densă, gust decis — espresso „care se simte”, ideal dimineața sau ca bază pentru cappuccino.',
        ],
        'points' => [
            'Pachet 500 g',
            'Corpos, cremă densă',
            'Pentru râșniță',
        ],
        'pret' => null,
    ],
    [
        'slug' => 'boabe-delicato',
        'cat' => 'boabe',
        'title' => 'Boabe Delicato',
        'kicker' => 'Pentru râșniță',
        'meta' => '500 g',
        'image' => 'assets/img/Marfa/delicato.png',
        'hover_image' => 'assets/img/Marfa/delicato2.png',
        'offer_image' => 'assets/img/Marfa/delicato2.png',
        'alt' => 'Cafea boabe Delicato Casa Bradu Caffè, 500 g',
        'short' => 'Boabe Delicato, 500 g: moale, aromatic — macini când prepari.',
        'long' => 'Boabele Delicato Casa Bradu, 500 g: profil moale și armonios. Le macini când le prepari, ca aroma să rămână deschisă.',
        'details' => [
            'Delicato: profil moale și armonios, cu note florale și de fructe uscate, aciditate blândă. Aromatic, rotund, potrivit pe tot parcursul zilei — pentru cine bea mai multe cești fără asprime.',
        ],
        'points' => [
            'Pachet 500 g',
            'Moale, aromatic',
            'Pentru râșniță',
        ],
        'pret' => null,
    ],
    [
        'slug' => 'cialde-deka',
        'cat' => 'cialde',
        'title' => 'Cialde Deka',
        'kicker' => 'Monodoze · Decafeinizat',
        'meta' => 'Import Italia',
        'image' => 'assets/img/Marfa/cialda_deka.png',
        'hover_image' => 'assets/img/Marfa/cialde_deka.jpg',
        'offer_image' => 'assets/img/Marfa/cialda_deka.png',
        'alt' => 'Cialde Deka Casa Bradu Caffè, decafeinizat',
        'short' => 'Monodoze ESE decafeinizate: corp și aromă, fără stimulent — espresso seral, ceaşcă cu ceaşcă.',
        'long' => 'Cialdele Deka Casa Bradu sunt monodoze ESE din Italia, decafeinizate, cu corp și aromă păstrate. Fără râșniță, fără cântar. Alegi ambalarea: bucăți sau cutii.',
        'details' => [
            'Deka (decafeinizat): espresso rotund și catifelat, cu note moi de cacao și fructe uscate, cremă fină și aciditate joasă. Gustul rămâne prezent, fără stimulent — ideal seara sau când vrei cafea fără cafeină.',
        ],
        'points' => [
            'Monodoze ESE, fără râșniță',
            'Decafeinizat, aromă păstrată',
            'Note de cacao și fructe uscate',
        ],
        'choice' => 'Ambalare',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți'],
            ['key' => 'cutie', 'label' => 'Cutii'],
        ],
    ],
    [
        'slug' => 'cialde-intenso',
        'cat' => 'cialde',
        'title' => 'Cialde Intenso',
        'kicker' => 'Monodoze',
        'meta' => 'Import Italia',
        'image' => 'assets/img/Marfa/cialda.png',
        'hover_image' => 'assets/img/Marfa/cialde.png',
        'offer_image' => 'assets/img/Marfa/cialde.png',
        'alt' => 'Cialde Intenso Casa Bradu Caffè',
        'short' => 'Monodoze ESE Intenso: espresso corpos, cremă densă — fără râșniță, ceaşcă cu ceaşcă.',
        'long' => 'Cialdele Intenso Casa Bradu sunt monodoze ESE din Italia: amestec puternic și corpos, cu aromă intensă și cremă densă. Alegi ambalarea: bucăți sau cutii.',
        'details' => [
            'Intenso: amestec puternic și corpos, cu aromă intensă, cremă densă și final persistent. Espresso italian clasic, pentru cine vrea caracter în ceașcă, dimineața sau după masă.',
        ],
        'points' => [
            'Monodoze ESE, fără râșniță',
            'Corpos, cremă densă',
            'Gust italian clasic',
        ],
        'choice' => 'Ambalare',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți'],
            ['key' => 'cutie', 'label' => 'Cutii'],
        ],
    ],
    [
        'slug' => 'capsule-nociola',
        'cat' => 'capsule',
        'title' => 'Capsule Nociola',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/Marfa/niciola.png',
        'hover_image' => 'assets/img/Marfa/caps.png',
        'offer_image' => 'assets/img/Marfa/caps.png',
        'alt' => 'Capsule Nociola Casa Bradu Caffè',
        'short' => 'Capsule Nociola: espresso cu alună prăjită — moale, cald, învăluitor.',
        'long' => 'Capsulele Nociola Casa Bradu, compatibile Nespresso: espresso aromatizat cu alună prăjită. Alegi ambalarea: bucăți sau cutii.',
        'details' => [
            'Nociola: espresso aromatizat cu alună prăjită — moale, cald, învăluitor. Corpul cafelei întâlnește dulceața nociola, pentru o ceașcă rotundă, fără amărăciune aspră.',
        ],
        'points' => [
            'Compatibile Nespresso',
            'Aromă de alună prăjită',
            'Ceașcă rotundă, învăluitoare',
        ],
        'choice' => 'Ambalare',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți'],
            ['key' => 'cutie', 'label' => 'Cutii'],
        ],
    ],
    [
        'slug' => 'capsule-ginseng',
        'cat' => 'capsule',
        'title' => 'Capsule Ginseng',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/Marfa/ginseng.jpg',
        'hover_image' => 'assets/img/Marfa/ginseng_pac.jpg',
        'offer_image' => 'assets/img/Marfa/ginseng.jpg',
        'alt' => 'Capsule Ginseng Casa Bradu Caffè',
        'short' => 'Capsule Ginseng: cafea decisă cu note dulci de ginseng și caramel.',
        'long' => 'Capsulele Ginseng Casa Bradu, compatibile Nespresso: gust decis de cafea, împletit cu dulceața ginsengului. Alegi ambalarea: bucăți sau cutii.',
        'details' => [
            'Ginseng: gust decis de cafea, împletit cu dulceața revitalizantă a ginsengului. Note moi, caramelate, o pauză energizantă — dimineața sau după-amiaza.',
        ],
        'points' => [
            'Compatibile Nespresso',
            'Note de ginseng și caramel',
            'Pauză energizantă',
        ],
        'choice' => 'Ambalare',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți'],
            ['key' => 'cutie', 'label' => 'Cutii'],
        ],
    ],
    [
        'slug' => 'capsule-tramonto',
        'cat' => 'capsule',
        'title' => 'Capsule Tramonto',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/Marfa/niciola.png',
        'hover_image' => 'assets/img/Marfa/caps.png',
        'offer_image' => 'assets/img/Marfa/caps.png',
        'alt' => 'Capsule Tramonto Casa Bradu Caffè',
        'short' => 'Capsule Tramonto: echilibru dulce-corpos, note de ciocolată și vanilie.',
        'long' => 'Capsulele Tramonto Casa Bradu, compatibile Nespresso: amestec echilibrat, cu note de ciocolată și final de vanilie. Alegi ambalarea: bucăți sau cutii.',
        'details' => [
            'Tramonto: amestec echilibrat (dulce și corpos), cu note de ciocolată, final de vanilie și aciditate joasă. Cafeaua „la apus” — potrivită oricând, mai fină decât Intenso.',
        ],
        'points' => [
            'Compatibile Nespresso',
            'Note de ciocolată și vanilie',
            'Echilibru dulce-corpos',
        ],
        'choice' => 'Ambalare',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți'],
            ['key' => 'cutie', 'label' => 'Cutii'],
        ],
    ],
    [
        'slug' => 'capsule-intenso',
        'cat' => 'capsule',
        'title' => 'Capsule Intenso',
        'kicker' => 'Compatibile Nespresso',
        'meta' => 'Import Italia',
        'image' => 'assets/img/Marfa/niciola.png',
        'hover_image' => 'assets/img/Marfa/caps.png',
        'offer_image' => 'assets/img/Marfa/caps.png',
        'alt' => 'Capsule Intenso Casa Bradu Caffè',
        'short' => 'Capsule Intenso: espresso puternic, cremă densă, caracter italian clasic.',
        'long' => 'Capsulele Intenso Casa Bradu, compatibile Nespresso: espresso puternic și corpos, cu aromă intensă și cremă densă. Alegi ambalarea: bucăți sau cutii.',
        'details' => [
            'Intenso: espresso puternic și corpos, cu aromă intensă, cremă densă și note de cacao și fructe uscate. Caracter italian clasic, persistent până la ultima înghițitură.',
        ],
        'points' => [
            'Compatibile Nespresso',
            'Corpos, cremă densă',
            'Gust italian clasic',
        ],
        'choice' => 'Ambalare',
        'options' => [
            ['key' => 'bucata', 'label' => 'Bucăți'],
            ['key' => 'cutie', 'label' => 'Cutii'],
        ],
    ],
    [
        'slug' => 'pahare',
        'cat' => 'pahare',
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
        'cat' => 'betisoare',
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
        'slug' => 'plus-silver',
        'kind' => 'aparat',
        'title' => 'Aroma Plus Silver',
        'kicker' => 'Pentru cialde',
        'meta' => 'Comodat și vânzare',
        'image' => 'assets/img/Aparate/PLUS-silver_tre-quarti.webp',
        'offer_image' => 'assets/img/Aparate/PLUS-silver_tre-quarti.webp',
        'alt' => 'Aparat de cafea Aroma Plus Silver, pentru cialde',
        'short' => 'Mașină pe cialde din oțel, 26 cm: rezervor din sticlă și performanță profesională.',
        'long' => 'Aroma Plus se îmbracă în oțel, alternând suprafețe lucioase și satinat, și transformă aparatul într-un adevărat obiect de design.',
        'details' => [
            'Aroma Plus se îmbracă în oțel, alternând suprafețe lucioase și satinat, și transformă aparatul într-un adevărat obiect de design.',
            'Plus Silver este echipată cu rezervor din sticlă, grup de extragere standard brevetat, presă pentru cialde din alamă reglabilă și portafiltru detașabil.',
            'Tehnologie brevetată, performanțe profesionale și extragere superioară — acestea sunt caracteristicile Aroma Plus. O adevărată mașină de cafea pe cialde de top, de doar 26 cm înălțime, robustă și durabilă datorită materialelor de înaltă calitate precum oțelul. Aroma Plus este eficientă și economisește spațiu, potrivită pentru orice casă și pentru toți iubitorii de espresso italian autentic.',
        ],
        'points' => [
            'Carcasă din oțel, design lucios și satinat',
            'Rezervor din sticlă, grup brevetat',
            'Doar 26 cm înălțime',
        ],
        'choice' => 'Tip',
        'options' => [
            ['key' => 'comodat', 'label' => 'În comodat', 'pret' => 0],
            ['key' => 'noi', 'label' => 'Aparate la vânzare'],
        ],
    ],
    [
        'slug' => 'plus-mono',
        'kind' => 'aparat',
        'title' => 'Aroma Plus Mono',
        'kicker' => 'Pentru cialde',
        'meta' => 'Comodat și vânzare',
        'image' => 'assets/img/Aparate/PLUSMono2.webp',
        'offer_image' => 'assets/img/Aparate/PLUSMono2.webp',
        'alt' => 'Aparat de cafea Aroma Plus Mono, pentru cialde',
        'short' => 'Mașină pe cialde minimală, Black sau White, 26 cm, cu detalii din oțel.',
        'long' => 'Plus Mono este mașina noastră minimală și elegantă, disponibilă în versiunile total Black sau White.',
        'details' => [
            'Plus Mono este mașina noastră minimală și elegantă, disponibilă în versiunile total Black sau White; ambele poartă detalii prețioase din oțel sau colorate, pentru un joc unic de lumini și volume. Plus Mono este echipată cu rezervor din sticlă, grup de extragere standard brevetat, presă pentru cialde din alamă reglabilă și portafiltru detașabil.',
            'Tehnologie brevetată, performanțe profesionale și extragere superioară — acestea sunt caracteristicile Aroma Plus Mono. O adevărată mașină de cafea pe cialde de top, de doar 26 cm înălțime, robustă și durabilă datorită materialelor de înaltă calitate precum oțelul. Aroma Plus este eficientă și economisește spațiu, potrivită pentru orice casă și pentru toți iubitorii de espresso italian autentic.',
        ],
        'points' => [
            'Versiuni Black sau White',
            'Rezervor din sticlă, grup brevetat',
            'Doar 26 cm înălțime',
        ],
        'choice' => 'Tip',
        'options' => [
            ['key' => 'comodat', 'label' => 'În comodat', 'pret' => 0],
            ['key' => 'noi', 'label' => 'Aparate la vânzare', 'pret' => 4999],
        ],
    ],
    [
        'slug' => 'plus-plus',
        'kind' => 'aparat',
        'title' => 'Aroma Plus+',
        'kicker' => 'Pentru cialde',
        'meta' => 'Comodat și vânzare',
        'image' => 'assets/img/Aparate/plus-tortora.webp',
        'offer_image' => 'assets/img/Aparate/plus-tortora.webp',
        'alt' => 'Aparat de cafea Aroma Plus+, pentru cialde',
        'short' => 'Plus+ redesenată: față nouă, buton elegant și înălțime mărită cu 1 cm.',
        'long' => 'Noua Plus+ reprezintă un salt calitativ în design și în funcționalități.',
        'details' => [
            'Noua Plus+ reprezintă un salt calitativ în design și în funcționalități. Noile caracteristici ale aparatului includ: față nouă, buton elegant nou și înălțime mărită cu 1 cm.',
            'Datorită noii dimensiuni, puteți alege să folosiți atât ceștile clasice, cât și paharele din sticlă termică Aroma, ideale pentru ceaiuri și tisane în cialde. Tehnologie brevetată, performanțe profesionale și extragere superioară — acestea sunt caracteristicile Aroma Plus+. Aparatul este robust și durabil datorită materialelor de înaltă calitate precum oțelul. O adevărată mașină de cafea pe cialde de top. Aroma Plus este eficientă și economisește spațiu, potrivită pentru orice casă și pentru toți iubitorii de espresso italian autentic.',
        ],
        'points' => [
            'Față nouă și buton elegant',
            'Înălțime mărită cu 1 cm',
            'Compatibilă cu pahare termice Aroma',
        ],
        'choice' => 'Tip',
        'options' => [
            ['key' => 'comodat', 'label' => 'În comodat', 'pret' => 0],
            ['key' => 'noi', 'label' => 'Aparate la vânzare', 'pret' => 4999],
        ],
    ],
    [
        'slug' => 'plus-inferno',
        'kind' => 'aparat',
        'title' => 'Aroma Plus Inferno',
        'kicker' => 'Pentru cialde',
        'meta' => 'Comodat și vânzare',
        'image' => 'assets/img/Aparate/plus-inferno.webp',
        'offer_image' => 'assets/img/Aparate/plus-inferno.webp',
        'alt' => 'Aparat de cafea Aroma Plus Inferno, pentru cialde',
        'short' => 'Ediție limitată roșie, best seller 2022, cu termostat special la 95°.',
        'long' => 'Mașina pe cialde best seller în 2022, Plus Inferno se îmbracă în roșu și are un termostat special la 95°.',
        'details' => [
            'Mașina pe cialde best seller în 2022, Plus Inferno se îmbracă în roșu și are un termostat special la 95°. O ediție limitată dedicată Infernului dantesc.',
            'Plus Inferno este echipată cu rezervor din sticlă. Tehnologie brevetată, performanțe profesionale și extragere superioară — acestea sunt caracteristicile Aroma Plus Inferno. O adevărată mașină de cafea pe cialde de top, de doar 26 cm înălțime, robustă și durabilă datorită materialelor de înaltă calitate precum oțelul. Aroma Plus este eficientă și economisește spațiu, potrivită pentru orice casă și pentru toți iubitorii de espresso italian autentic.',
        ],
        'points' => [
            'Ediție limitată roșie',
            'Termostat special 95°',
            'Doar 26 cm înălțime',
        ],
        'choice' => 'Tip',
        'options' => [
            ['key' => 'comodat', 'label' => 'În comodat', 'pret' => 0],
            ['key' => 'noi', 'label' => 'Aparate la vânzare', 'pret' => 4999],
        ],
    ],
    [
        'slug' => 'iconica',
        'kind' => 'aparat',
        'title' => 'Aroma Iconica',
        'kicker' => 'Pentru cialde',
        'meta' => 'Comodat și vânzare',
        'image' => 'assets/img/Aparate/iconica.webp',
        'offer_image' => 'assets/img/Aparate/iconica2.webp',
        'images' => [
            'assets/img/Aparate/iconica.webp',
            'assets/img/Aparate/iconica2.webp',
            'assets/img/Aparate/iconica3.webp',
        ],
        'alt' => 'Aparat de cafea Aroma Iconica, pentru cialde',
        'short' => 'Design italian Iconica: tradiție napolitană și espresso pe cialde, cu rezervor din sticlă de 1 l.',
        'long' => 'Iconica: tradiția napolitană a artei cafelei întâlnește viitorul espresso-ului pe cialde.',
        'details' => [
            'Un cuvânt care, mai mult decât oricare altul, reprezintă designul și meșteșugul italian care definesc acest aparat este Iconica. Cuvântul este emblema a ceea ce reprezintă aparatul: tradiția napolitană a artei cafelei care întâlnește viitorul espresso-ului pe cialde. Italia Dolce Vita a fost muza noastră, pe care o regăsim în liniile moi și sinuoase ale mașinii.',
            'Noutate: descoperă noul spate al aparatului, acum fără ușă și cu un rezervor din sticlă de 1 litru, pentru curățare și scoatere ușoară.',
        ],
        'points' => [
            'Design italian, linii moi',
            'Rezervor din sticlă 1 l',
            'Spate fără ușă, curățare ușoară',
        ],
        'choice' => 'Tip',
        'options' => [
            ['key' => 'comodat', 'label' => 'În comodat', 'pret' => 0],
            ['key' => 'noi', 'label' => 'Aparate la vânzare', 'pret' => 7999],
        ],
    ],
    [
        'slug' => 'kicco-mogano',
        'kind' => 'aparat',
        'title' => 'Aroma Kicco Industrial Mogano',
        'kicker' => 'Pentru cialde',
        'meta' => 'Comodat și vânzare',
        'image' => 'assets/img/Aparate/KICCO-mogano_tre-quarti.webp',
        'offer_image' => 'assets/img/Aparate/KICCO-mogano_tre-quarti2.webp',
        'images' => [
            'assets/img/Aparate/KICCO-mogano_tre-quarti.webp',
            'assets/img/Aparate/KICCO-mogano_tre-quarti2.webp',
            'assets/img/Aparate/KICCO-mogano_tre-quarti3.webp',
        ],
        'alt' => 'Aparat de cafea Aroma Kicco Industrial Mogano, pentru cialde',
        'short' => 'Kicco Industrial Mogano: panouri din lemn lucrat manual și linii din oțel, design industrial.',
        'long' => 'Pentru iubitorii de design mai agresiv și industrial, Kicco Industrial este colecția ideală.',
        'details' => [
            'Pentru iubitorii de design mai agresiv și industrial, Kicco Industrial este colecția ideală. Soliditatea aparatelor Aroma și stilul inovator fac din Kicco Industrial un obiect de neratat pentru trendsetteri.',
            'Colecția Kicco Industrial este decorată cu panouri din lemn lucrat și șlefuit manual, ornamente cu linii sinuoase din oțel și șuruburi la vedere, și este disponibilă în cireș, mahon și arțar. Mașina pe cialde Kicco are un design original și iconic: nu este doar un aparat de cafea pe cialde, ci un adevărat element de mobilier.',
            'Kicco garantează o extragere excelentă datorită grupului standard brevetat, portafiltrului detașabil și presei pentru cialde din alamă reglabilă.',
        ],
        'points' => [
            'Panouri din lemn mahon, lucrate manual',
            'Linii din oțel cu șuruburi la vedere',
            'Grup brevetat, extragere profesională',
        ],
        'choice' => 'Tip',
        'options' => [
            ['key' => 'comodat', 'label' => 'În comodat', 'pret' => 0],
            ['key' => 'noi', 'label' => 'Aparate la vânzare', 'pret' => 4999],
        ],
    ],
    [
        'slug' => 'capucinator-negru',
        'kind' => 'aparat',
        'title' => 'Capucinator Aroma Negru',
        'kicker' => 'Spumă de lapte',
        'meta' => 'La vânzare',
        'image' => 'assets/img/Aparate/capucinator-negru.webp',
        'offer_image' => 'assets/img/Aparate/capucinator-negru.webp',
        'alt' => 'Capucinator Aroma negru, pentru spumă de lapte',
        'short' => 'Capucinator electric Aroma, carcasă neagră: spumă densă sau aerată, lapte cald sau spumă rece.',
        'long' => 'Capucinator Aroma Coffee Machines, variantă neagră, cu carafă din sticlă și panou tactil.',
        'details' => [
            'Capucinator electric Aroma Coffee Machines, în variantă neagră. Carafa din sticlă (până la 500 ml pentru încălzire, 250 ml pentru spumă) stă pe baza motorizată cu panou tactil.',
            'Programe pentru spumă densă, spumă aerată, lapte cald și spumă rece — potrivite pentru cappuccino, latte și băuturi pe bază de lapte, acasă sau la birou.',
        ],
        'points' => [
            'Carcasă neagră, carafă din sticlă',
            'Spumă caldă densă sau aerată',
            'Lapte cald și spumă rece',
        ],
        'pret' => 1800,
    ],
    [
        'slug' => 'capucinator-alb',
        'kind' => 'aparat',
        'title' => 'Capucinator Aroma Alb',
        'kicker' => 'Spumă de lapte',
        'meta' => 'La vânzare',
        'image' => 'assets/img/Aparate/capucinator-alb.webp',
        'offer_image' => 'assets/img/Aparate/capucinator-alb.webp',
        'alt' => 'Capucinator Aroma alb, pentru spumă de lapte',
        'short' => 'Capucinator electric Aroma, carcasă albă: spumă densă sau aerată, lapte cald sau spumă rece.',
        'long' => 'Capucinator Aroma Coffee Machines, variantă albă, cu carafă din sticlă și panou tactil.',
        'details' => [
            'Capucinator electric Aroma Coffee Machines, în variantă albă. Carafa din sticlă (până la 500 ml pentru încălzire, 250 ml pentru spumă) stă pe baza motorizată cu panou tactil.',
            'Programe pentru spumă densă, spumă aerată, lapte cald și spumă rece — potrivite pentru cappuccino, latte și băuturi pe bază de lapte, acasă sau la birou.',
        ],
        'points' => [
            'Carcasă albă, carafă din sticlă',
            'Spumă caldă densă sau aerată',
            'Lapte cald și spumă rece',
        ],
        'pret' => 1800,
    ],
    ];
}

return product_catalog();
