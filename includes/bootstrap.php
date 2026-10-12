<?php

declare(strict_types=1);

const SITE_NAME = 'Casa Bradu Caffè';
const SITE_LEGAL = 'Casa Bradu Caffe SRL';
const SITE_DOMAIN = 'casabradu.md';
const SITE_EMAIL = 'info@casabradu.md';
const SITE_PHONE = '';
const SITE_DESCRIPTION = 'Importăm din Italia cafea artizanală și o distribuim sub brandul Casa Bradu Caffè: cialde, capsule, cafea boabe și aparate de cafea. Gust aromat și fin, cu un raport echilibrat între Arabica și Robusta.';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function site_base(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim($dir, '/');

    if ($dir === '' || $dir === '.') {
        return '';
    }

    return $dir;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = site_base();

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset(string $path): string
{
    return url($path);
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
        'aparate' => 'Aparate de cafea',
        'colaborare' => 'Colaborare / distribuție',
    ];
}

function canonical_url(): string
{
    $file = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');

    if ($file === 'index.php') {
        return 'https://' . SITE_DOMAIN . '/';
    }

    return 'https://' . SITE_DOMAIN . '/' . $file;
}

function wave_svg(): string
{
    return '<svg class="wave" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true"><path fill="#16352b" d="M0,58 C160,92 280,16 460,36 C660,60 760,12 960,40 C1140,66 1280,20 1440,46 L1440,90 L0,90 Z"></path></svg>';
}
