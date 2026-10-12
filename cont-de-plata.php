<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/factura.php';

$user = require_client();
$orderId = text_line((string) ($_GET['id'] ?? ''), 20);
$order = $orderId === '' ? null : client_order((string) $user['id'], $orderId);

if ($order === null) {
    header('Location: ' . url('cabinet.php'));
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

$logo = asset('assets/img/logo.jpg');
$back = url('cabinet.php');

require __DIR__ . '/includes/factura-document.php';

