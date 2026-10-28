<?php

declare(strict_types=1);

$old = array_merge([
    'nume' => '',
    'email' => '',
    'telefon' => '',
    'subiect' => 'colaborare',
    'mesaj' => '',
], $old ?? []);
$errors = $errors ?? [];
$theme = $theme ?? 'light';
?>
<form class="contact-form contact-form--<?= e($theme) ?>" method="post" action="<?= e(url('contact.php')) ?>" accept-charset="UTF-8">
    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
    <div class="hp" aria-hidden="true">
        <label>Site <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <?php if (!empty($errors['form'])): ?>
        <p class="notice notice-error" role="alert"><?= e(t($errors['form'])) ?></p>
    <?php endif; ?>

    <label for="nume"><?= tr('Nume') ?></label>
    <input id="nume" name="nume" type="text" required minlength="2" maxlength="80" autocomplete="name" value="<?= e($old['nume']) ?>" placeholder="<?= tr('Numele tău') ?>" <?= isset($errors['nume']) ? 'aria-invalid="true" aria-describedby="nume-error"' : '' ?>>
    <?php if (isset($errors['nume'])): ?><small class="field-error" id="nume-error"><?= e(t($errors['nume'])) ?></small><?php endif; ?>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" required maxlength="120" autocomplete="email" value="<?= e($old['email']) ?>" placeholder="adresa@email.com" <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
    <?php if (isset($errors['email'])): ?><small class="field-error" id="email-error"><?= e(t($errors['email'])) ?></small><?php endif; ?>

    <label for="telefon"><?= tr('Telefon') ?> <span><?= tr('(opțional)') ?></span></label>
    <input id="telefon" name="telefon" type="tel" inputmode="tel" maxlength="20" autocomplete="tel" value="<?= e($old['telefon']) ?>" placeholder="+373" <?= isset($errors['telefon']) ? 'aria-invalid="true" aria-describedby="telefon-error"' : '' ?>>
    <?php if (isset($errors['telefon'])): ?><small class="field-error" id="telefon-error"><?= e(t($errors['telefon'])) ?></small><?php endif; ?>

    <label for="subiect"><?= tr('Mă interesează') ?></label>
    <select id="subiect" name="subiect" <?= isset($errors['subiect']) ? 'aria-invalid="true"' : '' ?>>
        <?php foreach (subject_options() as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $old['subiect'] === $value ? 'selected' : '' ?>><?= tr($label) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors['subiect'])): ?><small class="field-error"><?= e(t($errors['subiect'])) ?></small><?php endif; ?>

    <label for="mesaj"><?= tr('Mesaj') ?></label>
    <textarea id="mesaj" name="mesaj" required minlength="10" maxlength="3000" rows="5" placeholder="<?= tr('Ce format cauți și pentru cine este cafeaua?') ?>" <?= isset($errors['mesaj']) ? 'aria-invalid="true" aria-describedby="mesaj-error"' : '' ?>><?= e($old['mesaj']) ?></textarea>
    <?php if (isset($errors['mesaj'])): ?><small class="field-error" id="mesaj-error"><?= e(t($errors['mesaj'])) ?></small><?php endif; ?>

    <button class="btn btn-send" type="submit"><?= tr('Trimite') ?></button>
    <p class="form-note"><?= tr('Folosim datele doar ca să îți răspundem. Vezi') ?> <a href="<?= e(url('confidentialitate.php')) ?>"><?= tr('Politica de confidențialitate') ?></a>.</p>
</form>
