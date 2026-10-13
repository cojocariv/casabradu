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
$requisiteErrors = [];
$oldQty = [];
foreach ($catalog as $slug => $item) {
    $oldQty[$slug] = 0;
}
$preselect = (string) ($_GET['produs'] ?? '');
if (isset($catalog[$preselect]) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $oldQty[$preselect] = 1;
}
$oldAddress = (string) ($user['adresa'] ?? '');
$oldRequisites = trim((string) ($user['rechizite'] ?? ''));
if ($oldRequisites === '') {
    $oldRequisites = requisite_template();
}
$oldNote = '';
$flash = flash_get();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form = (string) ($_POST['formular'] ?? '');
    $csrfOk = hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));

    if (!$csrfOk) {
        if ($form === 'parola') {
            $passwordErrors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
        } elseif ($form === 'rechizite') {
            $requisiteErrors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
        } else {
            $orderErrors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
        }
    } elseif ($form === 'rechizite') {
        $postedFields = $_POST['camp'] ?? [];
        if (!is_array($postedFields)) {
            $postedFields = [];
        }
        $lines = [];
        foreach (requisite_fields() as $key => $label) {
            $lines[] = $label . ': ' . text_line((string) ($postedFields[$key] ?? ''), 400);
        }
        $postedRequisites = implode("\n", $lines);
        if (strlen($postedRequisites) > 2000) {
            $requisiteErrors['rechizite'] = 'Textul este prea lung. Păstrează datele lângă fiecare denumire.';
        }
        $oldRequisites = requisite_normalize($postedRequisites);
        if ($requisiteErrors === [] && !requisites_filled($oldRequisites)) {
            $requisiteErrors['rechizite'] = 'Completează denumirea, IDNO, adresa juridică și IBAN-ul. Codul TVA poate rămâne gol.';
        }
        if ($requisiteErrors === [] && !save_client_requisites((string) $user['id'], $oldRequisites)) {
            $requisiteErrors['form'] = 'Rechizitele nu au putut fi salvate. Încearcă din nou.';
        }
        if ($requisiteErrors === []) {
            flash_set('Rechizitele au fost salvate. Le folosim pentru contul de plată.');
            header('Location: ' . url('cabinet.php'));
            exit;
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
                    'pret' => (isset($item['pret']) && is_numeric($item['pret'])) ? round((float) $item['pret'], 2) : null,
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
        if (!requisites_filled((string) ($user['rechizite'] ?? ''))) {
            $orderErrors['form'] = 'Completează rechizitele companiei, la contul tău, ca să îți putem trimite contul de plată.';
        }
        if ($orderErrors === []) {
            $order = place_order($user, $lines, $oldAddress, $oldNote);
            if ($order === null) {
                $orderErrors['form'] = db_failed()
                    ? 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.'
                    : 'Comanda nu a putut fi salvată. Încearcă din nou sau scrie-ne la ' . SITE_EMAIL . '.';
            } else {
                if ((string) ($_POST['actiune'] ?? '') === 'cont') {
                    header('Location: ' . url('cont-de-plata.php?id=' . rawurlencode((string) $order['id'])));
                    exit;
                }
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

                    <div class="table-wrap">
                        <table class="sheet-table">
                            <thead>
                                <tr>
                                    <th>Produs</th>
                                    <th>Cantitate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($catalog as $slug => $item): ?>
                                    <tr>
                                        <td>
                                            <label for="qty-<?= e($slug) ?>"><?= e($item['title']) ?> <span><?= e($item['kicker']) ?></span></label>
                                        </td>
                                        <td>
                                            <input id="qty-<?= e($slug) ?>" name="qty[<?= e($slug) ?>]" type="number" min="0" max="999" step="1" inputmode="numeric" value="<?= (int) $oldQty[$slug] ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (isset($orderErrors['qty'])): ?><small class="field-error"><?= e($orderErrors['qty']) ?></small><?php endif; ?>

                    <label for="adresa">Adresă de livrare</label>
                    <textarea id="adresa" name="adresa" required minlength="5" maxlength="300" rows="3" placeholder="Localitate, stradă, alte detalii"><?= e($oldAddress) ?></textarea>
                    <?php if (isset($orderErrors['adresa'])): ?><small class="field-error"><?= e($orderErrors['adresa']) ?></small><?php endif; ?>

                    <label for="nota">Mențiune <span>(opțional)</span></label>
                    <textarea id="nota" name="nota" maxlength="1000" rows="3" placeholder="Cutii, kilogram sau orice detaliu despre comandă"><?= e($oldNote) ?></textarea>

                    <div class="btn-row">
                        <button class="btn btn-dark" type="submit" name="actiune" value="comanda">Trimite comanda</button>
                        <button class="btn btn-line" type="submit" name="actiune" value="cont">Generează cont de plată</button>
                    </div>
                    <p class="form-note">Cantitatea 0 înseamnă că produsul nu intră în comandă. Contul de plată se emite pentru produsele alese și rechizitele salvate.</p>
                </form>
            </div>

            <aside class="contact-aside">
                <p class="kicker">Contul tău</p>
                <h2>Datele contului</h2>
                <div class="table-wrap">
                    <table class="sheet-table">
                        <tbody>
                            <tr>
                                <th scope="row">Nume</th>
                                <td><?= e((string) $user['nume']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Email</th>
                                <td><?= e((string) $user['email']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Telefon</th>
                                <td><?= e((string) ($user['telefon'] ?? '')) !== '' ? e((string) $user['telefon']) : '—' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <form class="contact-form requisite-form" method="post" action="<?= e(url('cabinet.php')) ?>" accept-charset="UTF-8">
                    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                    <input type="hidden" name="formular" value="rechizite">
                    <p class="kicker">Rechizite</p>
                    <?php if (!empty($requisiteErrors['form'])): ?>
                        <p class="notice notice-error" role="alert"><?= e($requisiteErrors['form']) ?></p>
                    <?php endif; ?>
                    <div class="table-wrap">
                        <table class="sheet-table">
                            <tbody>
                                <?php foreach (requisite_fields() as $key => $label): ?>
                                    <tr>
                                        <th scope="row"><label for="camp-<?= e($key) ?>"><?= e($label) ?></label></th>
                                        <td>
                                            <input id="camp-<?= e($key) ?>" name="camp[<?= e($key) ?>]" type="text" maxlength="400" value="<?= e(requisite_value($oldRequisites, $label)) ?>" <?= $key === 'tva' ? '' : 'required' ?> <?= isset($requisiteErrors['rechizite']) ? 'aria-invalid="true"' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (isset($requisiteErrors['rechizite'])): ?><small class="field-error"><?= e($requisiteErrors['rechizite']) ?></small><?php endif; ?>
                    <button class="btn btn-dark" type="submit">Salvează rechizitele</button>
                    <p class="form-note">Scrie datele firmei în dreptul fiecărei denumiri. Codul TVA poate rămâne gol. Le atașăm comenzii pentru contul de plată.</p>
                </form>
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
                <div class="table-wrap orders-sheet">
                    <table class="sheet-table">
                        <thead>
                            <tr>
                                <th>Comandă</th>
                                <th>Data</th>
                                <th>Produse</th>
                                <th>Adresă</th>
                                <th>Stare</th>
                                <th>Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= e((string) ($order['id'] ?? '')) ?></td>
                                    <td><?= e(date('d.m.Y H:i', strtotime((string) ($order['la'] ?? 'now')) ?: time())) ?></td>
                                    <td>
                                        <?php foreach (($order['linii'] ?? []) as $line): ?>
                                            <?php if (!is_array($line)) { continue; } ?>
                                            <span class="line-item"><?= e((string) ($line['titlu'] ?? '')) ?> × <?= (int) ($line['cantitate'] ?? 0) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (($order['nota'] ?? '') !== ''): ?>
                                            <span class="line-note"><?= e((string) $order['nota']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e((string) ($order['adresa'] ?? '')) ?></td>
                                    <td><span class="status status-<?= e((string) ($order['status'] ?? '')) ?>"><?= e(order_status_label((string) ($order['status'] ?? ''))) ?></span></td>
                                    <td><a class="invoice-link" href="<?= e(url('cont-de-plata.php?id=' . rawurlencode((string) ($order['id'] ?? '')))) ?>">Cont de plată</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
