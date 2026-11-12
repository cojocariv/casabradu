<?php

declare(strict_types=1);

function crm_header(string $title, string $page = ''): void
{
    $flash = crm_flash_get();
    ?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= crm_e($title) ?> · CRM Casa Bradu</title>
    <link rel="stylesheet" href="<?= crm_e(crm_asset('assets/crm.css')) ?>?v=2">
</head>
<body>
<header class="crm-top">
    <div class="crm-wrap crm-top-inner">
        <a class="crm-brand" href="<?= crm_e(crm_url()) ?>">CRM Casa Bradu</a>
        <?php if (crm_logged_in()): ?>
            <nav class="crm-nav">
                <a href="<?= crm_e(crm_url()) ?>"<?= $page === 'acasa' ? ' aria-current="page"' : '' ?>>Panou</a>
                <a href="<?= crm_e(crm_url('clienti.php')) ?>"<?= $page === 'clienti' ? ' aria-current="page"' : '' ?>>Clienți</a>
                <a href="<?= crm_e(crm_url('vanzari.php')) ?>"<?= $page === 'vanzari' ? ' aria-current="page"' : '' ?>>Vânzări</a>
                <a href="<?= crm_e(crm_url('iesire.php')) ?>">Ieșire</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="crm-main">
    <div class="crm-wrap">
        <?php if ($flash !== ''): ?>
            <p class="crm-flash" role="status"><?= crm_e($flash) ?></p>
        <?php endif; ?>
    <?php
}

function crm_footer(): void
{
    ?>
    </div>
</main>
<footer class="crm-foot">
    <div class="crm-wrap">
        <p>Casa Bradu Caffe SRL · evidență internă · crm.casabradu.md</p>
    </div>
</footer>
</body>
</html>
    <?php
}
