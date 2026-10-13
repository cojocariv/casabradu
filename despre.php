<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$page = 'despre';
$pageTitle = 'Import de cafea din Italia';
$pageDescription = 'Cafea din Italia, cu aromă și un raport ideal între Arabica și Robusta. Livrăm în toată Moldova și oferim aparate în comodat.';
$pageImage = 'assets/img/poveste-cafea.jpg';
$pageImageAlt = 'Cafea cu lapte Casa Bradu Caffè';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow">Povestea noastră</p>
        <h1>Cafea artizanală, adusă din Italia.</h1>
        <p class="lede">Casa Bradu Caffè importă din Italia cafea artizanală și o livrează în toată țara, în Republica Moldova. Cialde, capsule și boabe, cu aromă și un raport ideal între Arabica și Robusta. Aparatele le oferim în comodat.</p>
    </div>
</section>

<section class="section">
    <div class="wrap split">
        <div class="split-photo">
            <img src="<?= e(asset('assets/img/hero-cafea.jpg')) ?>" alt="Ceașcă de cafea cu model de frunză în spumă" width="1200" height="900">
        </div>
        <div>
            <p class="kicker">Casa Bradu</p>
            <h2>Un gust care se recunoaște.</h2>
            <p>Nu ținem o cafenea în paginile astea. Aducem cafeaua. O importăm din Italia și o livrăm în toată Republica Moldova, ca același amestec să ajungă acasă, la birou sau acolo unde se servesc zeci de cești pe zi.</p>
            <p>Punem accent pe aromă. Arabica deschide dulceața și finețea. Robusta dă corp, cremă și prezență. Raportul dintre ele este ideal: nici aspru, nici gol.</p>
            <div class="btn-row">
                <a class="btn btn-dark" href="<?= e(url('produse.php')) ?>">Vezi cafeaua</a>
                <a class="btn btn-line" href="<?= e(url('aparate.php')) ?>">Vezi aparatele</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="wrap">
        <div class="section-head">
            <p class="kicker">Ce facem</p>
            <h2>Import și distribuție</h2>
        </div>
        <div class="values">
            <article class="card plain-card">
                <h3>Din Italia</h3>
                <p>Cafeaua pleacă din Italia și ajunge în toată Republica Moldova, sub brandul Casa Bradu Caffè, în cialde, capsule și boabe.</p>
            </article>
            <article class="card plain-card">
                <h3>Același amestec</h3>
                <p>Formatul se schimbă. Aroma rămâne, cu Arabica și Robusta într-un raport ideal.</p>
            </article>
            <article class="card plain-card">
                <h3>Aparatul, în comodat</h3>
                <p>Oferim în comodat aparate pentru cialde, capsule și boabe, ca prepararea să fie pe măsura aromei.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="wrap">
        <div class="section-head">
            <p class="kicker">Amestecul</p>
            <h2>Arabica și Robusta</h2>
            <p class="script">in equilibrio</p>
        </div>
        <div class="blend">
            <article>
                <h3>Arabica</h3>
                <p>Aroma, finețea și notele dulci. Ea dă cafelei eleganța și face gustul să se deschidă încet.</p>
            </article>
            <article>
                <h3>Robusta</h3>
                <p>Corpul, crema și intensitatea. Ea ține gustul prezent până la ultima înghițitură.</p>
            </article>
        </div>
        <p class="center-note">Amestecul Casa Bradu ține raportul ideal dintre Arabica și Robusta. Aroma rămâne deschisă, fină, prezentă în ceașcă.</p>
        <p class="center-note"><a class="btn btn-dark" href="<?= e(url('contact.php')) ?>">Scrie-ne</a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
