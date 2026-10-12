<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/mesaj.php';

$sent = isset($_GET['trimis']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST';
$page = 'contact';
$pageTitle = 'Contact';
$pageDescription = 'Scrie-ne pentru cialde, capsule, cafea boabe sau aparate Casa Bradu Caffè. Import din Italia și distribuție în Republica Moldova.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Contact</p>
        <h1>Spune-ne ce cafea cauți.</h1>
        <p class="lede">Răspundem pentru cialde, capsule, boabe și aparate. Pentru acasă, pentru birou sau pentru o colaborare.</p>
    </div>
</section>

<section class="section">
    <div class="wrap contact-layout">
        <div class="contact-card" id="formular">
            <?php if ($sent): ?>
                <div class="notice" role="status">
                    <h2>Ți-am primit mesajul.</h2>
                    <p>Îți răspundem în curând la adresa lăsată în formular.</p>
                    <a class="btn btn-dark" href="<?= e(url('contact.php')) ?>">Trimite un alt mesaj</a>
                </div>
            <?php else: ?>
                <?php $theme = 'light'; require __DIR__ . '/includes/formular.php'; ?>
            <?php endif; ?>
        </div>
        <aside class="contact-aside">
            <p class="kicker">Casa Bradu Caffè</p>
            <h2>Import din Italia, distribuție sub brandul nostru.</h2>
            <ul class="ticks">
                <li>Cialde</li>
                <li>Capsule</li>
                <li>Cafea boabe</li>
                <li>Aparate pentru cialde și capsule</li>
            </ul>
            <p><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></p>
            <?php if (SITE_PHONE !== ''): ?>
                <p><a href="tel:<?= e(preg_replace('/\s+/', '', SITE_PHONE) ?? '') ?>"><?= e(SITE_PHONE) ?></a></p>
            <?php endif; ?>
            <p class="aside-legal"><?= e(SITE_LEGAL) ?><br>Republica Moldova</p>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
