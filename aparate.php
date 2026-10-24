<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = array_values(array_filter(
    require __DIR__ . '/includes/products.php',
    static function (array $item): bool {
        return ($item['kind'] ?? '') === 'aparat' || ($item['slug'] ?? '') === 'aparate';
    }
));
$page = 'aparate';
$pageTitle = 'Aparate în comodat și aparate la vânzare';
$pageDescription = 'La procurarea cafelei de la Casa Bradu Caffè, oferim un aparat gratuit în folosință. Aparate și la vânzare, pentru cialde, capsule și boabe.';
$pageImage = (string) (($products[0]['image'] ?? '') !== '' ? $products[0]['image'] : 'assets/img/aparat-cafea.jpg');
$pageImageAlt = (string) ($products[0]['alt'] ?? 'Aparat de cafea pentru cialde');
$seoItems = seo_product_items($products);
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow"><?= tr('Aparate') ?></p>
        <h1><?= tr('Comodat și aparate la vânzare.') ?></h1>
        <p class="lede"><?= tr('Oferim aparate de cafea în comodat și aparate la vânzare — pentru cialde, pentru capsule și pentru boabe. Prepararea ține pasul cu aroma, acasă sau la birou, oriunde livrăm în Republica Moldova.') ?></p>
        <p class="machines-perk"><?= tr('La procurarea cafelei de la Casa Bradu Caffè, oferim un aparat gratuit în folosință.') ?></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="product-grid product-grid-machines">
            <?php foreach ($products as $item): ?>
                <?php
                $gallery = [];
                $rawImages = $item['images'] ?? null;
                if (is_array($rawImages)) {
                    foreach ($rawImages as $path) {
                        if (is_string($path) && trim($path) !== '') {
                            $gallery[] = trim($path);
                        }
                    }
                }
                if ($gallery === [] && trim((string) ($item['image'] ?? '')) !== '') {
                    $gallery[] = (string) $item['image'];
                }
                $mainImage = $gallery[0] ?? (string) ($item['image'] ?? '');
                ?>
                <article class="product-card product-card-machine reveal" id="<?= e((string) $item['slug']) ?>">
                    <div class="product-card-media">
                        <div class="product-card-photo">
                            <img src="<?= e(asset($mainImage)) ?>" alt="<?= tr((string) $item['alt']) ?>" width="640" height="640" data-machine-main>
                        </div>
                        <?php if (count($gallery) > 1): ?>
                            <div class="product-thumbs" role="group" aria-label="<?= e(t('Imagini produs')) ?>">
                                <?php foreach ($gallery as $index => $path): ?>
                                    <button class="product-thumb<?= $index === 0 ? ' is-active' : '' ?>" type="button" data-machine-thumb="<?= e(asset($path)) ?>" aria-label="<?= e(t('Imagine') . ' ' . ($index + 1)) ?>">
                                        <img src="<?= e(asset($path)) ?>" alt="" width="120" height="120">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-body">
                        <p class="kicker"><?= tr((string) $item['kicker']) ?></p>
                        <h2><?= tr((string) $item['title']) ?></h2>
                        <div class="product-detail">
                            <?php
                            $details = $item['details'] ?? null;
                            if (is_array($details) && $details !== []):
                                foreach ($details as $paragraph):
                                    if (!is_string($paragraph) || trim($paragraph) === '') {
                                        continue;
                                    }
                                    ?>
                                    <p><?= tr($paragraph) ?></p>
                                <?php endforeach;
                            else: ?>
                                <p><?= tr((string) ($item['long'] ?? $item['short'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php buy_form((string) $item['slug'], 'aparate.php', 'btn btn-dark btn-sm'); ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
