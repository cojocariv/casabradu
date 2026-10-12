<?php

declare(strict_types=1);

function clean_line(string $value, int $max): string
{
    $value = trim(str_replace(["\r", "\n", "\0"], '', $value));

    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }

    return $value;
}

function clean_message(string $value, int $max): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = str_replace("\0", '', $value);
    $value = trim($value);

    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }

    return $value;
}

$errors = [];
$old = [
    'nume' => '',
    'email' => '',
    'telefon' => '',
    'subiect' => 'colaborare',
    'mesaj' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $preselect = (string) ($_GET['subiect'] ?? '');
    if (isset(subject_options()[$preselect])) {
        $old['subiect'] = $preselect;
    }

    return;
}

$old['nume'] = clean_line((string) ($_POST['nume'] ?? ''), 80);
$old['email'] = clean_line((string) ($_POST['email'] ?? ''), 120);
$old['telefon'] = clean_line((string) ($_POST['telefon'] ?? ''), 20);
$old['subiect'] = clean_line((string) ($_POST['subiect'] ?? ''), 40);
$old['mesaj'] = clean_message((string) ($_POST['mesaj'] ?? ''), 3000);

if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
    $errors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și trimite formularul din nou.';
}

if (clean_line((string) ($_POST['website'] ?? ''), 200) !== '') {
    header('Location: ' . url('contact.php?trimis=1'));
    exit;
}

$lastSent = (int) ($_SESSION['last_contact'] ?? 0);
if ($lastSent > 0 && (time() - $lastSent) < 20) {
    $errors['form'] = 'Te rugăm să aștepți câteva secunde înainte de un mesaj nou.';
}

if (strlen($old['nume']) < 2) {
    $errors['nume'] = 'Spune-ne cum te numești.';
}

if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Adresa de e-mail nu pare validă.';
}

if ($old['telefon'] !== '' && !preg_match('/^[0-9+\s().-]{6,20}$/', $old['telefon'])) {
    $errors['telefon'] = 'Numărul de telefon nu pare valid.';
}

if (!isset(subject_options()[$old['subiect']])) {
    $errors['subiect'] = 'Alege ce te interesează.';
}

if (strlen($old['mesaj']) < 10) {
    $errors['mesaj'] = 'Scrie-ne câteva detalii despre ce ai nevoie.';
}

if ($errors !== []) {
    return;
}

$subjects = subject_options();
$record = [
    'la' => date('c'),
    'nume' => $old['nume'],
    'email' => $old['email'],
    'telefon' => $old['telefon'],
    'subiect' => $subjects[$old['subiect']],
    'mesaj' => $old['mesaj'],
];

$storage = dirname(__DIR__) . '/storage';
if (!is_dir($storage)) {
    mkdir($storage, 0755, true);
}

$saved = file_put_contents(
    $storage . '/mesaje.jsonl',
    json_encode($record, JSON_UNESCAPED_UNICODE) . "\n",
    FILE_APPEND | LOCK_EX
) !== false;

$safeName = str_replace(["\r", "\n"], ' ', $old['nume']);
$safeEmail = str_replace(["\r", "\n"], '', $old['email']);
$body = "Mesaj nou de pe " . SITE_DOMAIN . "\n\n"
    . "Nume: {$old['nume']}\n"
    . "Email: {$old['email']}\n"
    . "Telefon: " . ($old['telefon'] !== '' ? $old['telefon'] : '—') . "\n"
    . "Subiect: {$subjects[$old['subiect']]}\n\n"
    . $old['mesaj'] . "\n";
$headers = "MIME-Version: 1.0\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "From: Casa Bradu Caffe <" . SITE_EMAIL . ">\r\n"
    . "Reply-To: {$safeName} <{$safeEmail}>\r\n";

$mailed = function_exists('mail') && @mail(
    SITE_EMAIL,
    'Mesaj site: ' . $subjects[$old['subiect']],
    $body,
    $headers
);

if (!$saved && !$mailed) {
    $errors['form'] = 'Mesajul nu a putut fi trimis. Scrie-ne direct la ' . SITE_EMAIL . '.';
    return;
}

$_SESSION['last_contact'] = time();
header('Location: ' . url('contact.php?trimis=1'));
exit;
