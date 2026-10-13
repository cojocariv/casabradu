<?php

declare(strict_types=1);

function order_statuses(): array
{
    return [
        'inregistrata' => 'Înregistrată',
        'confirmata' => 'Confirmată',
        'achitata' => 'Achitată',
        'livrata' => 'Livrată',
        'anulata' => 'Anulată',
    ];
}

function order_status_label(string $status): string
{
    $labels = order_statuses();

    return t($labels[$status] ?? 'Înregistrată');
}

function admin_configured(): bool
{
    return defined('ADMIN_USER') && ADMIN_USER !== '' && defined('ADMIN_PASS') && ADMIN_PASS !== '';
}

function admin_logged_in(): bool
{
    return ($_SESSION['admin'] ?? false) === true;
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: ' . url('admin.php'));
        exit;
    }
}

function admin_same(string $known, string $given): bool
{
    return hash_equals(hash('sha256', $known), hash('sha256', $given));
}

function admin_login(string $user, string $password): bool
{
    if (!admin_configured() || $user === '' || $password === '') {
        return false;
    }

    $ok = admin_same(ADMIN_USER, $user) && admin_same(ADMIN_PASS, $password);
    if (!$ok) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin'] = true;

    return true;
}

function admin_logout(): void
{
    unset($_SESSION['admin']);
}

function admin_login_blocked(): bool
{
    return (int) ($_SESSION['admin_wait'] ?? 0) > time();
}

function admin_login_failed(): void
{
    $fails = (int) ($_SESSION['admin_fails'] ?? 0) + 1;
    if ($fails >= 5) {
        $_SESSION['admin_wait'] = time() + 60;
        $_SESSION['admin_fails'] = 0;

        return;
    }
    $_SESSION['admin_fails'] = $fails;
}

function admin_login_succeeded(): void
{
    unset($_SESSION['admin_fails'], $_SESSION['admin_wait']);
}

function admin_orders(): ?array
{
    try {
        $pdo = db();
        $orders = $pdo->query(
            'SELECT c.id, c.user_id, c.la, c.status, c.adresa, c.nota, c.rechizite,
                    k.nume, k.email, k.telefon
             FROM comenzi c
             LEFT JOIN clienti k ON k.id = c.user_id
             ORDER BY c.la DESC, c.id DESC'
        );
        if ($orders === false) {
            return null;
        }

        return orders_with_lines($pdo, $orders->fetchAll());
    } catch (Throwable $e) {
        return null;
    }
}

function admin_order(string $orderId): ?array
{
    if ($orderId === '') {
        return null;
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT c.id, c.user_id, c.la, c.status, c.adresa, c.nota, c.rechizite,
                    k.nume, k.email, k.telefon
             FROM comenzi c
             LEFT JOIN clienti k ON k.id = c.user_id
             WHERE c.id = ?
             LIMIT 1'
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        $rows = orders_with_lines($pdo, [$row]);

        return $rows[0] ?? null;
    } catch (Throwable $e) {
        return null;
    }
}

function admin_set_status(string $orderId, string $status): bool
{
    if (!isset(order_statuses()[$status]) || $orderId === '') {
        return false;
    }

    try {
        $pdo = db();
        $check = $pdo->prepare('SELECT id FROM comenzi WHERE id = ? LIMIT 1');
        $check->execute([$orderId]);
        if ($check->fetch() === false) {
            return false;
        }
        $stmt = $pdo->prepare('UPDATE comenzi SET status = ? WHERE id = ?');

        return $stmt->execute([$status, $orderId]);
    } catch (Throwable $e) {
        return false;
    }
}
