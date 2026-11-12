<?php

declare(strict_types=1);

function crm_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function crm_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($base === '/' || $base === '\\' || $base === '.') {
        $base = '';
    }
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function crm_asset(string $path): string
{
    return crm_url(ltrim($path, '/'));
}

function crm_flash_set(string $message): void
{
    $_SESSION['crm_flash'] = $message;
}

function crm_flash_get(): string
{
    $message = (string) ($_SESSION['crm_flash'] ?? '');
    unset($_SESSION['crm_flash']);

    return $message;
}

function crm_text(string $value, int $max): string
{
    $value = trim(str_replace(["\r", "\n", "\0"], '', $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }

    return $value;
}

function crm_money_input(string $raw): ?float
{
    $raw = trim(str_replace([' ', "\xc2\xa0"], '', $raw));
    if ($raw === '') {
        return null;
    }
    $raw = str_replace(',', '.', $raw);
    if (!is_numeric($raw)) {
        return null;
    }

    return round((float) $raw, 2);
}

function crm_money_format(?float $value): string
{
    if ($value === null) {
        return '—';
    }

    return number_format($value, 2, ',', ' ') . ' lei';
}

function crm_status_labels(): array
{
    return [
        'activ' => 'Activ',
        'ok' => 'OK',
        'atentie' => 'Atenție',
        'pierdut' => 'Pierdut',
    ];
}

function crm_produs_options(): array
{
    return [
        'cialde' => 'Cialde',
        'capsule' => 'Capsule',
        'boabe' => 'Boabe',
        'pahare mici' => 'Pahare mici',
        'pahare mari' => 'Pahare mari',
        'betisoare' => 'Bețișoare',
        'aparat' => 'Aparat (vânzare)',
        'altul' => 'Altul',
    ];
}

function crm_aparat_tipuri(): array
{
    return [
        'cialde' => 'Cialde',
        'capsule' => 'Capsule',
        'boabe' => 'Boabe',
        'altul' => 'Altul',
    ];
}

function crm_csrf_ok(?string $token): bool
{
    return is_string($token) && hash_equals((string) ($_SESSION['csrf'] ?? ''), $token);
}
