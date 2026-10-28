<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$page = 'cookies';
$pageTitle = 'Politica de cookies';
$pageDescription = 'Ce cookie-uri folosim pe casabradu.md și cum poți gestiona consimțământul.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <?= breadcrumb_nav($page) ?>
        <p class="eyebrow"><?= tr('Legal') ?></p>
        <h1><?= tr('Politica de cookies') ?></h1>
        <p class="lede"><?= tr('Cookie-urile ne ajută să ținem coșul, sesiunea și securitatea site-ului. Mai jos vezi ce folosim și ce poți controla.') ?></p>
    </div>
</section>

<section class="section section-tight">
    <div class="wrap legal-doc">
        <p class="legal-meta"><?= tr('Ultima actualizare:') ?> 27.10.2026</p>

        <h2><?= tr('1. Ce sunt cookie-urile') ?></h2>
        <p><?= tr('Cookie-urile sunt fișiere mici stocate pe dispozitivul tău când vizitezi un site. Unele sunt esențiale pentru funcționare; altele pot fi folosite pentru analiză sau marketing — pe casabradu.md, de regulă, folosim doar cookie-uri necesare.') ?></p>

        <h2><?= tr('2. Ce cookie-uri folosim') ?></h2>
        <div class="legal-table-wrap">
            <table class="legal-table">
                <thead>
                    <tr>
                        <th><?= tr('Nume / tip') ?></th>
                        <th><?= tr('Scop') ?></th>
                        <th><?= tr('Durată') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PHPSESSID</td>
                        <td><?= tr('Sesiune: coș, autentificare, mesaje flash, token CSRF.') ?></td>
                        <td><?= tr('Până la închiderea browserului') ?></td>
                    </tr>
                    <tr>
                        <td>cbc_cookie_consent</td>
                        <td><?= tr('Memorează că ai văzut și acceptat bannerul de cookies (localStorage, nu cookie HTTP).') ?></td>
                        <td><?= tr('Până o ștergi din browser') ?></td>
                    </tr>
                    <tr>
                        <td>Google Fonts</td>
                        <td><?= tr('Încarcă fonturile afișate pe site. Poate implica o cerere către serverele Google.') ?></td>
                        <td><?= tr('Conform politicii Google') ?></td>
                    </tr>
                    <tr>
                        <td>Google Sign-In</td>
                        <td><?= tr('Doar pe paginile de autentificare/înregistrare, dacă alegi „Intră cu Google”.') ?></td>
                        <td><?= tr('Conform politicii Google') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2><?= tr('3. Cookie-uri de terți') ?></h2>
        <p><?= tr('Nu rulăm în prezent instrumente de analiză sau publicitate (de tip Google Analytics, Meta Pixel). Dacă vom adăuga astfel de servicii, vom actualiza această politică și vom cere consimțământul înainte de a le activa.') ?></p>

        <h2><?= tr('4. Cum poți controla cookie-urile') ?></h2>
        <ul>
            <li><?= tr('Poți accepta bannerul de pe site; preferința se salvează local pe dispozitivul tău.') ?></li>
            <li><?= tr('Poți șterge cookie-urile și datele site-ului din setările browserului.') ?></li>
            <li><?= tr('Dacă blochezi cookie-urile esențiale, coșul și autentificarea pot să nu mai funcționeze corect.') ?></li>
        </ul>
        <p>
            <button type="button" class="btn btn-line" id="cookie-reset" hidden><?= tr('Resetează preferința de cookies') ?></button>
        </p>

        <h2><?= tr('5. Date personale') ?></h2>
        <p><?= tr('Detaliile despre prelucrarea datelor personale sunt în') ?> <a href="<?= e(url('confidentialitate.php')) ?>"><?= tr('Politica de confidențialitate') ?></a>.</p>

        <h2><?= tr('6. Contact') ?></h2>
        <p><?= e(SITE_LEGAL) ?> · <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
