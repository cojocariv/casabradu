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

function admin_client(string $clientId): ?array
{
    if ($clientId === '') {
        return null;
    }
    try {
        $stmt = db()->prepare(
            'SELECT id, nume, email, telefon, adresa, rechizite FROM clienti WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$clientId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    } catch (Throwable $e) {
        return null;
    }
}

function admin_ensure_client(string $email, string $name, string $phone, string $requisites): ?array
{
    $email = strtolower(text_line($email, 120));
    $name = text_line($name, 80);
    $phone = text_line($phone, 20);
    $requisites = requisite_normalize($requisites);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '') {
        return null;
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT id, nume, email, telefon, adresa, rechizite FROM clienti WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            $nextPhone = $phone !== '' ? $phone : (string) ($row['telefon'] ?? '');
            $nextRequisites = $requisites !== '' ? $requisites : (string) ($row['rechizite'] ?? '');
            $update = $pdo->prepare(
                'UPDATE clienti SET nume = ?, telefon = ?, rechizite = ? WHERE id = ?'
            );
            $update->execute([
                $name,
                $nextPhone !== '' ? $nextPhone : '-',
                $nextRequisites,
                (string) $row['id'],
            ]);
            $row['nume'] = $name;
            $row['telefon'] = $nextPhone !== '' ? $nextPhone : '-';
            $row['rechizite'] = $nextRequisites;

            return $row;
        }

        $id = bin2hex(random_bytes(8));
        $insert = $pdo->prepare(
            'INSERT INTO clienti (id, nume, email, telefon, parola, adresa, creat, rechizite)
             VALUES (?, ?, ?, ?, ?, \'\', ?, ?)'
        );
        $insert->execute([
            $id,
            $name,
            $email,
            $phone !== '' ? $phone : '-',
            password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            date('Y-m-d H:i:s'),
            $requisites,
        ]);

        return [
            'id' => $id,
            'nume' => $name,
            'email' => $email,
            'telefon' => $phone !== '' ? $phone : '-',
            'adresa' => '',
            'rechizite' => $requisites,
        ];
    } catch (Throwable $e) {
        db_failed(true);

        return null;
    }
}

function admin_create_invoice(array $client, array $lines, string $requisites, string $address = '', string $note = ''): ?array
{
    if ($lines === [] || ($client['id'] ?? '') === '') {
        return null;
    }
    $requisites = requisite_normalize($requisites);
    if ($requisites === '') {
        $requisites = (string) ($client['rechizite'] ?? '');
    }
    $address = text_line($address, 300);
    $note = text_block($note !== '' ? $note : 'Cont generat din cabinetul administratorului.', 1000);
    $order = [
        'id' => 'CB' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
        'user_id' => (string) $client['id'],
        'la' => date('c'),
        'status' => 'inregistrata',
        'linii' => $lines,
        'adresa' => $address,
        'nota' => $note,
        'rechizite' => $requisites,
        'email' => (string) ($client['email'] ?? ''),
        'nume' => (string) ($client['nume'] ?? ''),
        'telefon' => (string) ($client['telefon'] ?? ''),
    ];

    try {
        $pdo = db();
        $pdo->beginTransaction();
        $insert = $pdo->prepare(
            'INSERT INTO comenzi (id, user_id, la, status, adresa, nota, rechizite) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $order['id'],
            $order['user_id'],
            date('Y-m-d H:i:s'),
            $order['status'],
            $address,
            $note,
            $requisites,
        ]);
        $lineInsert = $pdo->prepare(
            'INSERT INTO comenzi_linii (comanda_id, slug, titlu, cantitate, pret) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($lines as $line) {
            $pret = $line['pret'] ?? null;
            $lineInsert->execute([
                $order['id'],
                (string) ($line['slug'] ?? 'manual'),
                (string) $line['titlu'],
                (int) $line['cantitate'],
                is_numeric($pret) ? number_format((float) $pret, 2, '.', '') : null,
            ]);
        }
        if ($requisites !== '') {
            $reqUpdate = $pdo->prepare('UPDATE clienti SET rechizite = ? WHERE id = ?');
            $reqUpdate->execute([$requisites, $order['user_id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        db_failed(true);

        return null;
    }

    return $order;
}
