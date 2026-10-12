<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$next = safe_next((string) ($_GET['next'] ?? $_POST['next'] ?? 'cabinet.php'));

if (current_user() !== null) {
    header('Location: ' . url($next));
    exit;
}

$errors = [];
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = text_line((string) ($_POST['email'] ?? ''), 120);
    $password = (string) ($_POST['parola'] ?? '');

    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
        $errors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
    } elseif (text_line((string) ($_POST['website'] ?? ''), 200) !== '') {
        header('Location: ' . url($next));
        exit;
    } elseif (login_is_blocked()) {
        $errors['form'] = 'Prea multe încercări. Așteaptă un minut și încearcă din nou.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors['form'] = 'Email sau parolă incorectă.';
        login_failed();
    } elseif (!login_client($email, $password)) {
        if (db_failed()) {
            $errors['form'] = 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.';
        } else {
            $errors['form'] = 'Email sau parolă incorectă.';
            login_failed();
        }
    } else {
        login_succeeded();
        header('Location: ' . url($next));
        exit;
    }
}

$authError = auth_error_pull();
$page = 'autentificare';
$noindex = true;
$pageTitle = 'Intră în cont';
$pageDescription = 'Intră în cabinetul Casa Bradu Caffè ca să vezi comenzile și să trimiți una nouă.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Cabinet</p>
        <h1>Intră în cont.</h1>
        <p class="lede">De aici trimiți comenzi și le urmărești. Dacă ești pentru prima dată, îți faci contul în câteva clipe.</p>
    </div>
</section>

<section class="section">
    <div class="wrap auth-layout">
        <div class="contact-card">
            <?php if ($authError !== ''): ?>
                <p class="notice notice-error" role="alert"><?= e($authError) ?></p>
            <?php endif; ?>
            <?php require __DIR__ . '/includes/google-button.php'; ?>
            <form class="contact-form" method="post" action="<?= e(url('autentificare.php')) ?>" accept-charset="UTF-8">
                <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <div class="hp" aria-hidden="true">
                    <label>Site <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <?php if (!empty($errors['form'])): ?>
                    <p class="notice notice-error" role="alert"><?= e($errors['form']) ?></p>
                <?php endif; ?>

                <label for="email">Email</label>
                <input id="email" name="email" type="email" required maxlength="120" autocomplete="email" value="<?= e($email) ?>">

                <label for="parola">Parolă</label>
                <input id="parola" name="parola" type="password" required maxlength="72" autocomplete="current-password">

                <button class="btn btn-dark" type="submit">Intră</button>
            </form>
        </div>
        <aside class="contact-aside">
            <p class="kicker">Client nou</p>
            <h2>Creează un cont.</h2>
            <p>Îți trebuie numele, emailul, telefonul și o parolă. Apoi poți comanda direct.</p>
            <a class="btn btn-line" href="<?= e(url('inregistrare.php?next=' . rawurlencode($next))) ?>">Înregistrează-te</a>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
