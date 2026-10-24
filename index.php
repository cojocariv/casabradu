<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = require __DIR__ . '/includes/products.php';
$coffee = array_values(array_filter($products, static function (array $item): bool {
    return ($item['kind'] ?? '') !== 'aparat' && ($item['slug'] ?? '') !== 'aparate';
}));
$iconica = product_by_slug('iconica');

$page = 'acasa';
$pageTitle = 'Cafea artizanală din Italia';
$pageDescription = SITE_DESCRIPTION;
$pageImage = 'assets/img/hero-cafea.jpg';
$pageImageAlt = 'Ceașcă de cafea Casa Bradu Caffè, cu boabe prăjite';
$seoItems = seo_product_items($products);
require __DIR__ . '/includes/header.php';
?>

<section class="hero hero-bleed">
    <div class="hero-media">
        <img src="<?= e(asset('assets/img/hero-cafea.jpg')) ?>?v=2" alt="<?= tr('Ceașcă de cafea cu lapte, boabe prăjite și frunze verzi') ?>" width="1600" height="1067">
    </div>
    <div class="wrap hero-content reveal">
        <h1><?= tr('Cafeaua bună aduce') ?> <span class="script"><?= tr('zile mai fine') ?></span></h1>
        <p class="lede"><?= tr(SITE_DESCRIPTION) ?></p>
        <div class="hero-actions">
            <a class="btn btn-light" href="#produse"><?= tr('Vezi selecția') ?></a>
            <a class="btn btn-ghost" href="<?= e(url('contact.php')) ?>"><?= tr('Solicită ofertă') ?></a>
        </div>
    </div>
</section>

<section class="trust" aria-label="<?= tr('De ce Casa Bradu') ?>">
    <div class="wrap">
        <ul class="features">
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M6 13 16 8l10 5-10 5L6 13z"/><path d="M6 13v8l10 5 10-5v-8"/><path d="M16 18v8"/></svg>
                <span><?= tr('Import Italia') ?></span>
            </li>
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 6c4.2 0 7 4.4 7 10s-2.8 10-7 10-7-4.4-7-10 2.8-10 7-10z"/><path d="M16 8.5c1 4.2 1 10.8 0 15"/></svg>
                <span><?= tr('Raport ideal') ?></span>
            </li>
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M8 13h12v5a6 6 0 0 1-12 0v-5z"/><path d="M20 15h2.2a2.8 2.8 0 0 1 0 5.6H20"/><path d="M12 9.5c.4 1 .4 1.6 0 2.5M16.5 8.5c.4 1 .4 1.6 0 2.5"/></svg>
                <span><?= tr('Aromă') ?></span>
            </li>
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 27s7-6.4 7-13a7 7 0 1 0-14 0c0 6.6 7 13 7 13z"/><path d="M16 11.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5z"/></svg>
                <span><?= tr('Livrare în țară') ?></span>
            </li>
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M8 22V10h16v12"/><path d="M11 22v3h10v-3"/><path d="M12 14h8M12 17h8"/></svg>
                <span><?= tr('Aparat gratuit') ?></span>
            </li>
        </ul>
    </div>
</section>

<section class="section" id="produse">
    <div class="wrap">
        <div class="section-head reveal">
            <p class="kicker"><?= tr('Alegeri de casă') ?></p>
            <h2><?= tr('Cafeaua noastră') ?></h2>
            <p class="section-lead"><?= tr('gust fin') ?></p>
        </div>
        <div class="shop-grid">
            <?php foreach ($coffee as $item): ?>
                <article class="shop-item reveal">
                    <div class="shop-item-photo">
                        <img src="<?= e(asset($item['image'])) ?>" alt="<?= tr($item['alt']) ?>" width="800" height="800">
                    </div>
                    <div class="shop-item-body">
                        <p class="kicker"><?= tr($item['meta']) ?></p>
                        <h3><?= tr($item['title']) ?></h3>
                        <p><?= tr($item['short']) ?></p>
                        <?php buy_form($item['slug'], 'index.php'); ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="wrap">
        <div class="story reveal">
            <div>
                <p class="kicker"><?= tr('Povestea noastră') ?></p>
                <h2><?= tr('Mai mult decât cafea, un drum din Italia.') ?></h2>
                <p><?= tr('Casa Bradu Caffè aduce din Italia cafea artizanală și o livrează în toată țara, în Republica Moldova. Cialde, capsule și boabe, alese pentru aromă. Arabica dă finețea, Robusta ține corpul și crema. Raportul dintre ele este ideal în fiecare ceașcă.') ?></p>
                <a class="btn btn-dark" href="<?= e(url('despre.php')) ?>"><?= tr('Citește povestea') ?></a>
            </div>
            <div class="story-photo">
                <img src="<?= e(asset('assets/img/poveste-cafea.jpg')) ?>" alt="<?= tr('Cafea cu lapte, servită într-un pahar înalt') ?>" width="1000" height="750">
            </div>
        </div>
    </div>
</section>

<section class="section" id="aparate-acasa">
    <div class="wrap split">
        <div class="split-photo reveal">
            <?php if ($iconica !== null): ?>
                <img src="<?= e(asset((string) $iconica['image'])) ?>" alt="<?= tr((string) $iconica['alt']) ?>" width="1200" height="900">
            <?php else: ?>
                <img src="<?= e(asset('assets/img/aparat-cafea.jpg')) ?>" alt="<?= tr('Aparat de cafea pentru cialde') ?>" width="1200" height="900">
            <?php endif; ?>
        </div>
        <div class="reveal">
            <p class="kicker"><?= tr('Aparate de cafea') ?></p>
            <h2><?= $iconica !== null ? tr((string) $iconica['title']) : tr('Pentru cialde, capsule și boabe') ?></h2>
            <p class="home-machine-perk"><?= tr('La procurarea cafelei de la noi, aparatul se oferă gratuit în folosință.') ?></p>
            <p><?= $iconica !== null
                ? tr((string) ($iconica['short'] ?? ''))
                : tr('Oferim aparate de cafea în comodat și aparate la vânzare — pentru cialde, capsule și boabe, ca prepararea să țină pasul cu aroma. Pentru acasă, pentru birou sau pentru cine servește cafea în fiecare zi.') ?></p>
            <ul class="chips">
                <li><?= tr('Gratuit în folosință') ?></li>
                <li><?= tr('Cialde') ?></li>
                <li><?= tr('Acasă și birou') ?></li>
            </ul>
            <div class="btn-row">
                <a class="btn btn-dark" href="<?= e(url('aparate.php')) ?>"><?= tr('Vezi aparatele') ?></a>
                <?php
                if ($iconica !== null) {
                    buy_form('iconica', 'index.php', 'btn btn-line', 'comodat');
                }
                ?>
            </div>
        </div>
    </div>
</section>

<section class="offer" id="oferta">
    <?= wave_svg() ?>
    <div class="wrap offer-grid">
        <div class="reveal">
            <p class="kicker kicker-light"><?= tr('Colaborări') ?></p>
            <h2><?= tr('Hai să vorbim') ?></h2>
            <p class="script script-gold"><?= tr('despre cafea') ?></p>
            <p><?= tr('Pentru o ceașcă acasă, pentru birou sau pentru o colaborare. Livrăm în toată Republica Moldova. Spune-ne ce format cauți.') ?></p>
            <?php
            $theme = 'dark';
            require __DIR__ . '/includes/formular.php';
            ?>
        </div>
        <figure class="offer-photo reveal">
            <img src="<?= e(asset('assets/img/produs-boabe.jpg')) ?>" alt="<?= tr('Boabe de cafea prăjite, pregătite pentru măcinare') ?>" width="900" height="900">
            <figcaption><?= tr('Aromă și un raport ideal între Arabica și Robusta.') ?></figcaption>
        </figure>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
