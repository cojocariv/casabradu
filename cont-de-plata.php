<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/factura.php';

$orderId = text_line((string) ($_GET['id'] ?? ''), 20);
$asAdmin = admin_logged_in();
if ($asAdmin) {
    $order = admin_order($orderId);
    $user = ['nume' => is_array($order) ? (string) ($order['nume'] ?? '') : ''];
} else {
    $user = require_client();
    $order = $orderId === '' ? null : client_order((string) $user['id'], $orderId);
}

if ($order === null) {
    header('Location: ' . url($asAdmin ? 'admin.php' : 'cabinet.php'));
    exit;
}

$stamp = strtotime((string) ($order['la'] ?? '')) ?: time();
$invoiceDate = date('d.m.Y', $stamp);
$requisites = (string) ($order['rechizite'] ?? '');
$table = invoice_table($order['linii'] ?? []);
$payer = [
    'nume' => requisite_value($requisites, 'Denumirea companiei'),
    'idno' => requisite_value($requisites, 'IDNO'),
    'tva' => requisite_value($requisites, 'Cod TVA'),
    'adresa' => requisite_value($requisites, 'Adresa juridică'),
];
if ($payer['nume'] === '') {
    $payer['nume'] = (string) ($user['nume'] ?? '');
}

$logo = asset('assets/img/logo.jpg?v=4');
$back = url($asAdmin ? 'admin.php' : 'cabinet.php');

require __DIR__ . '/includes/factura-document.php';

