<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/mesaj.php';

$sent = isset($_GET['trimis']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST';
$page = 'contact';
$pageTitle = 'Contact pentru cafea și aparate';
$pageDescription = 'Scrie la info@casabradu.md pentru cafea, aparate în comodat sau aparate la vânzare. Livrăm cialde, capsule și boabe în toată Republica Moldova.';
if ($sent) {
    $noindex = true;
}
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow"><?= tr('Contact') ?></p>
        <h1><?= tr('Spune-ne ce cafea cauți.') ?></h1>
        <p class="lede"><?= tr('Răspundem pentru cialde, capsule, boabe, aparate în comodat și aparate la vânzare. Livrăm în toată țara, în Republica Moldova.') ?></p>
    </div>
</section>

<section class="section">
    <div class="wrap contact-layout">
        <div class="contact-card" id="formular">
            <?php if ($sent): ?>
                <div class="notice" role="status">
                    <h2><?= tr('Ți-am primit mesajul.') ?></h2>
                    <p><?= tr('Îți răspundem în curând la adresa lăsată în formular.') ?></p>
                    <a class="btn btn-dark" href="<?= e(url('contact.php')) ?>"><?= tr('Trimite un alt mesaj') ?></a>
                </div>
            <?php else: ?>
                <?php $theme = 'light'; require __DIR__ . '/includes/formular.php'; ?>
            <?php endif; ?>
        </div>
        <aside class="contact-aside">
            <p class="kicker">Casa Bradu Caffè</p>
            <h2><?= tr('Aromă, raport ideal, livrare în toată țara.') ?></h2>
            <ul class="ticks">
                <li><?= tr('Cialde, capsule și boabe') ?></li>
                <li><?= tr('Arabica și Robusta, în raport ideal') ?></li>
                <li><?= tr('Livrare în toată Republica Moldova') ?></li>
                <li><?= tr('Aparate în comodat și aparate la vânzare, pentru cialde, capsule și boabe') ?></li>
            </ul>
            <p><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></p>
            <?php if (SITE_PHONE !== ''): ?>
                <p><a href="tel:<?= e(preg_replace('/\s+/', '', SITE_PHONE) ?? '') ?>"><?= e(SITE_PHONE) ?></a></p>
            <?php endif; ?>
            <p class="aside-legal"><?= e(SITE_LEGAL) ?><br><?= tr('Republica Moldova') ?></p>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
