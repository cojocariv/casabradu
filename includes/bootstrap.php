<?php

declare(strict_types=1);

const SITE_NAME = 'Casa Bradu Caffè';
const SITE_LEGAL = 'Casa Bradu Caffe SRL';
const SITE_DOMAIN = 'casabradu.md';
const SITE_EMAIL = 'info@casabradu.md';
const SITE_ORDERS_EMAIL = 'comenzi@casabradu.md';
const SITE_PHONE = '';
const COMPANY_LEGAL = 'S.R.L. CASA BRADU CAFFE';
const COMPANY_IDNO = '1026023149244';
const COMPANY_TVA = '0615161';
const COMPANY_IBAN = 'MD50AG000000022517877084';
const COMPANY_BANK = "BC 'MAIB' S.A.";
const COMPANY_ADDRESS = 'Republica Moldova';
const SITE_DESCRIPTION = 'Cafea din Italia, cu aromă și un raport ideal între Arabica și Robusta. Livrăm în toată Republica Moldova. Oferim aparate în comodat și aparate la vânzare.';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $haystack = str_replace('\\', '/', ($_SERVER['SCRIPT_NAME'] ?? '') . ' ' . ($_SERVER['REDIRECT_URL'] ?? ''));
    $lang = preg_match('#/ru(?:/|$)#', $haystack) === 1 ? 'ru' : 'ro';

    return $lang;
}

function web_root(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim($dir, '/');
    if (substr($dir, -3) === '/ru') {
        $dir = substr($dir, 0, -3);
    }

    if ($dir === '' || $dir === '.' || $dir === '/') {
        return '';
    }

    return $dir;
}

function site_base(): string
{
    return web_root();
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = web_root() . (lang() === 'ru' ? '/ru' : '');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $root = web_root();

    return ($root === '' ? '' : $root) . '/' . $path;
}

function t(string $text): string
{
    if ($text === '' || lang() !== 'ru') {
        return $text;
    }

    static $map = null;
    if ($map === null) {
        $loaded = require __DIR__ . '/lang-ru.php';
        $map = is_array($loaded) ? $loaded : [];
    }

    return $map[$text] ?? $text;
}

function tr(string $text): string
{
    return e(t($text));
}

function localized_canonical(string $code): string
{
    $file = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $prefix = $code === 'ru' ? '/ru' : '';
    $origin = 'https://' . SITE_DOMAIN;

    if ($file === 'index.php') {
        return $origin . ($prefix === '' ? '/' : $prefix . '/');
    }

    return $origin . $prefix . '/' . $file;
}

function page_switch_url(string $code): string
{
    $file = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $base = web_root() . ($code === 'ru' ? '/ru' : '');
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $suffix = ($query !== '' && preg_match('/^[A-Za-z0-9_=&%.-]+$/', $query) === 1) ? '?' . $query : '';

    if ($file === 'index.php') {
        return ($base === '' ? '/' : $base . '/') . $suffix;
    }

    return ($base === '' ? '' : $base) . '/' . $file . $suffix;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function subject_options(): array
{
    return [
        'cialde' => 'Cialde',
        'capsule' => 'Capsule',
        'boabe' => 'Cafea boabe',
        'pahare' => 'Pahare de unică folosință',
        'betisoare' => 'Bețișoare',
        'aparate' => 'Aparate de cafea',
        'colaborare' => 'Colaborare / distribuție',
    ];
}

function canonical_url(): string
{
    return localized_canonical(lang());
}

function breadcrumb_nav(string $page): string
{
    $labels = [
        'cafea' => 'Cafea',
        'aparate' => 'Aparate',
        'despre' => 'Despre',
        'contact' => 'Contact',
    ];

    if (!isset($labels[$page])) {
        return '';
    }

    return '<nav class="crumbs" aria-label="' . e(t('Traseu')) . '"><ol>'
        . '<li><a href="' . e(url()) . '">' . e(t('Acasă')) . '</a></li>'
        . '<li><span aria-current="page">' . e(t($labels[$page])) . '</span></li>'
        . '</ol></nav>';
}

function seo_product_items(array $products): array
{
    $items = [];

    foreach ($products as $product) {
        $slug = (string) ($product['slug'] ?? '');
        $prefix = lang() === 'ru' ? '/ru' : '';
        $items[] = [
            'name' => t((string) ($product['title'] ?? '')),
            'url' => $slug === 'aparate'
                ? 'https://' . SITE_DOMAIN . $prefix . '/aparate.php'
                : 'https://' . SITE_DOMAIN . $prefix . '/produse.php#' . $slug,
        ];
    }

    return $items;
}

function seo_graph(string $page, string $title, string $description, array $items = []): array
{
    $origin = 'https://' . SITE_DOMAIN;
    $canonical = canonical_url();
    $orgId = $origin . '/#organization';
    $siteId = $origin . '/#website';
    $prefix = lang() === 'ru' ? '/ru' : '';
    $labels = [
        'cafea' => [t('Cafea'), $origin . $prefix . '/produse.php'],
        'aparate' => [t('Aparate'), $origin . $prefix . '/aparate.php'],
        'despre' => [t('Despre'), $origin . $prefix . '/despre.php'],
        'contact' => [t('Contact'), $origin . $prefix . '/contact.php'],
    ];

    $organization = [
        '@type' => 'Organization',
        '@id' => $orgId,
        'name' => SITE_NAME,
        'legalName' => SITE_LEGAL,
        'url' => $origin . '/',
        'email' => SITE_EMAIL,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $origin . '/assets/img/logo.jpg',
        ],
        'image' => $origin . '/assets/img/hero-cafea.jpg',
        'description' => t(SITE_DESCRIPTION),
        'areaServed' => [
            '@type' => 'Country',
            'name' => t('Republica Moldova'),
        ],
        'brand' => [
            '@type' => 'Brand',
            'name' => SITE_NAME,
        ],
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'email' => SITE_EMAIL,
            'areaServed' => 'MD',
            'availableLanguage' => ['ro', 'ru'],
        ],
    ];

    if (SITE_PHONE !== '') {
        $organization['telephone'] = SITE_PHONE;
        $organization['contactPoint']['telephone'] = SITE_PHONE;
    }

    $graph = [
        $organization,
        [
            '@type' => 'WebSite',
            '@id' => $siteId,
            'url' => $origin . '/',
            'name' => SITE_NAME,
            'description' => t(SITE_DESCRIPTION),
            'inLanguage' => lang() === 'ru' ? 'ru' : 'ro',
            'publisher' => ['@id' => $orgId],
        ],
    ];

    $webPage = [
        '@type' => 'WebPage',
        '@id' => $canonical . '#webpage',
        'url' => $canonical,
        'name' => $title,
        'description' => $description,
        'inLanguage' => lang() === 'ru' ? 'ru' : 'ro',
        'isPartOf' => ['@id' => $siteId],
        'about' => ['@id' => $orgId],
        'publisher' => ['@id' => $orgId],
    ];

    if (isset($labels[$page])) {
        $crumbId = $canonical . '#breadcrumb';
        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $crumbId,
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => t('Acasă'),
                    'item' => $origin . ($prefix === '' ? '/' : $prefix . '/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $labels[$page][0],
                    'item' => $labels[$page][1],
                ],
            ],
        ];
        $webPage['breadcrumb'] = ['@id' => $crumbId];
    }

    if ($items !== []) {
        $list = [];
        foreach (array_values($items) as $index => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => (string) ($item['name'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
            ];
        }
        $graph[] = [
            '@type' => 'ItemList',
            'name' => $title,
            'numberOfItems' => count($list),
            'itemListElement' => $list,
        ];
    }

    $graph[] = $webPage;

    return [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];
}

function wave_svg(): string
{
    return '<svg class="wave" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true"><path fill="#16352b" d="M0,58 C160,92 280,16 460,36 C660,60 760,12 960,40 C1140,66 1280,20 1440,46 L1440,90 L0,90 Z"></path></svg>';
}

$config = __DIR__ . '/config.php';
if (is_file($config)) {
    require_once $config;
}

function site_whatsapp_digits(): string
{
    $raw = '';
    if (defined('SITE_WHATSAPP')) {
        $raw = (string) constant('SITE_WHATSAPP');
    } elseif (SITE_PHONE !== '') {
        $raw = SITE_PHONE;
    }
    $digits = preg_replace('/\D+/', '', $raw);

    return is_string($digits) ? $digits : '';
}

function site_whatsapp_url(string $message = ''): string
{
    $digits = site_whatsapp_digits();
    if ($digits === '') {
        return '';
    }
    $url = 'https://wa.me/' . $digits;
    $message = trim($message);
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }

    return $url;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/cont.php';
require_once __DIR__ . '/cos.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/google.php';
require_once __DIR__ . '/sheets.php';

if (!headers_sent()) {
    header('Content-Language: ' . lang());
}
