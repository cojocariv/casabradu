<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$page = $page ?? 'acasa';
$pageTitle = $pageTitle ?? SITE_NAME;
$pageDescription = $pageDescription ?? SITE_DESCRIPTION;
$fullTitle = $page === 'acasa'
    ? 'Cafea artizanală din Italia · ' . SITE_NAME
    : $pageTitle . ' · ' . SITE_NAME;
$pageImage = $pageImage ?? 'assets/img/hero-cafea.jpg';
$pageImageAlt = $pageImageAlt ?? 'Cafea artizanală Casa Bradu Caffè, importată din Italia';
$ogImage = 'https://' . SITE_DOMAIN . '/' . ltrim($pageImage, '/');
$seoItems = $seoItems ?? [];

$client = current_user();
$nav = [
    'acasa' => ['Acasă', url()],
    'cafea' => ['Cafea', url('produse.php')],
    'aparate' => ['Aparate', url('aparate.php')],
    'despre' => ['Despre', url('despre.php')],
    'contact' => ['Contact', url('contact.php')],
    'cont' => $client
        ? ['Cabinet', url('cabinet.php')]
        : ['Cont', url('autentificare.php')],
];

$schema = seo_graph($page, $fullTitle, $pageDescription, is_array($seoItems) ? $seoItems : []);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <?php if (!empty($noindex)): ?>
        <meta name="robots" content="noindex, follow">
    <?php endif; ?>
    <?php if (empty($noCanonical)): ?>
        <link rel="canonical" href="<?= e(canonical_url()) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#16352b">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e(empty($noCanonical) ? canonical_url() : 'https://' . SITE_DOMAIN . '/') ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:alt" content="<?= e($pageImageAlt) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($fullTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <link rel="icon" href="<?= e(asset('assets/img/favicon.jpg')) ?>?v=3" type="image/jpeg">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/img/favicon.jpg')) ?>?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Great+Vibes&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <?php if ($page === 'acasa'): ?>
        <link rel="preload" as="image" href="<?= e(asset('assets/img/hero-cafea.jpg')) ?>">
    <?php endif; ?>
    <script>document.documentElement.classList.add('js')</script>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="page-<?= e($page) ?>">
<a class="skip" href="#continut">Sari la conținut</a>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="logo" href="<?= e(url()) ?>">
            <img src="<?= e(asset('assets/img/logo.jpg?v=4')) ?>" alt="<?= e(SITE_NAME) ?>">
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="navigare">
            <span class="sr-only">Meniu</span>
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <nav class="nav" id="navigare" aria-label="Principal">
            <div class="nav-links">
                <?php foreach ($nav as $key => $item): ?>
                    <?php $isCurrent = $page === $key || ($key === 'cont' && in_array($page, ['cont', 'cabinet', 'inregistrare', 'autentificare'], true)); ?>
                    <a href="<?= e($item[1]) ?>" <?= $isCurrent ? 'aria-current="page"' : '' ?>><?= e($item[0]) ?></a>
                <?php endforeach; ?>
                <a href="<?= e(url('cos.php')) ?>" <?= $page === 'cos' ? 'aria-current="page"' : '' ?>>Coș<?php $cartCount = cart_count(); if ($cartCount > 0): ?> <span class="cart-count"><?= (int) $cartCount ?></span><?php endif; ?></a>
            </div>
            <a class="btn btn-dark btn-sm" href="<?= e(url('contact.php')) ?>">Solicită ofertă</a>
        </nav>
    </div>
</header>
<main id="continut">
<?php
$headerFlash = '';
if (!isset($flash)) {
    $headerFlash = flash_get();
}
?>
<?php if ($headerFlash !== ''): ?>
    <div class="wrap flash-bar">
        <p class="notice" role="status"><?= e($headerFlash) ?></p>
    </div>
<?php endif; ?>
