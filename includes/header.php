<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$page = $page ?? 'acasa';
$pageTitle = $pageTitle ?? SITE_NAME;
$pageDescription = $pageDescription ?? SITE_DESCRIPTION;
$fullTitle = $page === 'acasa'
    ? SITE_NAME . ' · Cafea artizanală din Italia'
    : $pageTitle . ' · ' . SITE_NAME;

$client = current_user();
$nav = [
    'acasa' => ['Acasă', url()],
    'produse' => ['Produse', url('produse.php')],
    'despre' => ['Despre', url('despre.php')],
    'contact' => ['Contact', url('contact.php')],
    'cont' => $client
        ? ['Cabinet', url('cabinet.php')]
        : ['Cont', url('autentificare.php')],
];

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => SITE_NAME,
    'legalName' => SITE_LEGAL,
    'url' => 'https://' . SITE_DOMAIN . '/',
    'email' => SITE_EMAIL,
    'logo' => 'https://' . SITE_DOMAIN . '/assets/img/logo.jpg',
    'description' => SITE_DESCRIPTION,
    'areaServed' => 'MD',
    'brand' => [
        '@type' => 'Brand',
        'name' => SITE_NAME,
    ],
];

if (SITE_PHONE !== '') {
    $schema['telephone'] = SITE_PHONE;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <?php if (!empty($noindex)): ?>
        <meta name="robots" content="noindex">
    <?php endif; ?>
    <link rel="canonical" href="<?= e(canonical_url()) ?>">
    <meta name="theme-color" content="#16352b">
    <meta property="og:locale" content="ro_RO">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e(canonical_url()) ?>">
    <meta property="og:image" content="https://<?= e(SITE_DOMAIN) ?>/assets/img/hero-cafea.jpg">
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
            <img src="<?= e(asset('assets/img/logo.jpg')) ?>" alt="<?= e(SITE_NAME) ?>">
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
            </div>
            <a class="btn btn-dark btn-sm" href="<?= e(url('contact.php')) ?>">Solicită ofertă</a>
        </nav>
    </div>
</header>
<main id="continut">
