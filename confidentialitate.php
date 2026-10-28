<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$page = 'confidentialitate';
$pageTitle = 'Politica de confidențialitate';
$pageDescription = 'Cum prelucrăm datele personale pe casabradu.md, conform legislației din Republica Moldova.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow"><?= tr('Legal') ?></p>
        <h1><?= tr('Politica de confidențialitate') ?></h1>
        <p class="lede"><?= tr('Această politică explică ce date personale colectăm pe casabradu.md, de ce le folosim și ce drepturi ai.') ?></p>
    </div>
</section>

<section class="section section-tight">
    <div class="wrap legal-doc">
        <p class="legal-meta"><?= tr('Ultima actualizare:') ?> 27.10.2026</p>

        <h2><?= tr('1. Operatorul de date') ?></h2>
        <p><?= tr('Operatorul datelor personale este') ?> <strong><?= e(SITE_LEGAL) ?></strong> (<?= e(COMPANY_LEGAL) ?>), IDNO <?= e(COMPANY_IDNO) ?>, <?= e(COMPANY_ADDRESS) ?>.</p>
        <p><?= tr('Contact pentru date personale:') ?> <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a><?php if (SITE_ORDERS_EMAIL !== SITE_EMAIL): ?> · <a href="mailto:<?= e(SITE_ORDERS_EMAIL) ?>"><?= e(SITE_ORDERS_EMAIL) ?></a><?php endif; ?>.</p>

        <h2><?= tr('2. Ce date colectăm') ?></h2>
        <ul>
            <li><?= tr('Date din formularul de contact: nume, email, telefon (opțional), subiect, mesaj.') ?></li>
            <li><?= tr('Date de cont: nume, email, telefon, parolă (stocată criptat), adresă de livrare dacă o lași.') ?></li>
            <li><?= tr('Autentificare cu Google (dacă o folosești): identificator Google, email și nume confirmate de Google.') ?></li>
            <li><?= tr('Date de comandă: produse, cantități, tip persoană, date de facturare/livrare, mesaje legate de comandă.') ?></li>
            <li><?= tr('Date tehnice necesare funcționării site-ului: sesiune, coș, token CSRF, preferința limbii din URL.') ?></li>
        </ul>

        <h2><?= tr('3. De ce prelucrăm datele') ?></h2>
        <ul>
            <li><?= tr('Ca să răspundem la solicitări și oferte.') ?></li>
            <li><?= tr('Ca să creăm și să administrăm contul tău.') ?></li>
            <li><?= tr('Ca să procesăm comenzi, facturi, acte și livrări.') ?></li>
            <li><?= tr('Ca să respectăm obligații legale contabile și fiscale.') ?></li>
            <li><?= tr('Ca să asigurăm securitatea site-ului (anti-spam, sesiuni, autentificare).') ?></li>
        </ul>
        <p><?= tr('Temeiurile principale: executarea contractului / demersuri înainte de contract, interes legitim pentru funcționarea site-ului și obligații legale. Pentru mesaje de marketing separat, îți cerem consimțământul.') ?></p>

        <h2><?= tr('4. Cui transmitem datele') ?></h2>
        <p><?= tr('Nu vindem datele personale. Le putem transmite doar către:') ?></p>
        <ul>
            <li><?= tr('Furnizori de hosting și email necesari pentru operarea site-ului.') ?></li>
            <li><?= tr('Google, dacă te autentifici cu Google Sign-In.') ?></li>
            <li><?= tr('Instrumente interne de evidență a comenzilor (inclusiv foi de calcul, dacă sunt configurate), doar pentru procesarea comenzilor.') ?></li>
            <li><?= tr('Autorități publice, dacă legea o cere.') ?></li>
        </ul>

        <h2><?= tr('5. Cât timp păstrăm datele') ?></h2>
        <ul>
            <li><?= tr('Mesaje de contact: până la 24 de luni după ultimul răspuns, dacă nu există un contract ulterior.') ?></li>
            <li><?= tr('Cont și comenzi: pe durata relației și apoi pe perioada impusă de legislația contabilă/fiscală.') ?></li>
            <li><?= tr('Sesiunea de navigare: până la închiderea browserului sau expirarea sesiunii.') ?></li>
        </ul>

        <h2><?= tr('6. Drepturile tale') ?></h2>
        <p><?= tr('Conform Legii nr. 133/2011 privind protecția datelor cu caracter personal din Republica Moldova, poți cere:') ?></p>
        <ul>
            <li><?= tr('acces la datele tale;') ?></li>
            <li><?= tr('rectificarea datelor inexacte;') ?></li>
            <li><?= tr('ștergerea, în condițiile legii;') ?></li>
            <li><?= tr('restricționarea sau opoziția la anumite prelucrări;') ?></li>
            <li><?= tr('retragerea consimțământului, unde prelucrarea se bazează pe consimțământ.') ?></li>
        </ul>
        <p><?= tr('Scrie-ne la') ?> <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>. <?= tr('Poți și depune o plângere la Centrul Național pentru Protecția Datelor cu Caracter Personal (CNPDCP).') ?></p>

        <h2><?= tr('7. Cookie-uri') ?></h2>
        <p><?= tr('Folosim cookie-uri necesare pentru coș, autentificare și securitate. Detaliile sunt în') ?> <a href="<?= e(url('cookies.php')) ?>"><?= tr('Politica de cookies') ?></a>.</p>

        <h2><?= tr('8. Actualizări') ?></h2>
        <p><?= tr('Putem actualiza această politică. Versiunea curentă este mereu pe această pagină.') ?></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
