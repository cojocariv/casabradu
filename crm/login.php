<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (crm_logged_in()) {
    header('Location: ' . crm_url());
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!crm_csrf_ok((string) ($_POST['csrf'] ?? ''))) {
        $error = 'Sesiunea a expirat. Reîncearcă.';
    } elseif (crm_text((string) ($_POST['website'] ?? ''), 200) !== '') {
        header('Location: ' . crm_url('login.php'));
        exit;
    } elseif (!crm_admin_configured()) {
        $error = 'Contul admin nu este configurat în config.php.';
    } elseif (crm_login_blocked()) {
        $error = 'Prea multe încercări. Așteaptă un minut.';
    } elseif (!crm_login(crm_text((string) ($_POST['utilizator'] ?? ''), 80), (string) ($_POST['parola'] ?? ''))) {
        crm_login_failed();
        $error = 'Utilizator sau parolă greșită.';
    } else {
        crm_login_ok();
        header('Location: ' . crm_url());
        exit;
    }
}

crm_header('Autentificare', 'login');
?>
<section class="crm-card crm-login">
    <h1>Intră în CRM</h1>
    <p class="crm-muted">Aceeași autentificare ca la admin-ul site-ului.</p>
    <?php if ($error !== ''): ?>
        <p class="crm-flash crm-flash-error" role="alert"><?= crm_e($error) ?></p>
    <?php endif; ?>
    <form class="crm-form" method="post" action="<?= crm_e(crm_url('login.php')) ?>">
        <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <div class="hp" aria-hidden="true" style="position:absolute;left:-9999px">
            <label>Site <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <label>Utilizator
            <input type="text" name="utilizator" required autocomplete="username">
        </label>
        <label>Parolă
            <input type="password" name="parola" required autocomplete="current-password">
        </label>
        <button class="crm-btn" type="submit">Intră</button>
    </form>
</section>
<?php crm_footer(); ?>
