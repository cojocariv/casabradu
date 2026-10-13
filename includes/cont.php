<?php

declare(strict_types=1);

function text_line(string $value, int $max): string
{
    $value = trim(str_replace(["\r", "\n", "\0"], '', $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }

    return $value;
}

function text_block(string $value, int $max): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = str_replace("\0", '', $value);
    $value = trim($value);
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }

    return $value;
}

function public_client(array $row): array
{
    unset($row['parola']);

    return $row;
}

function current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? '';
    if (!is_string($id) || $id === '') {
        return null;
    }

    try {
        $stmt = db()->prepare('SELECT id, nume, email, telefon, adresa, rechizite, creat FROM clienti WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return null;
    }

    if (!is_array($row)) {
        unset($_SESSION['user_id']);

        return null;
    }

    return public_client($row);
}

function safe_next(?string $next): string
{
    $next = str_replace(["\r", "\n", "\0"], '', (string) $next);
    if ($next === 'cabinet.php' || $next === 'cos.php') {
        return $next;
    }
    if (preg_match('/^cabinet\.php\?produs=(cialde|capsule|boabe|aparate)$/', $next) === 1) {
        return $next;
    }

    return 'cabinet.php';
}

function require_client(): array
{
    $user = current_user();
    if ($user !== null) {
        return $user;
    }

    $file = basename($_SERVER['SCRIPT_NAME'] ?? 'cabinet.php');
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $next = $file . ($query !== '' ? '?' . $query : '');
    header('Location: ' . url('autentificare.php?next=' . rawurlencode(safe_next($next))));
    exit;
}

function create_client(string $nume, string $email, string $telefon, string $password): array
{
    try {
        $id = bin2hex(random_bytes(8));
        $stmt = db()->prepare(
            'INSERT INTO clienti (id, nume, email, telefon, parola, adresa, creat)
             VALUES (?, ?, ?, ?, ?, \'\', ?)'
        );
        $stmt->execute([
            $id,
            $nume,
            $email,
            $telefon,
            password_hash($password, PASSWORD_DEFAULT),
            date('Y-m-d H:i:s'),
        ]);
    } catch (PDOException $e) {
        return [
            'id' => null,
            'duplicate' => (string) $e->getCode() === '23000',
        ];
    } catch (Throwable $e) {
        return [
            'id' => null,
            'duplicate' => false,
        ];
    }

    return [
        'id' => $id,
        'duplicate' => false,
    ];
}

function login_client(string $email, string $password): bool
{
    try {
        $stmt = db()->prepare('SELECT id, parola FROM clienti WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $found = $stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }

    if (!is_array($found) || !password_verify($password, (string) ($found['parola'] ?? ''))) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (string) $found['id'];

    return true;
}

function logout_client(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function requisite_fields(): array
{
    return [
        'companie' => 'Denumirea companiei',
        'idno' => 'IDNO',
        'tva' => 'Cod TVA',
        'adresa' => 'Adresa juridică',
        'iban' => 'IBAN și banca',
    ];
}

function requisite_labels(): array
{
    return array_values(requisite_fields());
}

function requisite_template(): string
{
    $lines = [];
    foreach (requisite_labels() as $label) {
        $lines[] = $label . ': ';
    }

    return implode("\n", $lines);
}

function requisite_normalize(string $posted): string
{
    $posted = str_replace(["\r\n", "\r"], "\n", str_replace("\0", '', $posted));
    $labels = requisite_labels();
    $values = [];
    foreach ($labels as $label) {
        $values[$label] = '';
    }
    $matchOrder = $labels;
    usort($matchOrder, static function (string $a, string $b): int {
        return strlen($b) <=> strlen($a);
    });
    $extras = [];
    foreach (explode("\n", $posted) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $matched = false;
        foreach ($matchOrder as $label) {
            if (preg_match('/^' . preg_quote($label, '/') . '\s*:?\s*(.*)$/u', $line, $found) === 1) {
                $values[$label] = trim((string) $found[1]);
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $extras[] = $line;
        }
    }
    $lines = [];
    foreach ($labels as $label) {
        $lines[] = $label . ': ' . $values[$label];
    }
    foreach ($extras as $extra) {
        $lines[] = $extra;
    }
    $text = implode("\n", $lines);
    if (strlen($text) > 2000) {
        $text = substr($text, 0, 2000);
    }

    return $text;
}

function requisite_value(string $text, string $label): string
{
    $normalized = requisite_normalize($text);
    if (preg_match('/^' . preg_quote($label, '/') . ':[ \t]*(.*)$/mu', $normalized, $found) !== 1) {
        return '';
    }

    return trim((string) $found[1]);
}

function client_order(string $userId, string $orderId): ?array
{
    foreach (client_orders($userId) as $order) {
        if ((string) ($order['id'] ?? '') === $orderId) {
            return $order;
        }
    }

    return null;
}

function requisites_filled(string $text): bool
{
    $normalized = requisite_normalize($text);
    foreach (['Denumirea companiei', 'IDNO', 'Adresa juridică', 'IBAN și banca'] as $label) {
        if (preg_match('/^' . preg_quote($label, '/') . ':[ \t]*\S/mu', $normalized) !== 1) {
            return false;
        }
    }

    return true;
}

function save_client_requisites(string $userId, string $requisites): bool
{
    try {
        $stmt = db()->prepare('UPDATE clienti SET rechizite = ? WHERE id = ?');

        return $stmt->execute([$requisites, $userId]);
    } catch (Throwable $e) {
        return false;
    }
}

function remember_address(string $userId, string $address): void
{
    try {
        $stmt = db()->prepare('UPDATE clienti SET adresa = ? WHERE id = ?');
        $stmt->execute([$address, $userId]);
    } catch (Throwable $e) {
        return;
    }
}

function change_client_password(string $userId, string $current, string $next): bool
{
    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT parola FROM clienti WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!is_array($row) || !password_verify($current, (string) ($row['parola'] ?? ''))) {
            return false;
        }

        $update = $pdo->prepare('UPDATE clienti SET parola = ? WHERE id = ?');

        return $update->execute([password_hash($next, PASSWORD_DEFAULT), $userId]);
    } catch (Throwable $e) {
        return false;
    }
}

function client_orders(string $userId): array
{
    try {
        $pdo = db();
        $orders = $pdo->prepare(
            'SELECT id, user_id, la, status, adresa, nota, rechizite FROM comenzi WHERE user_id = ? ORDER BY la DESC, id DESC'
        );
        $orders->execute([$userId]);
        $rows = $orders->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    return orders_with_lines($pdo, $rows);
}

function orders_with_lines(PDO $pdo, array $rows): array
{
    if ($rows === []) {
        return [];
    }

    $ids = [];
    foreach ($rows as $row) {
        $ids[] = (string) $row['id'];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $lines = $pdo->prepare(
        "SELECT comanda_id, slug, titlu, cantitate, pret FROM comenzi_linii WHERE comanda_id IN ($placeholders) ORDER BY id"
    );
    $lines->execute($ids);
    $grouped = [];
    foreach ($lines->fetchAll() as $line) {
        $grouped[(string) $line['comanda_id']][] = [
            'slug' => (string) $line['slug'],
            'titlu' => (string) $line['titlu'],
            'cantitate' => (int) $line['cantitate'],
            'pret' => ($line['pret'] === null || $line['pret'] === '') ? null : (float) $line['pret'],
        ];
    }

    foreach ($rows as $index => $row) {
        $stamp = strtotime((string) ($row['la'] ?? '')) ?: time();
        $rows[$index]['la'] = date('c', $stamp);
        $rows[$index]['linii'] = $grouped[(string) $row['id']] ?? [];
    }

    return $rows;
}

function place_order(array $user, array $lines, string $address, string $note): ?array
{
    $order = [
        'id' => 'CB' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
        'user_id' => (string) $user['id'],
        'la' => date('c'),
        'status' => 'inregistrata',
        'linii' => $lines,
        'adresa' => $address,
        'nota' => $note,
        'rechizite' => (string) ($user['rechizite'] ?? ''),
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
            $order['rechizite'],
        ]);
        $lineInsert = $pdo->prepare(
            'INSERT INTO comenzi_linii (comanda_id, slug, titlu, cantitate, pret) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($lines as $line) {
            $pret = $line['pret'] ?? null;
            $lineInsert->execute([
                $order['id'],
                (string) $line['slug'],
                (string) $line['titlu'],
                (int) $line['cantitate'],
                is_numeric($pret) ? number_format((float) $pret, 2, '.', '') : null,
            ]);
        }
        $addressUpdate = $pdo->prepare('UPDATE clienti SET adresa = ? WHERE id = ?');
        $addressUpdate->execute([$address, $order['user_id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return null;
    }

    notify_order($order, $user);

    return $order;
}

function notify_order(array $order, array $user): void
{
    if (!function_exists('mail')) {
        return;
    }

    $lines = '';
    foreach ($order['linii'] as $line) {
        $lines .= '- ' . $line['titlu'] . ': ' . $line['cantitate'] . "\n";
    }

    $body = "Comandă nouă " . $order['id'] . "\n\n"
        . "Client: " . $user['nume'] . "\n"
        . "Email: " . $user['email'] . "\n"
        . "Telefon: " . $user['telefon'] . "\n\n"
        . $lines . "\n"
        . "Adresă: " . $order['adresa'] . "\n\n"
        . "Rechizite pentru cont de plată:\n" . ($order['rechizite'] !== '' ? $order['rechizite'] : '—') . "\n\n"
        . ($order['nota'] !== '' ? $order['nota'] . "\n" : '');

    $email = str_replace(["\r", "\n"], '', (string) $user['email']);
    $headers = "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "From: Casa Bradu Caffe <" . SITE_EMAIL . ">\r\n"
        . "Reply-To: {$email}\r\n";

    @mail(SITE_EMAIL, 'Comandă ' . $order['id'], $body, $headers);
}

function save_message(string $nume, string $email, string $telefon, string $subiect, string $mesaj): bool
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO mesaje (la, nume, email, telefon, subiect, mesaj) VALUES (?, ?, ?, ?, ?, ?)'
        );

        return $stmt->execute([date('Y-m-d H:i:s'), $nume, $email, $telefon, $subiect, $mesaj]);
    } catch (Throwable $e) {
        return false;
    }
}

function flash_set(string $message): void
{
    $_SESSION['flash'] = $message;
}

function flash_get(): string
{
    $message = (string) ($_SESSION['flash'] ?? '');
    unset($_SESSION['flash']);

    return $message;
}

function login_is_blocked(): bool
{
    return (int) ($_SESSION['login_wait'] ?? 0) > time();
}

function login_failed(): void
{
    $fails = (int) ($_SESSION['login_fails'] ?? 0) + 1;
    if ($fails >= 5) {
        $_SESSION['login_wait'] = time() + 60;
        $_SESSION['login_fails'] = 0;

        return;
    }
    $_SESSION['login_fails'] = $fails;
}

function login_succeeded(): void
{
    unset($_SESSION['login_fails'], $_SESSION['login_wait']);
}

function login_with_google(string $sub, string $email, string $name): array
{
    try {
        $pdo = db();
        $bySub = $pdo->prepare('SELECT id, email FROM clienti WHERE google_sub = ? LIMIT 1');
        $bySub->execute([$sub]);
        $row = $bySub->fetch();
        if (is_array($row)) {
            if (strcasecmp((string) $row['email'], $email) !== 0) {
                $taken = $pdo->prepare('SELECT id FROM clienti WHERE email = ? AND id <> ? LIMIT 1');
                $taken->execute([$email, $row['id']]);
                if ($taken->fetch() === false) {
                    $update = $pdo->prepare('UPDATE clienti SET email = ? WHERE id = ?');
                    $update->execute([$email, $row['id']]);
                }
            }

            return ['id' => (string) $row['id'], 'created' => false, 'error' => ''];
        }

        $byEmail = $pdo->prepare('SELECT id, google_sub FROM clienti WHERE email = ? LIMIT 1');
        $byEmail->execute([$email]);
        $existing = $byEmail->fetch();
        if (is_array($existing)) {
            $linked = (string) ($existing['google_sub'] ?? '');
            if ($linked !== '' && !hash_equals($linked, $sub)) {
                return ['id' => null, 'created' => false, 'error' => 'linked'];
            }
            $update = $pdo->prepare('UPDATE clienti SET google_sub = ? WHERE id = ? AND (google_sub IS NULL OR google_sub = ?)');
            $update->execute([$sub, $existing['id'], $sub]);

            return ['id' => (string) $existing['id'], 'created' => false, 'error' => ''];
        }

        $id = bin2hex(random_bytes(8));
        $display = $name;
        if (strlen($display) < 2) {
            $local = strstr($email, '@', true);
            $display = is_string($local) && strlen($local) >= 2 ? $local : 'Client';
        }
        $insert = $pdo->prepare(
            'INSERT INTO clienti (id, nume, email, telefon, parola, adresa, creat, google_sub)
             VALUES (?, ?, ?, \'\', ?, \'\', ?, ?)'
        );
        $insert->execute([
            $id,
            $display,
            $email,
            password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            date('Y-m-d H:i:s'),
            $sub,
        ]);
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            try {
                $again = $pdo->prepare('SELECT id FROM clienti WHERE google_sub = ? OR email = ? LIMIT 1');
                $again->execute([$sub, $email]);
                $found = $again->fetch();
                if (is_array($found)) {
                    return ['id' => (string) $found['id'], 'created' => false, 'error' => ''];
                }
            } catch (Throwable $retry) {
                return ['id' => null, 'created' => false, 'error' => 'save'];
            }
        }

        return ['id' => null, 'created' => false, 'error' => 'save'];
    } catch (Throwable $e) {
        return ['id' => null, 'created' => false, 'error' => db_failed() ? 'db' : 'save'];
    }

    return ['id' => $id, 'created' => true, 'error' => ''];
}
