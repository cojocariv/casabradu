<?php

declare(strict_types=1);

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
