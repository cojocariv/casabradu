<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/factura.php';
require_once __DIR__ . '/includes/act.php';

$orderId = text_line((string) ($_GET['id'] ?? ''), 20);
$asAdmin = admin_logged_in();
if ($asAdmin) {
    $order = admin_order($orderId);
} else {
    $user = require_client();
    $order = $orderId === '' ? null : client_order((string) $user['id'], $orderId);
    if (is_array($order)) {
        $order['nume'] = (string) ($user['nume'] ?? '');
        $order['email'] = (string) ($user['email'] ?? '');
        $order['telefon'] = (string) ($user['telefon'] ?? '');
    }
}

if ($order === null) {
    header('Location: ' . url($asAdmin ? 'admin.php' : 'cabinet.php'));
    exit;
}

$stamp = strtotime((string) ($order['la'] ?? '')) ?: time();
$actDate = date('d.m.Y', $stamp);
$actPlace = 'mun. Chișinău, Republica Moldova';
$table = invoice_table($order['linii'] ?? []);
$supplier = act_supplier();
$receiver = act_receiver_from_order($order);
$back = url($asAdmin ? 'admin.php' : 'cabinet.php');

require __DIR__ . '/includes/act-document.php';
