<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
crm_require_login();

$error = '';
$showForm = isset($_GET['nou']);
$clients = crm_clients_list();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!crm_csrf_ok((string) ($_POST['csrf'] ?? ''))) {
        $error = 'Sesiunea a expirat.';
        $showForm = true;
    } else {
        $act = crm_text((string) ($_POST['act'] ?? 'adauga'), 20);
        try {
            if ($act === 'sterge') {
                crm_sale_delete((int) ($_POST['id'] ?? 0));
                crm_flash_set('Vânzarea a fost ștearsă.');
                header('Location: ' . crm_url('vanzari.php'));
                exit;
            }
            $clientId = (int) ($_POST['client_id'] ?? 0);
            if ($clientId < 1 && crm_text((string) ($_POST['companie'] ?? ''), 160) !== '') {
                $found = crm_find_client_id_by_company((string) $_POST['companie']);
                if ($found !== null) {
                    $_POST['client_id'] = (string) $found;
                }
            }
            crm_sale_add($_POST);
            crm_flash_set('Vânzarea a fost înregistrată.');
            header('Location: ' . crm_url('vanzari.php'));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $showForm = true;
        }
    }
}

$from = crm_text((string) ($_GET['de_la'] ?? date('Y-m-01')), 10);
$to = crm_text((string) ($_GET['pana_la'] ?? date('Y-m-d')), 10);
$q = crm_text((string) ($_GET['q'] ?? ''), 80);
$tipPlata = crm_text((string) ($_GET['tip_plata'] ?? ''), 20);
$rows = crm_sales_list($from, $to, $q, $tipPlata);

$sumTotal = 0.0;
$sumCash = 0.0;
$sumOficial = 0.0;
$sumNeoficial = 0.0;
foreach ($rows as $row) {
    if ($row['total'] !== null) {
        $sumTotal += (float) $row['total'];
        if ((string) ($row['tip_plata'] ?? '') === 'neoficiala') {
            $sumNeoficial += (float) $row['total'];
        } else {
            $sumOficial += (float) $row['total'];
        }
    }
    if ($row['cash'] !== null) {
        $sumCash += (float) $row['cash'];
    }
}

crm_header('Vânzări', 'vanzari');
?>
<div class="crm-head">
    <div>
        <h1>Vânzări</h1>
        <p>Cialde, capsule, boabe, pahare — pe luni, ca în foaie.</p>
    </div>
    <a class="crm-btn" href="<?= crm_e(crm_url('vanzari.php?nou=1')) ?>">Vânzare nouă</a>
</div>

<?php if ($error !== ''): ?>
    <p class="crm-flash crm-flash-error" role="alert"><?= crm_e($error) ?></p>
<?php endif; ?>

<?php if ($showForm): ?>
<section class="crm-card">
    <h2>Vânzare nouă</h2>
    <form class="crm-form" method="post" action="<?= crm_e(crm_url('vanzari.php?nou=1')) ?>">
        <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <input type="hidden" name="act" value="adauga">
        <div class="crm-form-grid">
            <label class="full">Client existent (opțional)
                <select name="client_id">
                    <option value="0">— alege sau completează mai jos —</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int) $client['id'] ?>"<?= ((int) ($_POST['client_id'] ?? 0) === (int) $client['id']) ? ' selected' : '' ?>>
                            <?= crm_e((string) $client['companie']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Companie
                <input name="companie" maxlength="160" value="<?= crm_e((string) ($_POST['companie'] ?? '')) ?>">
            </label>
            <label>Contact
                <input name="contact" maxlength="120" value="<?= crm_e((string) ($_POST['contact'] ?? '')) ?>">
            </label>
            <label>Telefon
                <input name="telefon" maxlength="80" value="<?= crm_e((string) ($_POST['telefon'] ?? '')) ?>">
            </label>
            <label>Produs *
                <select name="produs" required>
                    <?php foreach (crm_produs_options() as $key => $label): ?>
                        <option value="<?= crm_e($key) ?>"<?= ((string) ($_POST['produs'] ?? 'cialde') === $key) ? ' selected' : '' ?>><?= crm_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Cutii
                <input name="cutii" value="<?= crm_e((string) ($_POST['cutii'] ?? '1')) ?>">
            </label>
            <label>Tip plată *
                <select name="tip_plata" required>
                    <?php foreach (crm_tip_plata_options() as $key => $label): ?>
                        <option value="<?= crm_e($key) ?>"<?= ((string) ($_POST['tip_plata'] ?? 'oficiala') === $key) ? ' selected' : '' ?>><?= crm_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Detaliu plată
                <input name="plata" maxlength="80" placeholder="ff + transfer / fara cec / cash" value="<?= crm_e((string) ($_POST['plata'] ?? '')) ?>">
            </label>
            <label>Total
                <input name="total" value="<?= crm_e((string) ($_POST['total'] ?? '')) ?>">
            </label>
            <label>Preț special
                <input name="pret_special" value="<?= crm_e((string) ($_POST['pret_special'] ?? '')) ?>">
            </label>
            <label>Cash adunat
                <input name="cash" value="<?= crm_e((string) ($_POST['cash'] ?? '')) ?>">
            </label>
            <label>Data
                <input type="date" name="la" value="<?= crm_e((string) ($_POST['la'] ?? date('Y-m-d'))) ?>">
            </label>
            <label class="full">Notă
                <textarea name="note" rows="2"><?= crm_e((string) ($_POST['note'] ?? '')) ?></textarea>
            </label>
        </div>
        <div class="crm-actions">
            <button class="crm-btn" type="submit">Salvează vânzarea</button>
            <a class="crm-btn crm-btn-line" href="<?= crm_e(crm_url('vanzari.php')) ?>">Anulează</a>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="crm-card">
    <form class="crm-toolbar" method="get" action="<?= crm_e(crm_url('vanzari.php')) ?>">
        <label>De la <input type="date" name="de_la" value="<?= crm_e($from) ?>"></label>
        <label>Până la <input type="date" name="pana_la" value="<?= crm_e($to) ?>"></label>
        <select name="tip_plata">
            <option value="">Toate plățile</option>
            <?php foreach (crm_tip_plata_options() as $key => $label): ?>
                <option value="<?= crm_e($key) ?>"<?= $tipPlata === $key ? ' selected' : '' ?>><?= crm_e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" placeholder="Caută…" value="<?= crm_e($q) ?>">
        <button class="crm-btn crm-btn-line" type="submit">Filtrează</button>
    </form>
    <p class="crm-muted">
        Total: <strong><?= crm_e(crm_money_format($sumTotal)) ?></strong>
        · Oficial: <strong><?= crm_e(crm_money_format($sumOficial)) ?></strong>
        · Neoficial: <strong><?= crm_e(crm_money_format($sumNeoficial)) ?></strong>
        · Cash: <strong><?= crm_e(crm_money_format($sumCash)) ?></strong>
        · <?= count($rows) ?> înregistrări
    </p>
    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Companie</th>
                    <th>Contact</th>
                    <th>Telefon</th>
                    <th>Produs</th>
                    <th>Cutii</th>
                    <th>Tip plată</th>
                    <th>Detaliu</th>
                    <th>Total</th>
                    <th>Preț spec.</th>
                    <th>Cash</th>
                    <th>Notă</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="13" class="crm-muted">Nicio vânzare pe perioada selectată.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $sale): ?>
                    <?php $tp = (string) ($sale['tip_plata'] ?? 'oficiala'); ?>
                    <tr>
                        <td><?= crm_e((string) $sale['la']) ?></td>
                        <td>
                            <?php if (!empty($sale['client_id'])): ?>
                                <a href="<?= crm_e(crm_url('client.php?id=' . (int) $sale['client_id'])) ?>">
                                    <?= crm_e((string) ($sale['client_companie'] ?: $sale['companie'])) ?>
                                </a>
                            <?php else: ?>
                                <?= crm_e((string) $sale['companie']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= crm_e((string) $sale['contact']) ?></td>
                        <td><?= crm_e((string) $sale['telefon']) ?></td>
                        <td><?= crm_e((string) $sale['produs']) ?></td>
                        <td><?= crm_e(rtrim(rtrim(number_format((float) $sale['cutii'], 2, ',', ' '), '0'), ',')) ?></td>
                        <td><span class="crm-badge crm-badge-<?= $tp === 'neoficiala' ? 'neoficiala' : 'oficiala' ?>"><?= crm_e(crm_tip_plata_label($tp)) ?></span></td>
                        <td><?= crm_e((string) $sale['plata']) ?></td>
                        <td><?= crm_e(crm_money_format($sale['total'] !== null ? (float) $sale['total'] : null)) ?></td>
                        <td><?= crm_e(crm_money_format($sale['pret_special'] !== null ? (float) $sale['pret_special'] : null)) ?></td>
                        <td><?= crm_e(crm_money_format($sale['cash'] !== null ? (float) $sale['cash'] : null)) ?></td>
                        <td><?= crm_e((string) $sale['note']) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Ștergi vânzarea?');">
                                <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
                                <input type="hidden" name="act" value="sterge">
                                <input type="hidden" name="id" value="<?= (int) $sale['id'] ?>">
                                <button class="crm-btn crm-btn-danger crm-btn-sm" type="submit">Șterge</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php crm_footer(); ?>
