<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = array_values(array_filter(
    require __DIR__ . '/includes/products.php',
    static function (array $item): bool {
        return ($item['kind'] ?? '') !== 'aparat' && ($item['slug'] ?? '') !== 'aparate';
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
        <p><a class="invoice-link" href="<?= e(url('aparate.php')) ?>"><?= tr('Aparatele pentru cialde, capsule și boabe le oferim în comodat și la vânzare.') ?></a></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php
        $filterCats = [];
        $catLabels = [
            'cialde' => 'Cialde',
            'capsule' => 'Capsule',
            'boabe' => 'Boabe',
            'pahare' => 'Pahare',
            'betisoare' => 'Bețișoare',
        ];
        foreach ($products as $item) {
            $cat = (string) ($item['cat'] ?? $item['slug'] ?? '');
            if ($cat === '' || isset($filterCats[$cat])) {
                continue;
            }
            if (isset($item['cat_label']) && is_string($item['cat_label']) && $item['cat_label'] !== '') {
                $filterCats[$cat] = $item['cat_label'];
            } else {
                $filterCats[$cat] = $catLabels[$cat] ?? (string) ($item['title'] ?? $cat);
            }
        }
        ?>
        <div class="filters" aria-label="<?= tr('Filtrează produsele') ?>">
            <button class="filter is-active" type="button" data-filter="toate"><?= tr('Toate') ?></button>
            <?php foreach ($filterCats as $catKey => $catLabel): ?>
                <button class="filter" type="button" data-filter="<?= e($catKey) ?>"><?= tr($catLabel) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="shop-grid product-shop">
            <?php foreach ($products as $item): ?>
                <article class="shop-item reveal" id="<?= e($item['slug']) ?>" data-cat="<?= e((string) ($item['cat'] ?? $item['slug'])) ?>">
                    <?php
                    $mainImage = (string) ($item['image'] ?? '');
                    $hoverImage = trim((string) ($item['hover_image'] ?? ''));
                    if ($hoverImage === '') {
                        $hoverImage = trim((string) ($item['offer_image'] ?? ''));
                    }
                    ?>
                    <div class="shop-item-photo<?= $hoverImage !== '' && $hoverImage !== $mainImage ? ' has-hover' : '' ?>">
                        <img class="shop-photo-main" src="<?= e(asset($mainImage)) ?>" alt="<?= tr($item['alt']) ?>" width="800" height="800" data-shop-main>
                        <?php if ($hoverImage !== '' && $hoverImage !== $mainImage): ?>
                            <img class="shop-photo-hover" src="<?= e(asset($hoverImage)) ?>" alt="" width="800" height="800" data-shop-hover aria-hidden="true">
                        <?php endif; ?>
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
