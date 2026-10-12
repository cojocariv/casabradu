<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$page = 'despre';
$pageTitle = 'Despre noi';
$pageDescription = 'Casa Bradu Caffè importă din Italia cafea artizanală și o distribuie sub brand propriu: cialde, capsule, boabe și aparate de cafea.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Povestea noastră</p>
        <h1>Cafea artizanală, adusă din Italia.</h1>
        <p class="lede">Casa Bradu Caffè se ocupă de importul cafelei artizanale din Italia și de distribuția ei sub brandul nostru. Cialde, capsule și cafea boabe. Alături, aparate pentru cialde și pentru capsule.</p>
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
            <p>Nu ținem o cafenea în paginile astea. Aducem cafeaua. O importăm din Italia și o distribuim sub numele Casa Bradu Caffè, ca același amestec să ajungă acasă, la birou sau în locul în care se servesc zeci de cești pe zi.</p>
            <p>Căutăm un gust aromat și fin. Arabica deschide aroma și dulceața. Robusta dă corp, cremă și prezență. Raportul dintre ele este partea de care avem grijă: nici aspru, nici gol.</p>
            <a class="btn btn-dark" href="<?= e(url('produse.php')) ?>">Vezi produsele</a>
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
                <p>Cafeaua pleacă din Italia și ajunge aici sub brandul Casa Bradu Caffè, în cialde, capsule și boabe.</p>
            </article>
            <article class="card plain-card">
                <h3>Același amestec</h3>
                <p>Formatul se schimbă. Gustul rămâne: aromat, fin, cu Arabica și Robusta în echilibru.</p>
            </article>
            <article class="card plain-card">
                <h3>Și aparatul</h3>
                <p>Importăm aparate pentru cialde și aparate pentru capsule, ca prepararea să fie pe măsura cafelei.</p>
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
        <p class="center-note">Amestecul Casa Bradu caută raportul în care cele două se completează. Un gust aromat și fin, nu un exces și nu o lipsă.</p>
        <p class="center-note"><a class="btn btn-dark" href="<?= e(url('contact.php')) ?>">Scrie-ne</a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
