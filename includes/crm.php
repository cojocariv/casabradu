<?php

declare(strict_types=1);

function crm_catalog_index(): array
{
    $index = [];
    foreach (product_price_rows() as $row) {
        $index[product_price_field($row)] = $row;
    }

    return $index;
}

function crm_stock_add(string $field, int $qty, string $note): string
{
    $index = crm_catalog_index();
    if (!isset($index[$field])) {
        return 'Alege produsul primit.';
    }
    if ($qty < 1 || $qty > 99999) {
        return 'Cantitatea trebuie să fie între 1 și 99999.';
    }
    $row = $index[$field];

    try {
        $stmt = db()->prepare(
            'INSERT INTO stoc_miscari (la, slug, optiune, cantitate, nota) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            date('Y-m-d H:i:s'),
            $row['slug'],
            $row['optiune'],
            $qty,
            $note,
        ]);
    } catch (Throwable $e) {
        return db_failed()
            ? 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.'
            : 'Intrarea nu a putut fi notată.';
    }

    return '';
}

function crm_stock_delete(int $id): bool
{
    if ($id < 1) {
        return false;
    }

    try {
        $stmt = db()->prepare('DELETE FROM stoc_miscari WHERE id = ?');

        return $stmt->execute([$id]);
    } catch (Throwable $e) {
        return false;
    }
}

function crm_stock_sheet(): array
{
    $rows = [];
    foreach (crm_catalog_index() as $field => $row) {
        $rows[$field] = $row + ['comandate' => 0, 'intrate' => 0, 'vandute' => 0];
    }
    $history = [];

    try {
        $pdo = db();
        $lines = $pdo->query(
            'SELECT l.slug, l.titlu, l.cantitate, c.status
             FROM comenzi_linii l
             INNER JOIN comenzi c ON c.id = l.comanda_id'
        );
        if ($lines !== false) {
            foreach ($lines as $line) {
                $status = (string) ($line['status'] ?? '');
                if ($status === 'anulata') {
                    continue;
                }
                $qty = (int) ($line['cantitate'] ?? 0);
                if ($qty < 1) {
                    continue;
                }
                $key = (string) ($line['slug'] ?? '');
                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'slug' => $key,
                        'optiune' => '',
                        'produs' => (string) ($line['titlu'] ?? $key),
                        'varianta' => '',
                        'comandate' => 0,
                        'intrate' => 0,
                        'vandute' => 0,
                    ];
                }
                if ($status === 'livrata') {
                    $rows[$key]['vandute'] += $qty;
                } else {
                    $rows[$key]['comandate'] += $qty;
                }
            }
        }

        $moves = $pdo->query(
            'SELECT id, la, slug, optiune, cantitate, nota
             FROM stoc_miscari
             ORDER BY la DESC, id DESC'
        );
        if ($moves !== false) {
            foreach ($moves as $move) {
                $key = product_price_field([
                    'slug' => (string) $move['slug'],
                    'optiune' => (string) $move['optiune'],
                ]);
                $qty = (int) ($move['cantitate'] ?? 0);
                if (isset($rows[$key])) {
                    $rows[$key]['intrate'] += $qty;
                }
                $named = $rows[$key] ?? null;
                $history[] = [
                    'id' => (int) $move['id'],
                    'la' => (string) $move['la'],
                    'produs' => is_array($named) ? (string) $named['produs'] : (string) $move['slug'],
                    'varianta' => is_array($named) ? (string) $named['varianta'] : (string) $move['optiune'],
                    'cantitate' => $qty,
                    'nota' => (string) ($move['nota'] ?? ''),
                ];
            }
        }
    } catch (Throwable $e) {
        return ['rows' => array_values($rows), 'istoric' => [], 'ok' => false];
    }

    return ['rows' => array_values($rows), 'istoric' => $history, 'ok' => true];
}

function crm_clients(): array
{
    try {
        $pdo = db();
        $list = $pdo->query(
            'SELECT id, nume, email, telefon, rechizite FROM clienti ORDER BY nume'
        );
        if ($list === false) {
            return ['rows' => [], 'ok' => false];
        }
        $rows = [];
        foreach ($list as $client) {
            $rows[(string) $client['id']] = [
                'id' => (string) $client['id'],
                'nume' => (string) $client['nume'],
                'email' => (string) $client['email'],
                'telefon' => (string) $client['telefon'],
                'companie' => requisite_value((string) ($client['rechizite'] ?? ''), 'Denumirea companiei'),
                'comenzi' => 0,
                'ultima' => '',
                'total' => null,
                'complet' => true,
                'are_comenzi' => false,
            ];
        }

        $orders = $pdo->query(
            'SELECT c.id, c.user_id, c.la, c.status, l.cantitate, l.pret
             FROM comenzi c
             LEFT JOIN comenzi_linii l ON l.comanda_id = c.id
             ORDER BY c.la DESC'
        );
        if ($orders === false) {
            return ['rows' => array_values($rows), 'ok' => false];
        }
        $seen = [];
        foreach ($orders as $order) {
            $userId = (string) ($order['user_id'] ?? '');
            if (!isset($rows[$userId])) {
                continue;
            }
            $orderId = (string) $order['id'];
            if (!isset($seen[$orderId])) {
                $seen[$orderId] = true;
                $rows[$userId]['comenzi']++;
                $stamp = (string) ($order['la'] ?? '');
                if ($rows[$userId]['ultima'] === '' || $stamp > $rows[$userId]['ultima']) {
                    $rows[$userId]['ultima'] = $stamp;
                }
            }
            if ((string) ($order['status'] ?? '') === 'anulata' || $order['cantitate'] === null) {
                continue;
            }
            $rows[$userId]['are_comenzi'] = true;
            $pret = $order['pret'];
            $qty = (int) $order['cantitate'];
            if (!is_numeric($pret) || (float) $pret <= 0 || $qty < 1) {
                $rows[$userId]['complet'] = false;
                continue;
            }
            $rows[$userId]['total'] = (float) ($rows[$userId]['total'] ?? 0) + ((float) $pret * $qty);
        }

        $list = array_values($rows);
        usort($list, static function (array $a, array $b): int {
            if ($a['ultima'] === '' && $b['ultima'] !== '') {
                return 1;
            }
            if ($b['ultima'] === '' && $a['ultima'] !== '') {
                return -1;
            }
            if ($a['ultima'] !== $b['ultima']) {
                return $a['ultima'] < $b['ultima'] ? 1 : -1;
            }

            return strcasecmp($a['nume'], $b['nume']);
        });

        return ['rows' => $list, 'ok' => true];
    } catch (Throwable $e) {
        return ['rows' => [], 'ok' => false];
    }
}
