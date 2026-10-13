<?php

declare(strict_types=1);

function product_catalog(): array
{
    return product_catalog_items();
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
