<?php

declare(strict_types=1);

if (!google_enabled()) {
    return;
}

$googleNext = safe_next((string) ($next ?? 'cabinet.php'));
$googleFrom = ($page ?? '') === 'inregistrare' ? 'inregistrare.php' : 'autentificare.php';
?>
<div class="google-auth">
    <div id="g_id_onload"
         data-client_id="<?= e(google_client_id()) ?>"
         data-callback="casaGoogleAuth"
         data-nonce="<?= e(google_nonce()) ?>"
         data-context="<?= $googleFrom === 'inregistrare.php' ? 'signup' : 'signin' ?>"
         data-ux_mode="popup"
         data-auto_prompt="false"
         data-locale="ro"></div>
    <div class="g_id_signin"
         data-type="standard"
         data-shape="pill"
         data-theme="outline"
         data-text="continue_with"
         data-size="large"
         data-logo_alignment="left"
         data-width="320"></div>
    <form id="google-auth-form" method="post" action="<?= e(url('google.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <input type="hidden" name="next" value="<?= e($googleNext) ?>">
        <input type="hidden" name="from" value="<?= e($googleFrom) ?>">
        <input type="hidden" name="credential" value="">
    </form>
    <p class="auth-or"><span>sau cu email</span></p>
</div>
<script>
function casaGoogleAuth(response) {
    var form = document.getElementById('google-auth-form');
    if (!form || !response || !response.credential) {
        return;
    }
    form.credential.value = response.credential;
    form.submit();
}
</script>
<script src="https://accounts.google.com/gsi/client" async defer></script>
