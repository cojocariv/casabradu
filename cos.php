<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/factura.php';

$errors = [];
$notice = flash_get();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['actiune'] ?? '');
    $csrfOk = hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));
    if (!$csrfOk) {
        $errors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
    } elseif ($action === 'adauga') {
        $slug = text_line((string) ($_POST['slug'] ?? ''), 40);
        $qty = (int) ($_POST['cantitate'] ?? 1);
        if ($qty < 1 || $qty > 999) {
            $qty = 1;
        }
        $item = product_by_slug($slug);
        if ($item === null || !cart_add($slug, $qty)) {
            flash_set('Produsul nu a putut fi adăugat în coș.');
        } else {
            flash_set($item['title'] . ' a fost adăugat în coș.');
        }
        header('Location: ' . url(cart_return((string) ($_POST['inapoi'] ?? ''))));
        exit;
    } elseif ($action === 'actualizeaza') {
        $posted = $_POST['qty'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }
        cart_set($posted);
        flash_set('Coșul a fost actualizat.');
        header('Location: ' . url('cos.php'));
        exit;
    } elseif ($action === 'sterge') {
        $slug = text_line((string) ($_POST['slug'] ?? ''), 40);
        $cart = cart_items();
        unset($cart[$slug]);
        $_SESSION['cos'] = $cart;
        flash_set('Produsul a fost scos din coș.');
        header('Location: ' . url('cos.php'));
        exit;
    } elseif ($action === 'comanda' || $action === 'cont') {
        $user = current_user();
        if ($user === null) {
            header('Location: ' . url('autentificare.php?next=' . rawurlencode('cos.php')));
            exit;
        }
        $lines = cart_lines();
        $address = text_block((string) ($_POST['adresa'] ?? ''), 300);
        $note = text_block((string) ($_POST['nota'] ?? ''), 1000);
        if ($lines === []) {
            $errors['form'] = 'Coșul este gol.';
        }
        if (strlen($address) < 5) {
            $errors['adresa'] = 'Scrie adresa de livrare sau localitatea.';
        }
        if (!requisites_filled((string) ($user['rechizite'] ?? ''))) {
            $errors['form'] = 'Completează rechizitele companiei în cabinet, ca să putem emite contul de plată.';
        }
        if ($errors === []) {
            $order = place_order($user, $lines, $address, $note);
            if ($order === null) {
                $errors['form'] = db_failed()
                    ? 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.'
                    : 'Comanda nu a putut fi salvată. Încearcă din nou sau scrie-ne la ' . SITE_EMAIL . '.';
            } else {
                cart_clear();
                if ($action === 'cont') {
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

$lines = cart_lines();
$user = current_user();
$address = (string) ($user['adresa'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $address = text_block((string) ($_POST['adresa'] ?? $address), 300);
}
$note = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') ? text_block((string) ($_POST['nota'] ?? ''), 1000) : '';
$table = invoice_table($lines);
$requisitesReady = $user !== null && requisites_filled((string) ($user['rechizite'] ?? ''));

$page = 'cos';
$noindex = true;
$pageTitle = 'Coș';
$pageDescription = 'Coșul de cumpărături Casa Bradu Caffè.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Magazin</p>
        <h1>Coșul tău.</h1>
        <p class="lede"><?= $lines === [] ? 'Nu ai produse în coș. Alege din selecție și adaugă ce vrei să comandăm.' : 'Verifică cantitățile, apoi trimite comanda. Te contactăm ca să o confirmăm.' ?></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($notice !== ''): ?>
            <p class="notice" role="status"><?= e($notice) ?></p>
        <?php endif; ?>
        <?php if (!empty($errors['form'])): ?>
            <p class="notice notice-error" role="alert"><?= e($errors['form']) ?></p>
        <?php endif; ?>

        <?php if ($lines === []): ?>
            <a class="btn btn-dark" href="<?= e(url('produse.php')) ?>">Vezi produsele</a>
        <?php else: ?>
            <p><a class="invoice-link" href="<?= e(url('produse.php')) ?>">Continuă cumpărăturile</a></p>
            <form id="cart-update" method="post" action="<?= e(url('cos.php')) ?>">
                <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                <input type="hidden" name="actiune" value="actualizeaza">
            </form>
            <div class="table-wrap orders-sheet">
                <table class="sheet-table">
                    <thead>
                        <tr>
                            <th>Produs</th>
                            <th>Cantitate</th>
                            <th>Preț, lei</th>
                            <th>Suma, lei</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lines as $index => $line): ?>
                            <?php $row = $table['rows'][$index] ?? ['pret' => null, 'suma' => null]; ?>
                            <tr>
                                <td><?= e($line['titlu']) ?></td>
                                <td>
                                    <input form="cart-update" name="qty[<?= e($line['slug']) ?>]" type="number" min="0" max="999" step="1" value="<?= (int) $line['cantitate'] ?>" inputmode="numeric" aria-label="Cantitate <?= e($line['titlu']) ?>">
                                </td>
                                <td><?= e(lei($row['pret'] ?? null)) ?></td>
                                <td><?= e(lei($row['suma'] ?? null)) ?></td>
                                <td>
                                    <form method="post" action="<?= e(url('cos.php')) ?>">
                                        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                                        <input type="hidden" name="actiune" value="sterge">
                                        <input type="hidden" name="slug" value="<?= e($line['slug']) ?>">
                                        <button class="btn btn-line btn-sm" type="submit">Elimină</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="cart-total"><strong>Total: <?= e($table['total'] === null ? 'la confirmare' : lei($table['total']) . ' lei') ?></strong></p>
            <?php if ($table['tva'] !== null): ?>
                <p class="form-note">Inclusiv TVA: <?= e(lei($table['tva'])) ?> lei</p>
            <?php else: ?>
                <p class="form-note">Prețul îl confirmăm împreună, înainte de contul de plată. Cantitatea 0 scoate produsul din coș.</p>
            <?php endif; ?>
            <button class="btn btn-line" type="submit" form="cart-update">Actualizează coșul</button>

            <div class="contact-card cart-checkout">
                <p class="kicker">Finalizare</p>
                <h2>Trimite comanda.</h2>
                <?php if ($user === null): ?>
                    <p>Ca să înregistrăm comanda, intră în cont. Produsele rămân în coș.</p>
                    <a class="btn btn-dark" href="<?= e(url('autentificare.php?next=' . rawurlencode('cos.php'))) ?>">Intră în cont</a>
                <?php else: ?>
                    <?php if (!$requisitesReady): ?>
                        <p class="notice notice-error" role="alert">Completează rechizitele companiei în cabinet înainte de comandă.</p>
                        <a class="btn btn-line" href="<?= e(url('cabinet.php')) ?>">Completează rechizitele</a>
                    <?php endif; ?>
                    <form class="contact-form" method="post" action="<?= e(url('cos.php')) ?>" accept-charset="UTF-8">
                        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                        <label for="adresa">Adresă de livrare</label>
                        <textarea id="adresa" name="adresa" required minlength="5" maxlength="300" rows="3"><?= e($address) ?></textarea>
                        <?php if (isset($errors['adresa'])): ?><small class="field-error"><?= e($errors['adresa']) ?></small><?php endif; ?>
                        <label for="nota">Mențiune <span>(opțional)</span></label>
                        <textarea id="nota" name="nota" maxlength="1000" rows="3"><?= e($note) ?></textarea>
                        <div class="btn-row">
                            <button class="btn btn-dark" type="submit" name="actiune" value="comanda" <?= $requisitesReady ? '' : 'disabled' ?>>Plasează comanda</button>
                            <button class="btn btn-line" type="submit" name="actiune" value="cont" <?= $requisitesReady ? '' : 'disabled' ?>>Generează cont de plată</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
