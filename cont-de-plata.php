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
$replyTo = (string) ($order['email'] ?? $user['email'] ?? '');
if (($order['email'] ?? '') === '' && $replyTo !== '') {
    $order['email'] = $replyTo;
}
$invoiceNotice = '';
$invoiceError = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string) ($_POST['actiune'] ?? '') === 'email') {
    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
        $_SESSION['invoice_mail'] = ['ok' => false, 'text' => 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.'];
    } elseif (!smtp_ready()) {
        $_SESSION['invoice_mail'] = ['ok' => false, 'text' => 'Pentru trimitere, adaugă parola cutiei comenzi@casabradu.md în configurarea site-ului.'];
    } elseif (send_invoice_mail($order, $invoiceDate, $payer, $table, $replyTo)) {
        $_SESSION['invoice_mail'] = ['ok' => true, 'text' => 'Contul de plată a fost trimis.'];
    } else {
        $reason = site_mail_status();
        $_SESSION['invoice_mail'] = ['ok' => false, 'text' => $reason !== '' ? $reason : 'Emailul nu a putut fi trimis. Încearcă din nou peste câteva minute.'];
    }
    header('Location: ' . url('cont-de-plata.php?id=' . rawurlencode((string) ($order['id'] ?? ''))));
    exit;
}

$mailResult = $_SESSION['invoice_mail'] ?? null;
unset($_SESSION['invoice_mail']);
if (is_array($mailResult) && ($mailResult['text'] ?? '') !== '') {
    if (!empty($mailResult['ok'])) {
        $invoiceNotice = (string) $mailResult['text'];
    } else {
        $invoiceError = (string) $mailResult['text'];
    }
}

require __DIR__ . '/includes/factura-document.php';

