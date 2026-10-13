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
$pageTitle = 'Aparate pentru cialde și capsule';
$pageDescription = 'Aparate de cafea pentru cialde și capsule, alese împreună cu cafeaua Casa Bradu Caffè. Pentru acasă, birou sau serviciu, în Moldova.';
$pageImage = 'assets/img/aparat-cafea.jpg';
$pageImageAlt = 'Aparat de cafea pentru capsule, cu o ceașcă de espresso';
$seoItems = seo_product_items($products);
require __DIR__ . '/includes/header.php';
$item = $products[0];
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow">Aparate</p>
        <h1>Aparate pentru cialde și capsule.</h1>
        <p class="lede">Importăm aparate de cafea pentru cialde și pentru capsule, ca prepararea să țină pasul cu cafeaua. Pentru acasă, pentru birou sau pentru cine servește cafea în fiecare zi.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="product-grid product-grid-single">
            <article class="product-card reveal" id="aparate">
                <div class="product-card-photo">
                    <img src="<?= e(asset($item['image'])) ?>" alt="<?= e($item['alt']) ?>" width="640" height="640">
                </div>
                <div class="product-card-body">
                    <p class="kicker"><?= e($item['kicker']) ?></p>
                    <h2><?= e($item['title']) ?></h2>
                    <p class="product-short"><?= e($item['long']) ?></p>
                    <?php buy_form('aparate', 'aparate.php', 'btn btn-dark btn-sm'); ?>
                </div>
            </article>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
