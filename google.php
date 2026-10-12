<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$next = safe_next((string) ($_POST['next'] ?? 'cabinet.php'));
$from = (string) ($_POST['from'] ?? 'autentificare.php');
if ($from !== 'inregistrare.php') {
    $from = 'autentificare.php';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . url('autentificare.php'));
    exit;
}

if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
    auth_error_set('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.');
    header('Location: ' . url($from . '?next=' . rawurlencode($next)));
    exit;
}

if (!google_enabled()) {
    auth_error_set('Intrarea cu Google nu este configurată încă.');
    header('Location: ' . url($from . '?next=' . rawurlencode($next)));
    exit;
}

$claims = google_verify_id_token((string) ($_POST['credential'] ?? ''));
if (!($claims['ok'] ?? false)) {
    $message = 'Contul Google nu a putut fi verificat. Încearcă din nou.';
    if (($claims['error'] ?? '') === 'email') {
        $message = 'Google nu a confirmat adresa de email. Confirm-o în contul Google și încearcă din nou.';
    }
    auth_error_set($message);
    header('Location: ' . url($from . '?next=' . rawurlencode($next)));
    exit;
}

$result = login_with_google((string) $claims['sub'], (string) $claims['email'], (string) $claims['name']);
if (!is_string($result['id']) || $result['id'] === '') {
    if (($result['error'] ?? '') === 'db') {
        $message = 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.';
    } elseif (($result['error'] ?? '') === 'linked') {
        $message = 'Acest email este deja legat de alt cont Google. Intră cu parola sau cu contul folosit prima dată.';
    } else {
        $message = 'Contul nu a putut fi creat. Încearcă din nou.';
    }
    auth_error_set($message);
    header('Location: ' . url($from . '?next=' . rawurlencode($next)));
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = $result['id'];
login_succeeded();
if (!empty($result['created'])) {
    flash_set('Contul a fost creat cu Google. Poți plasa prima comandă.');
}
header('Location: ' . url($next));
exit;
