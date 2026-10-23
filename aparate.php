<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = array_values(array_filter(
    require __DIR__ . '/includes/products.php',
    static function (array $item): bool {
        return $item['slug'] === 'aparate';
    }
));
$page = 'aparate';
$pageTitle = 'Aparate în comodat și aparate la vânzare';
$pageDescription = 'Oferim aparate de cafea în comodat și aparate la vânzare, pentru cialde, capsule și boabe. Livrare în toată Republica Moldova.';
$pageImage = 'assets/img/aparat-cafea.jpg';
$pageImageAlt = 'Aparat de cafea pentru capsule, cu o ceașcă de espresso';
$seoItems = seo_product_items($products);
require __DIR__ . '/includes/header.php';
$item = $products[0];
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow"><?= tr('Aparate') ?></p>
        <h1><?= tr('Comodat și aparate la vânzare.') ?></h1>
        <p class="lede"><?= tr('Oferim aparate de cafea în comodat și aparate la vânzare — pentru cialde, pentru capsule și pentru boabe. Prepararea ține pasul cu aroma, acasă sau la birou, oriunde livrăm în Republica Moldova.') ?></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="product-grid product-grid-single">
            <article class="product-card reveal" id="aparate">
                <div class="product-card-photo">
                    <img src="<?= e(asset($item['image'])) ?>" alt="<?= tr($item['alt']) ?>" width="640" height="640">
                </div>
                <div class="product-card-body">
                    <p class="kicker"><?= tr($item['kicker']) ?></p>
                    <h2><?= tr($item['title']) ?></h2>
                    <p class="product-short"><?= tr($item['long']) ?></p>
                    <?php buy_form('aparate', 'aparate.php', 'btn btn-dark btn-sm'); ?>
                </div>
            </article>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
