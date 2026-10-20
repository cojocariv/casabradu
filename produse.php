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
$pageDescription = 'Cialde, capsule și boabe din Italia, cu aromă și un raport ideal între Arabica și Robusta. Livrăm în toată Republica Moldova.';
$pageImage = 'assets/img/produs-boabe.jpg';
$pageImageAlt = 'Boabe de cafea Casa Bradu Caffè, importate din Italia';
$seoItems = seo_product_items($products);
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow"><?= tr('Cafea') ?></p>
        <h1><?= tr('Cialde, capsule și boabe.') ?></h1>
        <p class="lede"><?= tr('Același gust, în trei forme. Aromă deschisă și un raport ideal între Arabica și Robusta. Cafeaua vine din Italia și o livrăm în toată țara, în Republica Moldova.') ?></p>
        <p><a class="invoice-link" href="<?= e(url('aparate.php')) ?>"><?= tr('Aparatele pentru cialde, capsule și boabe le oferim în comodat.') ?></a></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="filters" aria-label="<?= tr('Filtrează produsele') ?>">
            <button class="filter is-active" type="button" data-filter="toate"><?= tr('Toate') ?></button>
            <?php foreach ($products as $item): ?>
                <button class="filter" type="button" data-filter="<?= e($item['slug']) ?>"><?= tr($item['title']) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="shop-grid product-shop">
            <?php foreach ($products as $item): ?>
                <article class="shop-item reveal" id="<?= e($item['slug']) ?>" data-cat="<?= e($item['slug']) ?>">
                    <div class="shop-item-photo">
                        <img src="<?= e(asset($item['image'])) ?>" alt="<?= tr($item['alt']) ?>" width="800" height="800">
                    </div>
                    <div class="shop-item-body">
                        <p class="kicker"><?= tr($item['kicker']) ?></p>
                        <h2><?= tr($item['title']) ?></h2>
                        <p><?= tr($item['short']) ?></p>
                        <?php buy_form($item['slug'], 'produse.php', 'btn btn-dark btn-sm'); ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
