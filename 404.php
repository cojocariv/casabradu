<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

http_response_code(404);

$page = 'eroare';
$noindex = true;
$noCanonical = true;
$pageTitle = 'Pagină negăsită';
$pageDescription = 'Pagina căutată nu există. Cafeaua și aparatele Casa Bradu Caffè sunt pe paginile lor.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">404</p>
        <h1><?= tr('Pagina nu există.') ?></h1>
        <p class="lede"><?= tr('Linkul nu duce nicăieri. Cafeaua și aparatele în comodat sunt pe paginile lor.') ?></p>
        <div class="btn-row">
            <a class="btn btn-dark" href="<?= e(url()) ?>"><?= tr('Acasă') ?></a>
            <a class="btn btn-line" href="<?= e(url('produse.php')) ?>"><?= tr('Cafea') ?></a>
            <a class="btn btn-line" href="<?= e(url('aparate.php')) ?>"><?= tr('Aparate') ?></a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
