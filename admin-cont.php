<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/cos.php';
require_once __DIR__ . '/includes/factura.php';
require_once __DIR__ . '/includes/crm.php';

if (!admin_logged_in()) {
    header('Location: ' . url('admin.php'));
    exit;
}

$errors = [];
$flash = flash_get();
$clients = crm_clients();
$clientRows = is_array($clients['rows'] ?? null) ? $clients['rows'] : [];

function admin_invoice_default_lines(): array
{
    $lines = [];
    foreach (product_price_rows() as $row) {
        $item = product_by_slug($row['slug']);
        $choice = is_array($item) ? product_choice($item, $row['optiune']) : null;
        $pret = is_array($choice) ? ($choice['pret'] ?? null) : null;
        $lines[] = [
            'slug' => product_price_field($row),
            'titlu' => $row['produs'] . ($row['varianta'] !== '' ? ', ' . $row['varianta'] : ''),
            'cantitate' => '',
            'pret' => is_numeric($pret) && (float) $pret > 0 ? product_price_input($pret) : '',
        ];
    }
    $lines[] = [
        'slug' => 'manual',
        'titlu' => '',
        'cantitate' => '',
        'pret' => '',
    ];

    return $lines;
}

function admin_invoice_parse_lines(array $posted): array
{
    $lines = [];
    $titles = $posted['titlu'] ?? [];
    $qtys = $posted['cantitate'] ?? [];
    $prices = $posted['pret'] ?? [];
    $slugs = $posted['slug'] ?? [];
    if (!is_array($titles)) {
        return [];
    }
    foreach ($titles as $index => $title) {
        $title = text_line((string) $title, 120);
        $qty = (int) ($qtys[$index] ?? 0);
        if ($title === '' || $qty < 1) {
            continue;
        }
        if ($qty > 99999) {
            $qty = 99999;
        }
        $rawPrice = trim((string) ($prices[$index] ?? ''));
        $parsed = product_parse_price($rawPrice);
        if ($parsed === 'invalid') {
            return ['error' => 'Scrie prețul în lei, de exemplu 12,50, sau lasă câmpul gol.'];
        }
        $slug = text_line((string) ($slugs[$index] ?? 'manual'), 40);
        if ($slug === '' || preg_match('/^[a-z0-9:_-]+$/i', $slug) !== 1) {
            $slug = 'manual';
        }
        $lines[] = [
            'slug' => $slug,
            'titlu' => $title,
            'cantitate' => $qty,
            'pret' => $parsed === null ? null : (float) $parsed,
        ];
    }

    return ['lines' => $lines];
}

function admin_invoice_form_state(?array $client, array $post = []): array
{
    $fields = requisite_fields();
    $state = [
        'client_id' => text_line((string) ($post['client_id'] ?? ($client['id'] ?? '')), 32),
        'email' => text_line((string) ($post['email'] ?? ($client['email'] ?? '')), 120),
        'nume' => text_line((string) ($post['nume'] ?? ($client['nume'] ?? '')), 80),
        'telefon' => text_line((string) ($post['telefon'] ?? (($client['telefon'] ?? '') === '-' ? '' : ($client['telefon'] ?? ''))), 20),
        'adresa' => text_line((string) ($post['adresa'] ?? ($client['adresa'] ?? '')), 300),
        'nota' => text_block((string) ($post['nota'] ?? ''), 1000),
        'companie' => '',
        'idno' => '',
        'tva' => '',
        'adresa_juridica' => '',
        'iban' => '',
        'lines' => [],
    ];

    $requisites = (string) ($post['rechizite_source'] ?? ($client['rechizite'] ?? ''));
    if ($post !== []) {
        foreach ($fields as $key => $label) {
            $map = [
                'companie' => 'companie',
                'idno' => 'idno',
                'tva' => 'tva',
                'adresa' => 'adresa_juridica',
                'iban' => 'iban',
            ];
            $field = $map[$key];
            $state[$field] = text_line((string) ($post[$field] ?? ''), $key === 'adresa' || $key === 'iban' ? 300 : 120);
        }
    } else {
        $state['companie'] = requisite_value($requisites, $fields['companie']);
        $state['idno'] = requisite_value($requisites, $fields['idno']);
        $state['tva'] = requisite_value($requisites, $fields['tva']);
        $state['adresa_juridica'] = requisite_value($requisites, $fields['adresa']);
        $state['iban'] = requisite_value($requisites, $fields['iban']);
    }

    if (isset($post['titlu']) && is_array($post['titlu'])) {
        foreach ($post['titlu'] as $index => $title) {
            $state['lines'][] = [
                'slug' => (string) ($post['slug'][$index] ?? 'manual'),
                'titlu' => (string) $title,
                'cantitate' => (string) ($post['cantitate'][$index] ?? ''),
                'pret' => (string) ($post['pret'][$index] ?? ''),
            ];
        }
    } else {
        $state['lines'] = admin_invoice_default_lines();
    }

    return $state;
}

$selectedId = text_line((string) ($_GET['client'] ?? $_POST['client_id'] ?? ''), 32);
$selected = admin_client($selectedId);
$form = admin_invoice_form_state($selected, ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : []);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['actiune'] ?? '');
    $csrfOk = hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));
    if (!$csrfOk) {
        $errors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
    } elseif ($action === 'incarca') {
        $loadId = text_line((string) ($_POST['client_id'] ?? ''), 32);
        header('Location: ' . url('admin-cont.php' . ($loadId !== '' ? '?client=' . rawurlencode($loadId) : '')));
        exit;
    } elseif ($action === 'genereaza' || $action === 'email') {
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Indică emailul clientului.';
        }
        if ($form['nume'] === '') {
            $form['nume'] = $form['companie'] !== '' ? $form['companie'] : 'Client';
        }
        $requisites = requisite_normalize(
            "Denumirea companiei: {$form['companie']}\n"
            . "IDNO: {$form['idno']}\n"
            . "Cod TVA: {$form['tva']}\n"
            . "Adresa juridică: {$form['adresa_juridica']}\n"
            . "IBAN și banca: {$form['iban']}"
        );
        if (!requisites_filled($requisites)) {
            $errors['rechizite'] = 'Completează denumirea, IDNO, adresa juridică și IBAN-ul. Codul TVA poate rămâne gol.';
        }
        $parsed = admin_invoice_parse_lines($_POST);
        if (isset($parsed['error'])) {
            $errors['linii'] = $parsed['error'];
            $lines = [];
        } else {
            $lines = $parsed['lines'];
        }
        if ($lines === []) {
            $errors['linii'] = $errors['linii'] ?? 'Adaugă cel puțin un produs cu cantitate.';
        }

        if ($errors === []) {
            $client = admin_ensure_client($form['email'], $form['nume'], $form['telefon'], $requisites);
            if ($client === null) {
                $errors['form'] = db_failed()
                    ? 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.'
                    : 'Clientul nu a putut fi salvat.';
            } else {
                if ($action === 'email' && !smtp_ready()) {
                    $errors['form'] = 'Pentru trimitere PDF, adaugă parola cutiei comenzi@casabradu.md în configurarea site-ului.';
                } else {
                    $order = admin_create_invoice($client, $lines, $requisites, $form['adresa'], $form['nota']);
                    if ($order === null) {
                        $errors['form'] = db_failed()
                            ? 'Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.'
                            : 'Contul de plată nu a putut fi creat.';
                    } elseif ($action === 'genereaza') {
                        flash_set('Contul de plată ' . $order['id'] . ' a fost generat.');
                        header('Location: ' . url('cont-de-plata.php?id=' . rawurlencode($order['id'])));
                        exit;
                    } else {
                        $stamp = strtotime((string) ($order['la'] ?? '')) ?: time();
                        $invoiceDate = date('d.m.Y', $stamp);
                        $table = invoice_table($order['linii'] ?? []);
                        $payer = [
                            'nume' => requisite_value($requisites, 'Denumirea companiei'),
                            'idno' => requisite_value($requisites, 'IDNO'),
                            'tva' => requisite_value($requisites, 'Cod TVA'),
                            'adresa' => requisite_value($requisites, 'Adresa juridică'),
                        ];
                        if ($payer['nume'] === '') {
                            $payer['nume'] = (string) ($client['nume'] ?? '');
                        }
                        if (send_invoice_mail($order, $invoiceDate, $payer, $table, (string) $client['email'], $client)) {
                            $_SESSION['invoice_mail'] = ['ok' => true, 'text' => 'Contul de plată a fost trimis.'];
                        } else {
                            $reason = site_mail_status();
                            $_SESSION['invoice_mail'] = [
                                'ok' => false,
                                'text' => $reason !== '' ? $reason : 'Emailul nu a putut fi trimis. Încearcă din nou peste câteva minute.',
                            ];
                        }
                        header('Location: ' . url('cont-de-plata.php?id=' . rawurlencode($order['id'])));
                        exit;
                    }
                }
            }
        }
    }
}

$page = 'admin';
$noindex = true;
$pageTitle = 'Cont de plată';
$pageDescription = 'Generează și trimite conturi de plată din cabinetul administratorului.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Administrator</p>
        <h1>Cont de plată.</h1>
        <p class="lede">Completează rechizitele clientului, produsele, cantitatea și prețul. Poți genera contul oricând și îl poți trimite clientului în PDF.</p>
        <p><a class="invoice-link" href="<?= e(url('admin.php')) ?>">Înapoi la comenzi</a></p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <?php if ($flash !== ''): ?>
            <p class="notice" role="status"><?= e($flash) ?></p>
        <?php endif; ?>
        <?php if (!empty($errors['form'])): ?>
            <p class="notice notice-error" role="alert"><?= e($errors['form']) ?></p>
        <?php endif; ?>

        <form class="contact-form invoice-builder" method="post" action="<?= e(url('admin-cont.php')) ?>" accept-charset="UTF-8">
            <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">

            <div class="price-board">
                <p class="kicker">Client</p>
                <h2>Datele și rechizitele</h2>
                <p class="form-note">Poți alege un client salvat ca să precompletezi câmpurile, sau le completezi manual.</p>

                <label for="client_id">Client salvat</label>
                <div class="invoice-client-row">
                    <select id="client_id" name="client_id" data-load-url="<?= e(url('admin-cont.php')) ?>">
                        <option value="">Client nou / manual</option>
                        <?php foreach ($clientRows as $row): ?>
                            <option value="<?= e((string) $row['id']) ?>" <?= $form['client_id'] === (string) $row['id'] ? 'selected' : '' ?>>
                                <?= e((string) $row['nume'] . ' · ' . (string) $row['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-line btn-sm" type="submit" name="actiune" value="incarca" formnovalidate>Încarcă datele</button>
                </div>

                <label for="email">Email client</label>
                <input id="email" name="email" type="email" required maxlength="120" value="<?= e($form['email']) ?>" <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>

                <label for="nume">Nume / persoană de contact</label>
                <input id="nume" name="nume" type="text" maxlength="80" value="<?= e($form['nume']) ?>">

                <label for="telefon">Telefon <span>(opțional)</span></label>
                <input id="telefon" name="telefon" type="text" maxlength="20" value="<?= e($form['telefon']) ?>">

                <label for="companie">Denumirea companiei</label>
                <input id="companie" name="companie" type="text" maxlength="120" value="<?= e($form['companie']) ?>" required>

                <label for="idno">IDNO</label>
                <input id="idno" name="idno" type="text" maxlength="40" value="<?= e($form['idno']) ?>" required>

                <label for="tva">Cod TVA <span>(opțional)</span></label>
                <input id="tva" name="tva" type="text" maxlength="40" value="<?= e($form['tva']) ?>">

                <label for="adresa_juridica">Adresa juridică</label>
                <input id="adresa_juridica" name="adresa_juridica" type="text" maxlength="300" value="<?= e($form['adresa_juridica']) ?>" required>

                <label for="iban">IBAN și banca</label>
                <input id="iban" name="iban" type="text" maxlength="300" value="<?= e($form['iban']) ?>" required>
                <?php if (isset($errors['rechizite'])): ?><small class="field-error"><?= e($errors['rechizite']) ?></small><?php endif; ?>

                <label for="adresa">Adresă de livrare <span>(opțional)</span></label>
                <input id="adresa" name="adresa" type="text" maxlength="300" value="<?= e($form['adresa']) ?>">

                <label for="nota">Mențiune <span>(opțional)</span></label>
                <textarea id="nota" name="nota" rows="2" maxlength="1000"><?= e($form['nota']) ?></textarea>
            </div>

            <div class="price-board" id="produse-cont">
                <p class="kicker">Produse</p>
                <h2>Linii de cont</h2>
                <p class="form-note">Produsele din catalog sunt precompletate. Scrie cantitatea și ajustează prețul. Poți modifica denumirea sau adăuga o linie liberă.</p>
                <?php if (isset($errors['linii'])): ?>
                    <p class="notice notice-error" role="alert"><?= e($errors['linii']) ?></p>
                <?php endif; ?>
                <div class="table-wrap orders-sheet">
                    <table class="sheet-table invoice-lines">
                        <thead>
                            <tr>
                                <th>Produs</th>
                                <th>Cantitate</th>
                                <th>Preț, lei</th>
                                <th>Suma, lei</th>
                            </tr>
                        </thead>
                        <tbody id="invoice-lines">
                            <?php foreach ($form['lines'] as $index => $line): ?>
                                <?php
                                $qtyShow = (int) ($line['cantitate'] ?? 0);
                                $priceParsed = product_parse_price((string) ($line['pret'] ?? ''));
                                $sumShow = ($qtyShow > 0 && is_string($priceParsed) && $priceParsed !== 'invalid')
                                    ? number_format($qtyShow * (float) $priceParsed, 2, ',', '')
                                    : '';
                                ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="slug[]" value="<?= e((string) $line['slug']) ?>">
                                        <label class="sr-only" for="titlu-<?= (int) $index ?>">Produs</label>
                                        <input id="titlu-<?= (int) $index ?>" name="titlu[]" type="text" maxlength="120" value="<?= e((string) $line['titlu']) ?>" placeholder="Denumirea mărfii">
                                    </td>
                                    <td>
                                        <label class="sr-only" for="cant-<?= (int) $index ?>">Cantitate</label>
                                        <input id="cant-<?= (int) $index ?>" class="invoice-qty" name="cantitate[]" type="number" min="0" max="99999" step="1" value="<?= e((string) $line['cantitate']) ?>" inputmode="numeric">
                                    </td>
                                    <td>
                                        <label class="sr-only" for="pret-<?= (int) $index ?>">Preț</label>
                                        <input id="pret-<?= (int) $index ?>" class="invoice-price" name="pret[]" type="text" inputmode="decimal" maxlength="12" value="<?= e((string) $line['pret']) ?>" placeholder="12,50">
                                    </td>
                                    <td>
                                        <span class="invoice-sum" aria-live="polite"><?= e($sumShow) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-line btn-sm" type="button" id="add-invoice-line">Adaugă linie</button>
            </div>

            <div class="btn-row invoice-actions">
                <button class="btn btn-dark" type="submit" name="actiune" value="genereaza">Generează cont de plată</button>
                <button class="btn btn-line" type="submit" name="actiune" value="email">Generează și trimite PDF</button>
            </div>
        </form>
    </div>
</section>

<template id="invoice-line-template">
    <tr>
        <td>
            <input type="hidden" name="slug[]" value="manual">
            <label class="sr-only">Produs</label>
            <input name="titlu[]" type="text" maxlength="120" value="" placeholder="Denumirea mărfii">
        </td>
        <td>
            <label class="sr-only">Cantitate</label>
            <input class="invoice-qty" name="cantitate[]" type="number" min="0" max="99999" step="1" value="" inputmode="numeric">
        </td>
        <td>
            <label class="sr-only">Preț</label>
            <input class="invoice-price" name="pret[]" type="text" inputmode="decimal" maxlength="12" value="" placeholder="12,50">
        </td>
        <td>
            <span class="invoice-sum" aria-live="polite"></span>
        </td>
    </tr>
</template>

<script>
(function () {
    var clientSelect = document.getElementById('client_id');
    if (clientSelect) {
        clientSelect.addEventListener('change', function () {
            var base = clientSelect.getAttribute('data-load-url') || 'admin-cont.php';
            var id = clientSelect.value || '';
            window.location.href = id ? (base + (base.indexOf('?') >= 0 ? '&' : '?') + 'client=' + encodeURIComponent(id)) : base;
        });
    }

    var button = document.getElementById('add-invoice-line');
    var body = document.getElementById('invoice-lines');
    var template = document.getElementById('invoice-line-template');
    if (!body) {
        return;
    }

    function parsePrice(raw) {
        raw = String(raw || '').replace(/\u00a0/g, '').replace(/\s+/g, '').replace(',', '.');
        if (raw === '') {
            return null;
        }
        var value = Number(raw);
        if (!isFinite(value) || value <= 0) {
            return null;
        }
        return value;
    }

    function formatSum(value) {
        var fixed = value.toFixed(2).split('.');
        return fixed[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ',' + fixed[1];
    }

    function updateRow(row) {
        if (!row) {
            return;
        }
        var qtyInput = row.querySelector('.invoice-qty');
        var priceInput = row.querySelector('.invoice-price');
        var sumNode = row.querySelector('.invoice-sum');
        if (!qtyInput || !priceInput || !sumNode) {
            return;
        }
        var qty = parseInt(qtyInput.value, 10);
        var price = parsePrice(priceInput.value);
        if (!qty || qty < 1 || price === null) {
            sumNode.textContent = '';
            return;
        }
        sumNode.textContent = formatSum(qty * price);
    }

    function updateAll() {
        body.querySelectorAll('tr').forEach(updateRow);
    }

    body.addEventListener('input', function (event) {
        var target = event.target;
        if (!target || !target.classList) {
            return;
        }
        if (target.classList.contains('invoice-qty') || target.classList.contains('invoice-price')) {
            updateRow(target.closest('tr'));
        }
    });

    if (button && template) {
        button.addEventListener('click', function () {
            body.appendChild(template.content.cloneNode(true));
        });
    }

    updateAll();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
