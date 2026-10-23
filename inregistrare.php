<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    header('Location: ' . url('cabinet.php'));
    exit;
}

$errors = [];
$old = ['nume' => '', 'email' => '', 'telefon' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $old['nume'] = text_line((string) ($_POST['nume'] ?? ''), 80);
    $old['email'] = text_line((string) ($_POST['email'] ?? ''), 120);
    $old['telefon'] = text_line((string) ($_POST['telefon'] ?? ''), 20);
    $password = (string) ($_POST['parola'] ?? '');
    $confirm = (string) ($_POST['parola2'] ?? '');

    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
        $errors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
    }
    if (text_line((string) ($_POST['website'] ?? ''), 200) !== '') {
        header('Location: ' . url('autentificare.php'));
        exit;
    }
    if (strlen($old['nume']) < 2) {
        $errors['nume'] = 'Spune-ne cum te numești.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Adresa de e-mail nu pare validă.';
    }
    if (!preg_match('/^[0-9+\s().-]{6,20}$/', $old['telefon'])) {
        $errors['telefon'] = 'Numărul de telefon nu pare valid.';
    }
    if (strlen($password) < 8) {
        $errors['parola'] = 'Parola trebuie să aibă cel puțin 8 caractere.';
    } elseif (strlen($password) > 72) {
        $errors['parola'] = 'Parola este prea lungă.';
    }
    if ($password !== $confirm) {
        $errors['parola2'] = 'Parolele nu coincid.';
    }

    if ($errors === []) {
        $result = create_client($old['nume'], $old['email'], $old['telefon'], $password);
        if ($result['duplicate']) {
            $errors['email'] = 'Există deja un cont cu acest email. Intră în cont.';
        } elseif ($result['id'] === null) {
            $errors['form'] = db_failed()
                ? 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.'
                : 'Contul nu a putut fi creat. Încearcă din nou.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $result['id'];
            flash_set('Contul a fost creat. Poți plasa prima comandă.');
            header('Location: ' . url(safe_next((string) ($_POST['next'] ?? 'cabinet.php'))));
            exit;
        }
    }
}

$next = safe_next((string) ($_GET['next'] ?? $_POST['next'] ?? 'cabinet.php'));
$authError = auth_error_pull();
$page = 'inregistrare';
$noindex = true;
$pageTitle = 'Creează cont';
$pageDescription = 'Creează un cont Casa Bradu Caffè ca să plasezi comenzi de cialde, capsule, boabe și aparate.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow"><?= tr('Cont nou') ?></p>
        <h1><?= tr('Înregistrează-te.') ?></h1>
        <p class="lede"><?= tr('Poți intra cu Google sau îți creezi contul cu email. Apoi comanzi cialde, capsule și boabe, sau un aparat — în comodat sau la vânzare.') ?></p>
    </div>
</section>

<section class="section">
    <div class="wrap auth-layout">
        <div class="contact-card">
            <?php if ($authError !== ''): ?>
                    <p class="notice notice-error" role="alert"><?= e(t($authError)) ?></p>
            <?php endif; ?>
            <?php require __DIR__ . '/includes/google-button.php'; ?>
            <form class="contact-form" method="post" action="<?= e(url('inregistrare.php')) ?>" accept-charset="UTF-8">
                <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <div class="hp" aria-hidden="true">
                    <label>Site <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <?php if (!empty($errors['form'])): ?>
                    <p class="notice notice-error" role="alert"><?= e(t($errors['form'])) ?></p>
                <?php endif; ?>

                <label for="nume"><?= tr('Nume') ?></label>
                <input id="nume" name="nume" type="text" required minlength="2" maxlength="80" autocomplete="name" value="<?= e($old['nume']) ?>" <?= isset($errors['nume']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['nume'])): ?><small class="field-error"><?= e(t($errors['nume'])) ?></small><?php endif; ?>

                <label for="email">Email</label>
                <input id="email" name="email" type="email" required maxlength="120" autocomplete="email" value="<?= e($old['email']) ?>" <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['email'])): ?><small class="field-error"><?= e(t($errors['email'])) ?></small><?php endif; ?>

                <label for="telefon"><?= tr('Telefon') ?></label>
                <input id="telefon" name="telefon" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" value="<?= e($old['telefon']) ?>" placeholder="+373" <?= isset($errors['telefon']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['telefon'])): ?><small class="field-error"><?= e(t($errors['telefon'])) ?></small><?php endif; ?>

                <label for="parola"><?= tr('Parolă') ?></label>
                <input id="parola" name="parola" type="password" required minlength="8" maxlength="72" autocomplete="new-password" <?= isset($errors['parola']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['parola'])): ?><small class="field-error"><?= e(t($errors['parola'])) ?></small><?php endif; ?>

                <label for="parola2"><?= tr('Confirmă parola') ?></label>
                <input id="parola2" name="parola2" type="password" required minlength="8" maxlength="72" autocomplete="new-password" <?= isset($errors['parola2']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['parola2'])): ?><small class="field-error"><?= e(t($errors['parola2'])) ?></small><?php endif; ?>

                <button class="btn btn-dark" type="submit"><?= tr('Creează contul') ?></button>
                <p class="form-note"><?= tr('Folosim datele ca să îți confirmăm comenzile.') ?></p>
            </form>
        </div>
        <aside class="contact-aside">
            <p class="kicker"><?= tr('Ai deja cont?') ?></p>
            <h2><?= tr('Intră și continuă comanda.') ?></h2>
            <p><?= tr('Contul păstrează comenzile tale și adresa folosită ultima dată.') ?></p>
            <a class="btn btn-line" href="<?= e(url('autentificare.php?next=' . rawurlencode($next))) ?>"><?= tr('Intră în cont') ?></a>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
