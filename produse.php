<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = require __DIR__ . '/includes/products.php';
$page = 'produse';
$pageTitle = 'Cialde, capsule, boabe și aparate';
$pageDescription = 'Cialde, capsule, cafea boabe și aparate de cafea Casa Bradu Caffè, importate din Italia. Gust aromat și fin, cu Arabica și Robusta în echilibru.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Selecția</p>
        <h1>Cialde, capsule, boabe și aparate.</h1>
        <p class="lede">Patru feluri de a ajunge la același gust: aromat, fin, cu un raport echilibrat între Arabica și Robusta. Cafeaua vine din Italia. Aparatele, la fel, pentru cialde și pentru capsule.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="filters" aria-label="Filtrează produsele">
            <button class="filter is-active" type="button" data-filter="toate">Toate</button>
            <?php foreach ($products as $item): ?>
                <button class="filter" type="button" data-filter="<?= e($item['slug']) ?>"><?= e($item['title']) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="catalog">
            <?php foreach ($products as $item): ?>
                <article class="catalog-row reveal" id="<?= e($item['slug']) ?>" data-cat="<?= e($item['slug']) ?>">
                    <div class="catalog-photo">
                        <img src="<?= e(asset($item['image'])) ?>" alt="<?= e($item['alt']) ?>" width="900" height="900">
                    </div>
                    <div>
                        <p class="kicker"><?= e($item['kicker']) ?></p>
                        <h2><?= e($item['title']) ?></h2>
                        <p><?= e($item['long']) ?></p>
                        <ul class="ticks">
                            <?php foreach ($item['points'] as $point): ?>
                                <li><?= e($point) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php buy_form($item['slug'], 'produse.php', 'btn btn-dark'); ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
