<?php

declare(strict_types=1);

function crm_admin_configured(): bool
{
    return defined('ADMIN_USER') && ADMIN_USER !== '' && defined('ADMIN_PASS') && ADMIN_PASS !== '';
}

function crm_logged_in(): bool
{
    return ($_SESSION['crm_admin'] ?? false) === true || ($_SESSION['admin'] ?? false) === true;
}

function crm_require_login(): void
{
    if (!crm_logged_in()) {
        header('Location: ' . crm_url('login.php'));
        exit;
    }
}

function crm_same(string $known, string $given): bool
{
    return hash_equals(hash('sha256', $known), hash('sha256', $given));
}

function crm_login(string $user, string $password): bool
{
    if (!crm_admin_configured() || $user === '' || $password === '') {
        return false;
    }
    if (!crm_same((string) ADMIN_USER, $user) || !crm_same((string) ADMIN_PASS, $password)) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['crm_admin'] = true;
    $_SESSION['admin'] = true;

    return true;
}

function crm_logout(): void
{
    unset($_SESSION['crm_admin'], $_SESSION['admin']);
}

function crm_login_blocked(): bool
{
    return (int) ($_SESSION['crm_wait'] ?? 0) > time();
}

function crm_login_failed(): void
{
    $fails = (int) ($_SESSION['crm_fails'] ?? 0) + 1;
    if ($fails >= 5) {
        $_SESSION['crm_wait'] = time() + 60;
        $_SESSION['crm_fails'] = 0;

        return;
    }
    $_SESSION['crm_fails'] = $fails;
}

function crm_login_ok(): void
{
    unset($_SESSION['crm_fails'], $_SESSION['crm_wait']);
}
