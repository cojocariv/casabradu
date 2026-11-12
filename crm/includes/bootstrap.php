<?php

declare(strict_types=1);

$siteRoot = dirname(__DIR__, 2);
$config = $siteRoot . '/includes/config.php';
if (!is_file($config)) {
    http_response_code(500);
    echo 'Lipsește includes/config.php pe site.';
    exit;
}

require_once $config;
require_once $siteRoot . '/includes/db.php';

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

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data.php';

crm_install();
