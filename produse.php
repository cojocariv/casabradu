<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = array_values(array_filter(
    require __DIR__ . '/includes/products.php',
    static function (array $item): bool {
        return $item['slug'] !== 'aparate';
    }
));
$page = 'cafea';
$pageTitle = 'Cialde, capsule și boabe din Italia';
$pageDescription = 'Cialde, capsule și cafea boabe Casa Bradu Caffè, importate din Italia pentru Moldova. Alegi bucăți sau cutii, iar la boabe 250 g, 500 g sau 1 kg.';
$pageImage = 'assets/img/produs-boabe.jpg';
$pageImageAlt = 'Boabe de cafea Casa Bradu Caffè, importate din Italia';
$seoItems = seo_product_items($products);
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow">Cafea</p>
        <h1>Cialde, capsule și boabe.</h1>
        <p class="lede">Același gust, în trei forme: aromat, fin, cu un raport echilibrat între Arabica și Robusta. Cafeaua vine din Italia.</p>
        <p><a class="invoice-link" href="<?= e(url('aparate.php')) ?>">Aparatele de cafea sunt pe o pagină separată.</a></p>
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

        <div class="product-grid">
            <?php foreach ($products as $item): ?>
                <article class="product-card reveal" id="<?= e($item['slug']) ?>" data-cat="<?= e($item['slug']) ?>">
                    <div class="product-card-photo">
                        <img src="<?= e(asset($item['image'])) ?>" alt="<?= e($item['alt']) ?>" width="640" height="640">
                    </div>
                    <div class="product-card-body">
                        <p class="kicker"><?= e($item['kicker']) ?></p>
                        <h2><?= e($item['title']) ?></h2>
                        <p class="product-short"><?= e($item['short']) ?></p>
                        <?php buy_form($item['slug'], 'produse.php', 'btn btn-dark btn-sm'); ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
