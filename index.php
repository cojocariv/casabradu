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
$pageDescription = 'Cafea din Italia. La procurare, aparatul e gratuit în folosință. Livrare în toată Republica Moldova.';
$pageImage = 'assets/img/hero-cafea.jpg';
$pageImageAlt = 'Ceașcă de cafea Casa Bradu Caffè, cu boabe prăjite';
$seoItems = seo_product_items($products);
$birouWhatsApp = site_whatsapp_digits() !== ''
    ? site_whatsapp_url(t('Bună! Vreau ofertă pentru birou: cafea + aparat gratuit în folosință.'))
    : '';
require __DIR__ . '/includes/header.php';
?>

<section class="hero hero-bleed">
    <div class="hero-media">
        <img src="<?= e(asset('assets/img/hero-cafea.jpg')) ?>?v=2" alt="<?= tr('Ceașcă de cafea cu lapte, boabe prăjite și frunze verzi') ?>" width="1600" height="1067">
    </div>
    <div class="wrap hero-content reveal">
        <h1 class="hero-sell"><?= tr('Cafea din Italia. Aparat gratuit în folosință.') ?></h1>
        <p class="lede"><?= tr('La procurarea cafelei de la Casa Bradu, aparatul e 0 lei în folosință. Livrăm în toată Moldova — acasă sau la birou.') ?></p>
        <div class="hero-actions">
            <a class="btn btn-light" href="#pachete"><?= tr('Alege pachetul') ?></a>
            <a class="btn btn-ghost" href="#produse"><?= tr('Comandă cafea') ?></a>
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

<section class="section section-packs" id="pachete">
    <div class="wrap">
        <div class="section-head reveal">
            <p class="kicker"><?= tr('Cum începi') ?></p>
            <h2><?= tr('Trei drumuri clare spre comandă.') ?></h2>
            <p class="section-lead"><?= tr('Alegi ce ți se potrivește — noi ne ocupăm de rest.') ?></p>
        </div>
        <div class="packs-grid">
            <article class="pack reveal">
                <p class="pack-tag"><?= tr('Pentru acasă') ?></p>
                <h3><?= tr('Starter casă') ?></h3>
                <p><?= tr('Aparat Iconica în folosință (0 lei) + cialde Intenso. Espresso acasă, fără investiție în aparat.') ?></p>
                <ul class="pack-points">
                    <li><?= tr('Aparat gratuit în folosință') ?></li>
                    <li><?= tr('Cialde Intenso') ?></li>
                    <li><?= tr('Livrare în Moldova') ?></li>
                </ul>
                <form class="pack-form" method="post" action="<?= e(url('cos.php')) ?>">
                    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                    <input type="hidden" name="actiune" value="pachet">
                    <input type="hidden" name="pachet" value="casa">
                    <input type="hidden" name="inapoi" value="index.php">
                    <button class="btn btn-dark" type="submit"><?= tr('Adaugă în coș') ?></button>
                </form>
            </article>
            <article class="pack pack-featured reveal">
                <p class="pack-tag"><?= tr('Pentru birou') ?></p>
                <h3><?= tr('Cafea la birou') ?></h3>
                <p><?= tr('Consum lunar pe măsura echipei, aparat în folosință, factură și livrare — fără bătaie de cap.') ?></p>
                <ul class="pack-points">
                    <li><?= tr('Aparat 0 lei în folosință') ?></li>
                    <li><?= tr('Cantitate pe măsura biroului') ?></li>
                    <li><?= tr('Factură / transfer') ?></li>
                </ul>
                <?php if ($birouWhatsApp !== ''): ?>
                    <a class="btn btn-dark" href="<?= e($birouWhatsApp) ?>" target="_blank" rel="noopener noreferrer"><?= tr('Ofertă pe WhatsApp') ?></a>
                <?php else: ?>
                    <a class="btn btn-dark" href="#oferta"><?= tr('Solicită ofertă birou') ?></a>
                <?php endif; ?>
            </article>
            <article class="pack reveal">
                <p class="pack-tag"><?= tr('Ai deja aparat') ?></p>
                <h3><?= tr('Doar cafea') ?></h3>
                <p><?= tr('Refill rapid: cialde Intenso + boabe Intenso 500 g. Compleezi stocul și gata.') ?></p>
                <ul class="pack-points">
                    <li><?= tr('Cialde Intenso') ?></li>
                    <li><?= tr('Boabe Intenso 500 g') ?></li>
                    <li><?= tr('Comandă din nou, oricând') ?></li>
                </ul>
                <form class="pack-form" method="post" action="<?= e(url('cos.php')) ?>">
                    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                    <input type="hidden" name="actiune" value="pachet">
                    <input type="hidden" name="pachet" value="refill">
                    <input type="hidden" name="inapoi" value="index.php">
                    <button class="btn btn-dark" type="submit"><?= tr('Adaugă în coș') ?></button>
                </form>
            </article>
        </div>
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
                <?php
                $mainImage = (string) ($item['image'] ?? '');
                $hoverImage = trim((string) ($item['hover_image'] ?? ''));
                if ($hoverImage === '') {
                    $hoverImage = trim((string) ($item['offer_image'] ?? ''));
                }
                ?>
                <article class="shop-item reveal">
                    <div class="shop-item-photo<?= $hoverImage !== '' && $hoverImage !== $mainImage ? ' has-hover' : '' ?>">
                        <img class="shop-photo-main" src="<?= e(asset($mainImage)) ?>" alt="<?= tr($item['alt']) ?>" width="800" height="800" data-shop-main>
                        <?php if ($hoverImage !== '' && $hoverImage !== $mainImage): ?>
                            <img class="shop-photo-hover" src="<?= e(asset($hoverImage)) ?>" alt="" width="800" height="800" data-shop-hover aria-hidden="true">
                        <?php endif; ?>
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
                <h2><?= tr('Gustul bun are o casă.') ?></h2>
                <p><?= tr('Rețeta artizanală poartă semnătura Casei Bradu. Cafeaua este prăjită și ambalată cu grijă în Italia — mereu proaspătă, echilibrată și cu un gust aparte. Pentru diminețile de acasă, pauzele de la birou și obiceiul drag de a sta la povești în jurul unei cafele bune.') ?></p>
                <p><?= tr('De patru ani, rețeta noastră este îndrăgită de clienții care ne-au rămas aproape. Astăzi, ducem povestea Casei Bradu mai departe, păstrând aceeași grijă pentru gust, pentru oameni și pentru lucrurile făcute temeinic.') ?></p>
                <p class="script"><?= tr('Din casa noastră, în ritualul tău.') ?></p>
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
