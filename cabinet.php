<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = require_client();
$products = require __DIR__ . '/includes/products.php';
$catalog = [];
foreach ($products as $item) {
    $catalog[$item['slug']] = $item;
}

$orderErrors = [];
$passwordErrors = [];
$oldQty = [];
foreach ($catalog as $slug => $item) {
    $oldQty[$slug] = 0;
}
$preselect = (string) ($_GET['produs'] ?? '');
if (isset($catalog[$preselect]) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $oldQty[$preselect] = 1;
}
$oldAddress = (string) ($user['adresa'] ?? '');
$oldNote = '';
$flash = flash_get();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form = (string) ($_POST['formular'] ?? '');
    $csrfOk = hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));

    if (!$csrfOk) {
        if ($form === 'parola') {
            $passwordErrors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
        } else {
            $orderErrors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
        }
    } elseif ($form === 'parola') {
        $current = (string) ($_POST['parola_veche'] ?? '');
        $next = (string) ($_POST['parola_noua'] ?? '');
        $confirm = (string) ($_POST['parola_noua2'] ?? '');
        if (strlen($next) < 8) {
            $passwordErrors['parola'] = 'Parola nouă trebuie să aibă cel puțin 8 caractere.';
        } elseif (strlen($next) > 72) {
            $passwordErrors['parola'] = 'Parola nouă este prea lungă.';
        }
        if ($next !== $confirm) {
            $passwordErrors['parola2'] = 'Parolele nu coincid.';
        }
        if ($passwordErrors === [] && !change_client_password((string) $user['id'], $current, $next)) {
            $passwordErrors['parola'] = 'Parola actuală nu este corectă.';
        }
        if ($passwordErrors === []) {
            flash_set('Parola a fost schimbată.');
            header('Location: ' . url('cabinet.php'));
            exit;
        }
    } else {
        $posted = $_POST['qty'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }
        $lines = [];
        foreach ($catalog as $slug => $item) {
            $raw = trim((string) ($posted[$slug] ?? '0'));
            if ($raw === '') {
                $raw = '0';
            }
            if (preg_match('/^\d{1,3}$/', $raw) !== 1) {
                $orderErrors['qty'] = 'Cantitatea este un număr între 0 și 999.';
                $qty = 0;
            } else {
                $qty = (int) $raw;
            }
            $oldQty[$slug] = $qty;
            if ($qty > 0) {
                $lines[] = [
                    'slug' => $slug,
                    'titlu' => $item['title'],
                    'cantitate' => $qty,
                ];
            }
        }
        $oldAddress = text_block((string) ($_POST['adresa'] ?? ''), 300);
        $oldNote = text_block((string) ($_POST['nota'] ?? ''), 1000);
        if ($lines === []) {
            $orderErrors['qty'] = 'Alege cel puțin un produs și o cantitate.';
        }
        if (strlen($oldAddress) < 5) {
            $orderErrors['adresa'] = 'Scrie adresa de livrare sau localitatea.';
        }
        if ($orderErrors === []) {
            $order = place_order($user, $lines, $oldAddress, $oldNote);
            if ($order === null) {
                $orderErrors['form'] = db_failed()
                    ? 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.'
                    : 'Comanda nu a putut fi salvată. Încearcă din nou sau scrie-ne la ' . SITE_EMAIL . '.';
            } else {
                flash_set('Comanda ' . $order['id'] . ' a fost înregistrată. Te contactăm pentru confirmare.');
                header('Location: ' . url('cabinet.php'));
                exit;
            }
        }
    }
}

$orders = client_orders((string) $user['id']);
$page = 'cabinet';
$noindex = true;
$pageTitle = 'Cabinet';
$pageDescription = 'Cabinetul tău Casa Bradu Caffè: comenzi de cafea și aparate.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Cabinet</p>
        <h1>Bun venit, <?= e((string) $user['nume']) ?>.</h1>
        <p class="lede">Trimite o comandă de cialde, capsule, boabe sau aparate. O înregistrăm și te contactăm ca să o confirmăm.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($flash !== ''): ?>
            <p class="notice" role="status"><?= e($flash) ?></p>
        <?php endif; ?>

        <div class="cabinet-grid">
            <div class="contact-card" id="comanda">
                <p class="kicker">Comandă nouă</p>
                <h2>Ce vrei să aducem.</h2>
                <form class="contact-form" method="post" action="<?= e(url('cabinet.php')) ?>" accept-charset="UTF-8">
                    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                    <input type="hidden" name="formular" value="comanda">
                    <?php if (!empty($orderErrors['form'])): ?>
                        <p class="notice notice-error" role="alert"><?= e($orderErrors['form']) ?></p>
                    <?php endif; ?>

                    <div class="order-lines">
                        <?php foreach ($catalog as $slug => $item): ?>
                            <div class="order-line">
                                <label for="qty-<?= e($slug) ?>"><?= e($item['title']) ?> <span><?= e($item['kicker']) ?></span></label>
                                <input id="qty-<?= e($slug) ?>" name="qty[<?= e($slug) ?>]" type="number" min="0" max="999" step="1" inputmode="numeric" value="<?= (int) $oldQty[$slug] ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($orderErrors['qty'])): ?><small class="field-error"><?= e($orderErrors['qty']) ?></small><?php endif; ?>

                    <label for="adresa">Adresă de livrare</label>
                    <textarea id="adresa" name="adresa" required minlength="5" maxlength="300" rows="3" placeholder="Localitate, stradă, alte detalii"><?= e($oldAddress) ?></textarea>
                    <?php if (isset($orderErrors['adresa'])): ?><small class="field-error"><?= e($orderErrors['adresa']) ?></small><?php endif; ?>

                    <label for="nota">Mențiune <span>(opțional)</span></label>
                    <textarea id="nota" name="nota" maxlength="1000" rows="3" placeholder="Cutii, kilogram sau orice detaliu despre comandă"><?= e($oldNote) ?></textarea>

                    <button class="btn btn-dark" type="submit">Trimite comanda</button>
                    <p class="form-note">Cantitatea 0 înseamnă că produsul nu intră în comandă. Prețul îl confirmăm împreună.</p>
                </form>
            </div>

            <aside class="contact-aside">
                <p class="kicker">Contul tău</p>
                <h2><?= e((string) $user['nume']) ?></h2>
                <ul class="ticks">
                    <li><?= e((string) $user['email']) ?></li>
                    <?php if ((string) ($user['telefon'] ?? '') !== ''): ?>
                        <li><?= e((string) $user['telefon']) ?></li>
                    <?php endif; ?>
                </ul>
                <form method="post" action="<?= e(url('iesire.php')) ?>">
                    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                    <button class="btn btn-line" type="submit">Ieși din cont</button>
                </form>

                <details class="account-fold" <?= $passwordErrors !== [] ? 'open' : '' ?>>
                    <summary>Schimbă parola</summary>
                    <form class="contact-form" method="post" action="<?= e(url('cabinet.php')) ?>" accept-charset="UTF-8">
                        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                        <input type="hidden" name="formular" value="parola">
                        <?php if (!empty($passwordErrors['form'])): ?>
                            <p class="notice notice-error" role="alert"><?= e($passwordErrors['form']) ?></p>
                        <?php endif; ?>
                        <label for="parola_veche">Parola actuală</label>
                        <input id="parola_veche" name="parola_veche" type="password" required autocomplete="current-password" <?= isset($passwordErrors['parola']) ? 'aria-invalid="true"' : '' ?>>
                        <?php if (isset($passwordErrors['parola'])): ?><small class="field-error"><?= e($passwordErrors['parola']) ?></small><?php endif; ?>
                        <label for="parola_noua">Parola nouă</label>
                        <input id="parola_noua" name="parola_noua" type="password" required minlength="8" maxlength="72" autocomplete="new-password">
                        <label for="parola_noua2">Confirmă parola nouă</label>
                        <input id="parola_noua2" name="parola_noua2" type="password" required minlength="8" maxlength="72" autocomplete="new-password" <?= isset($passwordErrors['parola2']) ? 'aria-invalid="true"' : '' ?>>
                        <?php if (isset($passwordErrors['parola2'])): ?><small class="field-error"><?= e($passwordErrors['parola2']) ?></small><?php endif; ?>
                        <button class="btn btn-dark" type="submit">Salvează parola</button>
                    </form>
                </details>
            </aside>
        </div>

        <div class="orders-block">
            <p class="kicker">Comenzile tale</p>
            <h2>Istoric</h2>
            <?php if ($orders === []): ?>
                <p class="lede">Nu ai comenzi încă. Prima o poți trimite din formular.</p>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($orders as $order): ?>
                        <article class="order-card">
                            <div class="order-top">
                                <h3><?= e((string) ($order['id'] ?? '')) ?></h3>
                                <span class="status">Înregistrată</span>
                            </div>
                            <p class="order-date"><?= e(date('d.m.Y H:i', strtotime((string) ($order['la'] ?? 'now')) ?: time())) ?></p>
                            <ul class="ticks">
                                <?php foreach (($order['linii'] ?? []) as $line): ?>
                                    <?php if (!is_array($line)) { continue; } ?>
                                    <li><?= e((string) ($line['titlu'] ?? '')) ?> · <?= (int) ($line['cantitate'] ?? 0) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (($order['adresa'] ?? '') !== ''): ?>
                                <p><?= e((string) $order['adresa']) ?></p>
                            <?php endif; ?>
                            <?php if (($order['nota'] ?? '') !== ''): ?>
                                <p><?= e((string) $order['nota']) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
