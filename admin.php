<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/crm.php';

$errors = [];
$flash = flash_get();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $form = (string) ($_POST['formular'] ?? '');
    $csrfOk = hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));

    if (!$csrfOk) {
        $errors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
    } elseif ($form === 'iesire') {
        admin_logout();
        header('Location: ' . url('admin.php'));
        exit;
    } elseif ($form === 'stare' && admin_logged_in()) {
        $orderId = text_line((string) ($_POST['id'] ?? ''), 20);
        $status = text_line((string) ($_POST['status'] ?? ''), 20);
        $filter = text_line((string) ($_POST['filtru'] ?? ''), 20);
        if (!admin_set_status($orderId, $status)) {
            $errors['form'] = 'Starea comenzii nu a putut fi salvată.';
        } else {
            flash_set('Comanda ' . $orderId . ' este acum „' . order_status_label($status) . '”.');
            $next = 'admin.php';
            if ($filter !== '' && isset(order_statuses()[$filter])) {
                $next .= '?stare=' . rawurlencode($filter);
            }
            $next .= '#' . $orderId;
            header('Location: ' . url($next));
            exit;
        }
    } elseif ($form === 'preturi' && admin_logged_in()) {
        $postedPrices = $_POST['pret'] ?? [];
        if (!is_array($postedPrices)) {
            $postedPrices = [];
        }
        $priceError = product_save_prices($postedPrices);
        if ($priceError === '') {
            flash_set('Prețurile au fost salvate.');
            header('Location: ' . url('admin.php#preturi'));
            exit;
        }
        $errors['form'] = $priceError;
        $pricePosted = $postedPrices;
    } elseif ($form === 'stoc' && admin_logged_in()) {
        $note = text_block((string) ($_POST['nota'] ?? ''), 300);
        $stockError = crm_stock_add(
            text_line((string) ($_POST['marfa'] ?? ''), 60),
            (int) ($_POST['cantitate'] ?? 0),
            $note
        );
        if ($stockError === '') {
            flash_set('Intrarea în stoc a fost notată.');
            header('Location: ' . url('admin.php#marfa'));
            exit;
        }
        $errors['form'] = $stockError;
    } elseif ($form === 'stoc-sterge' && admin_logged_in()) {
        $moveId = (int) ($_POST['id'] ?? 0);
        if (crm_stock_delete($moveId)) {
            flash_set('Intrarea a fost ștearsă din evidență.');
        } else {
            $errors['form'] = 'Intrarea nu a putut fi ștearsă.';
        }
        if ($errors === []) {
            header('Location: ' . url('admin.php#marfa'));
            exit;
        }
    } elseif ($form === 'intrare') {
        $user = text_line((string) ($_POST['utilizator'] ?? ''), 80);
        $password = (string) ($_POST['parola'] ?? '');
        if (text_line((string) ($_POST['website'] ?? ''), 200) !== '') {
            header('Location: ' . url('admin.php'));
            exit;
        }
        if (!admin_configured()) {
            $errors['form'] = 'Cabinetul de administrator nu este configurat încă.';
        } elseif (admin_login_blocked()) {
            $errors['form'] = 'Prea multe încercări. Așteaptă un minut și încearcă din nou.';
        } elseif (!admin_login($user, $password)) {
            $errors['form'] = 'Utilizator sau parolă incorectă.';
            admin_login_failed();
        } else {
            admin_login_succeeded();
            header('Location: ' . url('admin.php'));
            exit;
        }
    }
}

$pricePosted = $pricePosted ?? [];
$loggedIn = admin_logged_in();
$stockSheet = $loggedIn ? crm_stock_sheet() : ['rows' => [], 'istoric' => [], 'ok' => false];
$clientSheet = $loggedIn ? crm_clients() : ['rows' => [], 'ok' => false];
$ordersByClient = [];
$filter = text_line((string) ($_GET['stare'] ?? ''), 20);
if (!isset(order_statuses()[$filter])) {
    $filter = '';
}

$orders = null;
$visible = [];
$counts = array_fill_keys(array_keys(order_statuses()), 0);
if ($loggedIn) {
    $orders = admin_orders();
    if (is_array($orders)) {
        foreach ($orders as $order) {
            $status = (string) ($order['status'] ?? '');
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            if ($filter === '' || $status === $filter) {
                $visible[] = $order;
            }
            $ordersByClient[(string) ($order['user_id'] ?? '')][] = $order;
        }
    }
}

$page = 'admin';
$noindex = true;
$pageTitle = 'Administrator';
$pageDescription = 'Cabinetul de administrator Casa Bradu Caffè.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Administrator</p>
        <h1><?= $loggedIn ? 'Comenzile clienților.' : 'Intră în cabinet.' ?></h1>
        <?php if ($loggedIn): ?>
            <p class="lede">Vezi cine a comandat, ce a ales și marchează comanda pe măsură ce o confirmi, o încasezi sau o livrezi.</p>
        <?php endif; ?>
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

        <?php if (!$loggedIn): ?>
            <div class="admin-login contact-card">
                <?php if (!admin_configured()): ?>
                    <p class="notice notice-error" role="alert">Adaugă ADMIN_USER și ADMIN_PASS în includes/config.php, pe server, ca să poți intra.</p>
                <?php endif; ?>
                <form class="contact-form" method="post" action="<?= e(url('admin.php')) ?>" accept-charset="UTF-8">
                    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                    <input type="hidden" name="formular" value="intrare">
                    <div class="hp" aria-hidden="true">
                        <label>Site <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>
                    <label for="utilizator">Utilizator</label>
                    <input id="utilizator" name="utilizator" type="text" required maxlength="80" autocomplete="username">
                    <label for="parola">Parolă</label>
                    <input id="parola" name="parola" type="password" required maxlength="200" autocomplete="current-password">
                    <button class="btn btn-dark" type="submit">Intră</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-bar">
                <?php if (is_array($orders)): ?>
                    <nav class="admin-filters" aria-label="Filtru comenzi">
                        <a href="<?= e(url('admin.php')) ?>" <?= $filter === '' ? 'class="is-on"' : '' ?>>Toate <span><?= count($orders) ?></span></a>
                        <?php foreach (order_statuses() as $key => $label): ?>
                            <a href="<?= e(url('admin.php?stare=' . rawurlencode($key))) ?>" <?= $filter === $key ? 'class="is-on"' : '' ?>><?= e($label) ?> <span><?= (int) $counts[$key] ?></span></a>
                        <?php endforeach; ?>
                    </nav>
                <?php else: ?>
                    <p class="notice notice-error" role="alert">Nu ne putem conecta la baza de date. Încearcă din nou peste câteva minute.</p>
                <?php endif; ?>
                <div class="admin-bar-actions">
                    <a class="btn btn-dark btn-sm" href="<?= e(url('admin-cont.php')) ?>">Cont de plată</a>
                    <form method="post" action="<?= e(url('admin.php')) ?>">
                        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                        <input type="hidden" name="formular" value="iesire">
                        <button class="btn btn-line btn-sm" type="submit">Ieși</button>
                    </form>
                </div>
            </div>
            <div class="price-board" id="preturi">
                <p class="kicker">Produse</p>
                <h2>Prețuri</h2>
                <p class="form-note">Prețul este în lei. Lasă câmpul gol dacă îl confirmi la comandă.</p>
                <form method="post" action="<?= e(url('admin.php#preturi')) ?>">
                    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                    <input type="hidden" name="formular" value="preturi">
                    <div class="table-wrap orders-sheet">
                        <table class="sheet-table price-table">
                            <thead>
                                <tr>
                                    <th>Produs</th>
                                    <th>Variantă</th>
                                    <th>Preț, lei</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $priced = [];
                                foreach (product_catalog() as $item) {
                                    $priced[(string) $item['slug']] = $item;
                                }
                                foreach (product_price_rows() as $row):
                                    $field = product_price_field($row);
                                    $item = $priced[$row['slug']] ?? null;
                                    $choice = is_array($item) ? product_choice($item, $row['optiune']) : null;
                                    $value = array_key_exists($field, $pricePosted)
                                        ? (string) $pricePosted[$field]
                                        : product_price_input(is_array($choice) ? ($choice['pret'] ?? null) : null);
                                    ?>
                                    <tr>
                                        <td><?= e($row['produs']) ?></td>
                                        <td><?= e($row['varianta'] !== '' ? $row['varianta'] : '—') ?></td>
                                        <td>
                                            <label class="sr-only" for="pret-<?= e($field) ?>">Preț <?= e($row['produs'] . ($row['varianta'] !== '' ? ', ' . $row['varianta'] : '')) ?></label>
                                            <input id="pret-<?= e($field) ?>" name="pret[<?= e($field) ?>]" type="text" inputmode="decimal" maxlength="12" placeholder="la confirmare" value="<?= e($value) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-dark" type="submit">Salvează prețurile</button>
                </form>
            </div>
            <div class="price-board" id="marfa">
                <p class="kicker">Stoc</p>
                <h2>Evidența mărfurilor</h2>
                <p class="form-note">Comandate sunt comenzile clienților încă nelivrate. Vândute sunt cele livrate. Intrate le notezi când primești marfa. Stocul este intrate minus vândute.</p>
                <form class="crm-form" method="post" action="<?= e(url('admin.php#marfa')) ?>">
                    <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                    <input type="hidden" name="formular" value="stoc">
                    <label class="sr-only" for="marfa-produs">Produs</label>
                    <select id="marfa-produs" name="marfa" required>
                        <?php foreach (product_price_rows() as $row): ?>
                            <?php $field = product_price_field($row); ?>
                            <option value="<?= e($field) ?>"><?= e($row['produs'] . ($row['varianta'] !== '' ? ', ' . $row['varianta'] : '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="sr-only" for="marfa-cantitate">Cantitate intrată</label>
                    <input id="marfa-cantitate" name="cantitate" type="number" min="1" max="99999" step="1" value="1" required inputmode="numeric">
                    <label class="sr-only" for="marfa-nota">Notă</label>
                    <input id="marfa-nota" name="nota" type="text" maxlength="300" placeholder="Notă, opțional">
                    <button class="btn btn-dark btn-sm" type="submit">Notează intrarea</button>
                </form>
                <div class="table-wrap orders-sheet">
                    <table class="sheet-table">
                        <thead>
                            <tr>
                                <th>Produs</th>
                                <th>Variantă</th>
                                <th>Comandate</th>
                                <th>Intrate</th>
                                <th>Vândute</th>
                                <th>Stoc</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockSheet['rows'] as $row): ?>
                                <?php $available = (int) $row['intrate'] - (int) $row['vandute']; ?>
                                <tr>
                                    <td><?= e((string) $row['produs']) ?></td>
                                    <td><?= e($row['varianta'] !== '' ? (string) $row['varianta'] : '—') ?></td>
                                    <td><?= (int) $row['comandate'] ?></td>
                                    <td><?= (int) $row['intrate'] ?></td>
                                    <td><?= (int) $row['vandute'] ?></td>
                                    <td<?= $available < 0 ? ' class="stock-low"' : '' ?>><?= $available ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($stockSheet['istoric'] !== []): ?>
                    <h3>Intrări notate</h3>
                    <div class="table-wrap orders-sheet">
                        <table class="sheet-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Produs</th>
                                    <th>Cantitate</th>
                                    <th>Notă</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stockSheet['istoric'] as $move): ?>
                                    <tr>
                                        <td><?= e(date('d.m.Y', strtotime($move['la']) ?: time())) ?></td>
                                        <td><?= e($move['produs'] . ($move['varianta'] !== '' ? ', ' . $move['varianta'] : '')) ?></td>
                                        <td><?= (int) $move['cantitate'] ?></td>
                                        <td><?= e($move['nota'] !== '' ? $move['nota'] : '—') ?></td>
                                        <td>
                                            <form method="post" action="<?= e(url('admin.php#marfa')) ?>">
                                                <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                                                <input type="hidden" name="formular" value="stoc-sterge">
                                                <input type="hidden" name="id" value="<?= (int) $move['id'] ?>">
                                                <button class="btn btn-line btn-sm" type="submit">Șterge</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="price-board" id="clienti">
                <p class="kicker">Clienți</p>
                <h2>Evidența clienților</h2>
                <p class="form-note">Clienții înregistrați, compania și comenzile lor. Deschide un rând pentru istoric.</p>
                <?php if ($clientSheet['rows'] === []): ?>
                    <p>Nu există clienți înregistrați.</p>
                <?php else: ?>
                    <div class="table-wrap orders-sheet">
                        <table class="sheet-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Companie</th>
                                    <th>Contact</th>
                                    <th>Comenzi</th>
                                    <th>Ultima</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <?php foreach ($clientSheet['rows'] as $client): ?>
                                <?php
                                $clientId = (string) $client['id'];
                                $detailId = 'det-client-' . $clientId;
                                $clientOrders = $ordersByClient[$clientId] ?? [];
                                if (!$client['are_comenzi']) {
                                    $totalLabel = '—';
                                } elseif (!$client['complet'] || $client['total'] === null) {
                                    $totalLabel = 'la confirmare';
                                } else {
                                    $totalLabel = product_money($client['total']);
                                }
                                $lastLabel = $client['ultima'] === '' ? '—' : date('d.m.Y', strtotime($client['ultima']) ?: time());
                                ?>
                                <tbody>
                                    <tr class="order-summary">
                                        <td>
                                            <button class="order-toggle" type="button" aria-expanded="false" aria-controls="<?= e($detailId) ?>"><?= e($client['nume']) ?></button>
                                        </td>
                                        <td><?= e($client['companie'] !== '' ? $client['companie'] : '—') ?></td>
                                        <td>
                                            <?php if ($client['email'] !== ''): ?>
                                                <a href="mailto:<?= e($client['email']) ?>"><?= e($client['email']) ?></a>
                                            <?php endif; ?>
                                            <?php if ($client['telefon'] !== ''): ?>
                                                <span class="line-note"><?= e($client['telefon']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int) $client['comenzi'] ?></td>
                                        <td><?= e($lastLabel) ?></td>
                                        <td><?= e($totalLabel) ?></td>
                                    </tr>
                                    <tr class="order-detail" id="<?= e($detailId) ?>" hidden>
                                        <td colspan="6">
                                            <?php if ($clientOrders === []): ?>
                                                <p>Acest client nu are comenzi.</p>
                                            <?php else: ?>
                                                <?php foreach ($clientOrders as $clientOrder): ?>
                                                    <span class="line-item">
                                                        <?= e((string) $clientOrder['id']) ?>
                                                        · <?= e(date('d.m.Y', strtotime((string) ($clientOrder['la'] ?? 'now')) ?: time())) ?>
                                                        · <?= e(order_status_label((string) ($clientOrder['status'] ?? ''))) ?>
                                                    </span>
                                                    <?php foreach (($clientOrder['linii'] ?? []) as $line): ?>
                                                        <?php if (!is_array($line)) { continue; } ?>
                                                        <span class="line-note"><?= e((string) ($line['titlu'] ?? '')) ?> × <?= (int) ($line['cantitate'] ?? 0) ?></span>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (is_array($orders) && $visible === []): ?>
                <p class="lede">Nu există comenzi<?= $filter === '' ? '' : ' în starea aceasta' ?>.</p>
            <?php elseif (is_array($orders)): ?>
                <div class="table-wrap orders-sheet">
                    <table class="sheet-table">
                        <thead>
                            <tr>
                                <th>Comandă</th>
                                <th>Companie</th>
                                <th>Data</th>
                                <th>Stare</th>
                            </tr>
                        </thead>
                        <?php foreach ($visible as $order): ?>
                            <?php
                            $orderId = (string) ($order['id'] ?? '');
                            $status = (string) ($order['status'] ?? 'inregistrata');
                            $detailId = 'det-' . $orderId;
                            $company = requisite_value((string) ($order['rechizite'] ?? ''), 'Denumirea companiei');
                            ?>
                            <tbody>
                                <tr class="order-summary">
                                    <td>
                                        <button class="order-toggle" type="button" aria-expanded="false" aria-controls="<?= e($detailId) ?>"><?= e($orderId) ?></button>
                                    </td>
                                    <td><?= e($company !== '' ? $company : '—') ?></td>
                                    <td><?= e(date('d.m.Y H:i', strtotime((string) ($order['la'] ?? 'now')) ?: time())) ?></td>
                                    <td><span class="status status-<?= e($status) ?>"><?= e(order_status_label($status)) ?></span></td>
                                </tr>
                                <tr class="order-detail" id="<?= e($detailId) ?>" hidden>
                                    <td colspan="4">
                                        <table class="sheet-table order-more">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">Client</th>
                                                    <td>
                                                        <span class="line-item"><?= e((string) ($order['nume'] ?? 'Client')) ?></span>
                                                        <?php if (($order['email'] ?? '') !== ''): ?>
                                                            <span class="line-item"><a href="mailto:<?= e((string) $order['email']) ?>"><?= e((string) $order['email']) ?></a></span>
                                                        <?php endif; ?>
                                                        <?php if (($order['telefon'] ?? '') !== ''): ?>
                                                            <span class="line-note"><?= e((string) $order['telefon']) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Produse</th>
                                                    <td>
                                                        <?php foreach (($order['linii'] ?? []) as $line): ?>
                                                            <?php if (!is_array($line)) { continue; } ?>
                                                            <span class="line-item"><?= e((string) ($line['titlu'] ?? '')) ?> × <?= (int) ($line['cantitate'] ?? 0) ?></span>
                                                        <?php endforeach; ?>
                                                        <?php if (($order['nota'] ?? '') !== ''): ?>
                                                            <span class="line-note"><?= e((string) $order['nota']) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Adresă</th>
                                                    <td><?= e((string) ($order['adresa'] ?? '')) ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Rechizite</th>
                                                    <td class="requisites"><?= e((string) ($order['rechizite'] ?? '')) ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Document</th>
                                                    <td><a class="invoice-link" href="<?= e(url('cont-de-plata.php?id=' . rawurlencode($orderId))) ?>">Cont de plată</a></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Stare</th>
                                                    <td>
                                                        <form class="status-form" method="post" action="<?= e(url('admin.php')) ?>">
                                                            <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
                                                            <input type="hidden" name="formular" value="stare">
                                                            <input type="hidden" name="id" value="<?= e($orderId) ?>">
                                                            <input type="hidden" name="filtru" value="<?= e($filter) ?>">
                                                            <label class="sr-only" for="status-<?= e($orderId) ?>">Stare</label>
                                                            <select id="status-<?= e($orderId) ?>" name="status">
                                                                <?php foreach (order_statuses() as $key => $label): ?>
                                                                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button class="btn btn-dark btn-sm" type="submit">Salvează</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
            <script>
                document.querySelectorAll('.order-summary').forEach(function (row) {
                    row.addEventListener('click', function (event) {
                        var target = event.target && event.target.closest ? event.target : event.target.parentElement;
                        if (!target) {
                            return;
                        }
                        if (target.closest('a, input, select, textarea, label') || (target.closest('button') && !target.closest('.order-toggle'))) {
                            return;
                        }
                        var button = row.querySelector('.order-toggle');
                        if (!button) {
                            return;
                        }
                        var panel = document.getElementById(button.getAttribute('aria-controls'));
                        if (!panel) {
                            return;
                        }
                        var open = button.getAttribute('aria-expanded') === 'true';
                        button.setAttribute('aria-expanded', open ? 'false' : 'true');
                        panel.hidden = open;
                        row.classList.toggle('is-open', !open);
                    });
                });
                var opened = location.hash.replace('#', '');
                if (opened) {
                    var current = document.getElementById('det-' + opened);
                    if (current) {
                        var summary = current.previousElementSibling;
                        if (summary) {
                            summary.click();
                        }
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
