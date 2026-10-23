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
        $option = text_line((string) ($_POST['optiune'] ?? ''), 20);
        if (preg_match('/^[a-z0-9]*$/', $option) !== 1) {
            $option = '';
        }
        $qty = (int) ($_POST['cantitate'] ?? 1);
        if ($qty < 1 || $qty > 999) {
            $qty = 1;
        }
        $item = product_by_slug($slug);
        $choice = $item === null ? null : product_choice($item, $option);
        if ($item === null || $choice === null || !cart_add($slug, $qty, (string) $choice['key'])) {
            flash_set('Produsul nu a putut fi adăugat în coș.');
        } else {
            flash_set(product_line_title($item, $choice) . ' a fost adăugat în coș.');
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
        $payment = text_line((string) ($_POST['plata'] ?? ''), 20);
        $personType = text_line((string) ($_POST['tip_persoana'] ?? ''), 20);
        if ($lines === []) {
            $errors['form'] = 'Coșul este gol.';
        }
        if (strlen($address) < 5) {
            $errors['adresa'] = 'Scrie adresa de livrare sau localitatea.';
        }
        if (!isset(order_person_types()[$personType])) {
            $errors['tip_persoana'] = 'Alege tipul de persoană.';
        }
        if (!isset(order_payment_methods($personType)[$payment])) {
            $errors['plata'] = 'Alege metoda de achitare.';
        }
        if ($errors === []) {
            $order = place_order($user, $lines, $address, $note, $payment, $personType);
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
                flash_set(sprintf(t('Comanda %s a fost înregistrată. Te contactăm pentru confirmare.'), $order['id']));
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
$payment = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')
    ? text_line((string) ($_POST['plata'] ?? ''), 20)
    : '';
$personType = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')
    ? text_line((string) ($_POST['tip_persoana'] ?? ''), 20)
    : 'fizica';
if ($personType !== 'juridica' && $personType !== 'fizica') {
    $personType = 'fizica';
}
$table = invoice_table($lines);
$page = 'cos';
$noindex = true;
$pageTitle = 'Coș';
$pageDescription = 'Coșul de cumpărături Casa Bradu Caffè.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow"><?= tr('Magazin') ?></p>
        <h1><?= tr('Coșul tău.') ?></h1>
        <p class="lede"><?= $lines === [] ? tr('Nu ai produse în coș. Alege cafeaua sau un aparat în comodat.') : tr('Verifică cantitățile, apoi trimite comanda. Livrăm în toată Republica Moldova și te contactăm ca să o confirmăm.') ?></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($notice !== ''): ?>
            <p class="notice" role="status"><?= e($notice) ?></p>
        <?php endif; ?>
        <?php if (!empty($errors['form'])): ?>
            <p class="notice notice-error" role="alert"><?= e(t($errors['form'])) ?></p>
        <?php endif; ?>

        <?php if ($lines === []): ?>
            <div class="btn-row">
                <a class="btn btn-dark" href="<?= e(url('produse.php')) ?>"><?= tr('Vezi cafeaua') ?></a>
                <a class="btn btn-line" href="<?= e(url('aparate.php')) ?>"><?= tr('Vezi aparatele') ?></a>
            </div>
        <?php else: ?>
            <p class="continue-links">
                <a class="invoice-link" href="<?= e(url('produse.php')) ?>"><?= tr('Continuă cu cafeaua') ?></a>
                <a class="invoice-link" href="<?= e(url('aparate.php')) ?>"><?= tr('Continuă cu aparatele') ?></a>
            </p>
            <form id="cart-update" method="post" action="<?= e(url('cos.php')) ?>">
                <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                <input type="hidden" name="actiune" value="actualizeaza">
            </form>
            <div class="table-wrap orders-sheet" id="cart-live"
                 data-confirm="<?= e(t('la confirmare')) ?>"
                 data-lei="<?= e(t('lei')) ?>">
                <table class="sheet-table">
                    <thead>
                        <tr>
                            <th><?= tr('Produs') ?></th>
                            <th><?= tr('Cantitate') ?></th>
                            <th><?= tr('Preț, lei') ?></th>
                            <th><?= tr('Suma, lei') ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lines as $index => $line): ?>
                            <?php
                            $row = $table['rows'][$index] ?? ['pret' => null, 'suma' => null];
                            $pretBani = $row['pret'] ?? null;
                            $pretLabel = ($money = lei($pretBani)) === 'la confirmare' ? t('la confirmare') : $money;
                            $sumaLabel = ($money = lei($row['suma'] ?? null)) === 'la confirmare' ? t('la confirmare') : $money;
                            ?>
                            <tr class="cart-row" data-price-bani="<?= $pretBani === null ? '' : (int) $pretBani ?>">
                                <td><?= tr($line['titlu']) ?></td>
                                <td>
                                    <input class="cart-qty" form="cart-update" name="qty[<?= e($line['slug']) ?>]" type="number" min="0" max="999" step="1" value="<?= (int) $line['cantitate'] ?>" inputmode="numeric" aria-label="<?= e(t('Cantitate') . ' ' . t($line['titlu'])) ?>">
                                </td>
                                <td><?= e($pretLabel) ?></td>
                                <td class="cart-line-sum"><?= e($sumaLabel) ?></td>
                                <td>
                                    <form method="post" action="<?= e(url('cos.php')) ?>">
                                        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                                        <input type="hidden" name="actiune" value="sterge">
                                        <input type="hidden" name="slug" value="<?= e($line['slug']) ?>">
                                        <button class="btn btn-line btn-sm" type="submit"><?= tr('Elimină') ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="cart-total"><strong><?= tr('Total') ?>: <span class="cart-total-value"><?= e($table['total'] === null ? t('la confirmare') : lei($table['total']) . ' ' . t('lei')) ?></span></strong></p>
            <p class="form-note cart-tva-line"<?= $table['tva'] === null ? ' hidden' : '' ?>><?= tr('Inclusiv TVA') ?>: <span class="cart-tva-value"><?= e($table['tva'] === null ? '' : lei($table['tva'])) ?></span> <?= tr('lei') ?></p>
            <p class="form-note cart-confirm-note"<?= $table['tva'] !== null ? ' hidden' : '' ?>><?= tr('Prețul îl confirmăm împreună, înainte de contul de plată. Cantitatea 0 scoate produsul din coș.') ?></p>
            <button class="btn btn-line" type="submit" form="cart-update"><?= tr('Actualizează coșul') ?></button>

            <div class="contact-card cart-checkout">
                <p class="kicker"><?= tr('Finalizare') ?></p>
                <h2><?= tr('Trimite comanda.') ?></h2>
                <?php if ($user === null): ?>
                    <p><?= tr('Ca să înregistrăm comanda, intră în cont. Produsele rămân în coș.') ?></p>
                    <a class="btn btn-dark" href="<?= e(url('autentificare.php?next=' . rawurlencode('cos.php'))) ?>"><?= tr('Intră în cont') ?></a>
                <?php else: ?>
                    <form class="contact-form" method="post" action="<?= e(url('cos.php')) ?>" accept-charset="UTF-8">
                        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                        <label for="adresa"><?= tr('Adresă de livrare') ?></label>
                        <textarea id="adresa" name="adresa" required minlength="5" maxlength="300" rows="3"><?= e($address) ?></textarea>
                        <?php if (isset($errors['adresa'])): ?><small class="field-error"><?= e(t($errors['adresa'])) ?></small><?php endif; ?>

                        <fieldset class="order-choice">
                            <legend><?= tr('Tipul persoanei') ?></legend>
                            <div class="buy-options" role="radiogroup" aria-label="<?= e(t('Tipul persoanei')) ?>">
                                <?php foreach (order_person_types() as $key => $label): ?>
                                    <label class="buy-option">
                                        <input type="radio" name="tip_persoana" value="<?= e($key) ?>"<?= $personType === $key ? ' checked' : '' ?> required>
                                        <span><?= tr($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (isset($errors['tip_persoana'])): ?><small class="field-error"><?= e(t($errors['tip_persoana'])) ?></small><?php endif; ?>
                        </fieldset>

                        <fieldset class="order-choice" id="order-payment">
                            <legend><?= tr('Metoda de achitare') ?></legend>
                            <div class="buy-options" role="radiogroup" aria-label="<?= e(t('Metoda de achitare')) ?>">
                                <?php
                                $payFizica = order_payment_methods('fizica');
                                $payJuridica = order_payment_methods('juridica');
                                $payShown = $personType === 'juridica' ? $payJuridica : $payFizica;
                                if ($payment === '' || !isset($payShown[$payment])) {
                                    $payment = (string) array_key_first($payShown);
                                }
                                foreach ($payShown as $key => $label):
                                ?>
                                    <label class="buy-option">
                                        <input type="radio" name="plata" value="<?= e($key) ?>"<?= $payment === $key ? ' checked' : '' ?> required>
                                        <span><?= tr($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (isset($errors['plata'])): ?><small class="field-error"><?= e(t($errors['plata'])) ?></small><?php endif; ?>
                        </fieldset>

                        <label for="nota"><?= tr('Mențiune') ?> <span><?= tr('(opțional)') ?></span></label>
                        <textarea id="nota" name="nota" maxlength="1000" rows="3"><?= e($note) ?></textarea>
                        <div class="btn-row">
                            <button class="btn btn-dark" type="submit" name="actiune" value="comanda"><?= tr('Plasează comanda') ?></button>
                            <button class="btn btn-line" type="submit" name="actiune" value="cont"><?= tr('Generează cont de plată') ?></button>
                        </div>
                    </form>
                    <script>
                    (function () {
                        var form = document.querySelector('.cart-checkout form.contact-form');
                        if (!form) {
                            return;
                        }
                        var paymentRoot = document.getElementById('order-payment');
                        if (!paymentRoot) {
                            return;
                        }
                        var methods = {
                            fizica: <?= json_encode($payFizica, JSON_UNESCAPED_UNICODE) ?>,
                            juridica: <?= json_encode($payJuridica, JSON_UNESCAPED_UNICODE) ?>
                        };
                        var labels = <?= json_encode([
                            'Numerar' => t('Numerar'),
                            'Card bancar' => t('Card bancar'),
                            'Transfer bancar' => t('Transfer bancar'),
                        ], JSON_UNESCAPED_UNICODE) ?>;
                        var group = paymentRoot.querySelector('.buy-options');
                        function syncPayment() {
                            var person = form.querySelector('input[name="tip_persoana"]:checked');
                            var type = person && person.value === 'juridica' ? 'juridica' : 'fizica';
                            var list = methods[type] || methods.fizica;
                            var prev = form.querySelector('input[name="plata"]:checked');
                            var prevVal = prev ? prev.value : '';
                            group.innerHTML = '';
                            var keys = Object.keys(list);
                            keys.forEach(function (key, index) {
                                var label = document.createElement('label');
                                label.className = 'buy-option';
                                var input = document.createElement('input');
                                input.type = 'radio';
                                input.name = 'plata';
                                input.value = key;
                                input.required = true;
                                if (prevVal === key || (!list[prevVal] && index === 0)) {
                                    input.checked = true;
                                }
                                var span = document.createElement('span');
                                span.textContent = labels[list[key]] || list[key];
                                label.appendChild(input);
                                label.appendChild(span);
                                group.appendChild(label);
                            });
                        }
                        form.querySelectorAll('input[name="tip_persoana"]').forEach(function (input) {
                            input.addEventListener('change', syncPayment);
                        });
                    })();
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
