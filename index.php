<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = require __DIR__ . '/includes/products.php';
$coffee = array_values(array_filter($products, static function (array $item): bool {
    return $item['slug'] !== 'aparate';
}));
$frames = ['visual-soft', 'visual-round', 'visual-arch'];

$page = 'acasa';
$pageTitle = 'Cafea artizanală din Italia';
$pageDescription = SITE_DESCRIPTION;
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="wrap hero-grid">
        <div class="hero-copy reveal">
            <p class="eyebrow">Caffè artigianale</p>
            <h1>Cafeaua bună aduce</h1>
            <p class="script">zile mai fine</p>
            <p class="lede">La Casa Bradu Caffè importăm din Italia cafea artizanală și o distribuim sub brandul nostru. Cialde, capsule și boabe, cu un gust aromat și fin și un raport echilibrat între Arabica și Robusta.</p>
            <a class="btn btn-dark" href="#produse">Vezi selecția</a>
        </div>
        <div class="hero-photo reveal">
            <img src="<?= e(asset('assets/img/hero-cafea.jpg')) ?>" alt="Ceașcă de cafea cu lapte, boabe prăjite și frunze verzi" width="1200" height="900">
        </div>
    </div>
    <div class="wrap">
        <ul class="features">
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M6 13 16 8l10 5-10 5L6 13z"/><path d="M6 13v8l10 5 10-5v-8"/><path d="M16 18v8"/></svg>
                <span>Import Italia</span>
            </li>
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 6c4.2 0 7 4.4 7 10s-2.8 10-7 10-7-4.4-7-10 2.8-10 7-10z"/><path d="M16 8.5c1 4.2 1 10.8 0 15"/></svg>
                <span>Arabica &amp; Robusta</span>
            </li>
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M8 13h12v5a6 6 0 0 1-12 0v-5z"/><path d="M20 15h2.2a2.8 2.8 0 0 1 0 5.6H20"/><path d="M12 9.5c.4 1 .4 1.6 0 2.5M16.5 8.5c.4 1 .4 1.6 0 2.5"/></svg>
                <span>Gust aromat și fin</span>
            </li>
            <li>
                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M12 9h8v10.2a4 4 0 0 1-8 0V9z"/><path d="M12 13h8"/><path d="M14.5 6.5h3"/></svg>
                <span>Cialde și capsule</span>
            </li>
        </ul>
        <div class="ornament" aria-hidden="true"><span></span><i></i><span></span></div>
    </div>
</section>

<section class="section" id="produse">
    <div class="wrap">
        <div class="section-head reveal">
            <p class="kicker">Alegeri de casă</p>
            <h2>Cafeaua noastră</h2>
            <p class="script">gust fin</p>
        </div>
        <div class="cards">
            <?php foreach ($coffee as $index => $item): ?>
                <article class="card reveal">
                    <div class="card-visual <?= e($frames[$index % 3]) ?>">
                        <img src="<?= e(asset($item['image'])) ?>" alt="<?= e($item['alt']) ?>" width="800" height="800">
                    </div>
                    <h3><?= e($item['title']) ?></h3>
                    <p><?= e($item['short']) ?></p>
                    <p class="card-meta"><?= e($item['meta']) ?></p>
                    <a class="btn btn-dark btn-sm" href="<?= e(url('contact.php?subiect=' . $item['slug'] . '#formular')) ?>">Cere ofertă</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-tight">
    <div class="wrap">
        <div class="story reveal">
            <div>
                <p class="kicker">Povestea noastră</p>
                <h2>Mai mult decât cafea, un drum din Italia.</h2>
                <p>Casa Bradu Caffè aduce în Moldova cafea artizanală, distribuită sub brandul nostru. Cialde, capsule și boabe, alese pentru un gust aromat și fin. Arabica dă finețea, Robusta ține corpul și crema. Împreună caută echilibrul pe care îl vrem în fiecare ceașcă.</p>
                <a class="btn btn-dark" href="<?= e(url('despre.php')) ?>">Citește povestea</a>
            </div>
            <div class="story-photo">
                <img src="<?= e(asset('assets/img/poveste-cafea.jpg')) ?>" alt="Cafea cu lapte, servită într-un pahar înalt" width="1000" height="750">
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap split">
        <div class="split-photo reveal">
            <img src="<?= e(asset('assets/img/aparat-cafea.jpg')) ?>" alt="Aparat de cafea pentru capsule, cu o ceașcă de espresso" width="1200" height="900">
        </div>
        <div class="reveal">
            <p class="kicker">Aparate de cafea</p>
            <h2>Pentru cialde și capsule</h2>
            <p class="script">espresso a casa</p>
            <p>Importăm aparate pentru cialde și aparate pentru capsule, ca prepararea să țină pasul cu cafeaua. Pentru acasă, pentru birou sau pentru cine servește cafea în fiecare zi.</p>
            <ul class="chips">
                <li>Aparate pentru cialde</li>
                <li>Aparate pentru capsule</li>
            </ul>
            <div class="btn-row">
                <a class="btn btn-dark" href="<?= e(url('produse.php#aparate')) ?>">Vezi aparatele</a>
                <a class="btn btn-line" href="<?= e(url('contact.php?subiect=aparate#formular')) ?>">Cere ofertă</a>
            </div>
        </div>
    </div>
</section>

<section class="offer" id="oferta">
    <?= wave_svg() ?>
    <div class="wrap offer-grid">
        <div class="reveal">
            <p class="kicker kicker-light">Colaborări</p>
            <h2>Hai să vorbim</h2>
            <p class="script script-gold">despre cafea</p>
            <p>Pentru o ceașcă acasă, pentru birou sau pentru o colaborare. Spune-ne ce format cauți.</p>
            <?php
            $theme = 'dark';
            require __DIR__ . '/includes/formular.php';
            ?>
        </div>
        <figure class="offer-photo reveal">
            <img src="<?= e(asset('assets/img/produs-boabe.jpg')) ?>" alt="Boabe de cafea prăjite, pregătite pentru măcinare" width="900" height="900">
            <figcaption>Gust aromat și fin. Arabica și Robusta, în echilibru.</figcaption>
        </figure>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
