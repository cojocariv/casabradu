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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['actiune'] ?? '');
    if ($action === 'pdf') {
        if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
            header('Location: ' . url('act-predare.php?id=' . rawurlencode((string) ($order['id'] ?? ''))));
            exit;
        }
        $pdf = act_pdf($order, $actDate, $actPlace, $supplier, $receiver, $table);
        $filename = 'act-predare-primire-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($order['id'] ?? 'casa-bradu')) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) strlen($pdf));
        echo $pdf;
        exit;
    }
}

require __DIR__ . '/includes/act-document.php';
