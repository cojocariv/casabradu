<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
crm_require_login();

$id = (int) ($_GET['id'] ?? 0);
$client = $id > 0 ? crm_client_get($id) : null;
if ($client === null) {
    crm_flash_set('Clientul nu a fost găsit.');
    header('Location: ' . crm_url('clienti.php'));
    exit;
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!crm_csrf_ok((string) ($_POST['csrf'] ?? ''))) {
        $error = 'Sesiunea a expirat.';
    } else {
        $act = crm_text((string) ($_POST['act'] ?? ''), 30);
        try {
            if ($act === 'salveaza') {
                crm_client_save($_POST, $id);
                crm_flash_set('Datele clientului au fost actualizate.');
                header('Location: ' . crm_url('client.php?id=' . $id));
                exit;
            }
            if ($act === 'aparat') {
                crm_machine_add($id, $_POST);
                crm_flash_set('Aparatul a fost adăugat.');
                header('Location: ' . crm_url('client.php?id=' . $id . '#aparate'));
                exit;
            }
            if ($act === 'sterge_aparat') {
                crm_machine_delete((int) ($_POST['aparat_id'] ?? 0), $id);
                crm_flash_set('Aparatul a fost șters.');
                header('Location: ' . crm_url('client.php?id=' . $id . '#aparate'));
                exit;
            }
            if ($act === 'vanzare') {
                $_POST['client_id'] = (string) $id;
                crm_sale_add($_POST);
                crm_flash_set('Vânzarea a fost înregistrată.');
                header('Location: ' . crm_url('client.php?id=' . $id . '#vanzari'));
                exit;
            }
            if ($act === 'sterge_client') {
                crm_client_delete($id);
                crm_flash_set('Clientul a fost șters.');
                header('Location: ' . crm_url('clienti.php'));
                exit;
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
        $client = crm_client_get($id) ?? $client;
    }
}

$machines = crm_machines_for($id);
$sales = crm_sales_for_client($id);
$st = (string) ($client['status'] ?? 'activ');

crm_header((string) $client['companie'], 'clienti');
?>
<div class="crm-head">
    <div>
        <h1><?= crm_e((string) $client['companie']) ?></h1>
        <p>
            <span class="crm-badge crm-badge-<?= crm_e($st) ?>"><?= crm_e(crm_status_labels()[$st] ?? $st) ?></span>
            · <?= crm_e((string) $client['contact']) ?>
            · <?= crm_e((string) $client['telefon']) ?>
        </p>
    </div>
    <a class="crm-btn crm-btn-line" href="<?= crm_e(crm_url('clienti.php')) ?>">Înapoi la clienți</a>
</div>

<?php if ($error !== ''): ?>
    <p class="crm-flash crm-flash-error" role="alert"><?= crm_e($error) ?></p>
<?php endif; ?>

<section class="crm-card">
    <h2>Date client</h2>
    <form class="crm-form" method="post">
        <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <input type="hidden" name="act" value="salveaza">
        <div class="crm-form-grid">
            <label class="full">Companie / client *
                <input name="companie" required maxlength="160" value="<?= crm_e((string) $client['companie']) ?>">
            </label>
            <label>Persoană de contact
                <input name="contact" maxlength="120" value="<?= crm_e((string) $client['contact']) ?>">
            </label>
            <label>Telefon
                <input name="telefon" maxlength="80" value="<?= crm_e((string) $client['telefon']) ?>">
            </label>
            <label>Preț / cod / notă
                <input name="pret_nota" maxlength="120" value="<?= crm_e((string) $client['pret_nota']) ?>">
            </label>
            <label>Status
                <select name="status">
                    <?php foreach (crm_status_labels() as $key => $label): ?>
                        <option value="<?= crm_e($key) ?>"<?= $st === $key ? ' selected' : '' ?>><?= crm_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="full">Note
                <textarea name="note" rows="3"><?= crm_e((string) $client['note']) ?></textarea>
            </label>
        </div>
        <div class="crm-actions">
            <button class="crm-btn" type="submit">Salvează</button>
        </div>
    </form>
</section>

<section class="crm-card" id="aparate">
    <h2>Aparate în comodat / evidență</h2>
    <form class="crm-form" method="post" style="margin-bottom:16px">
        <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <input type="hidden" name="act" value="aparat">
        <div class="crm-form-grid">
            <label class="full">Model aparat *
                <input name="model" required maxlength="160" placeholder="ex: Aroma Plus / Spinell Pinocchio / Nespresso">
            </label>
            <label>Tip
                <select name="tip">
                    <?php foreach (crm_aparat_tipuri() as $key => $label): ?>
                        <option value="<?= crm_e($key) ?>"><?= crm_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Cantitate
                <input type="number" name="cantitate" min="1" max="99" value="1">
            </label>
            <label>Stare
                <select name="stare">
                    <option value="comodat">Comodat</option>
                    <option value="vandut">Vândut</option>
                    <option value="returnat">Returnat</option>
                </select>
            </label>
            <label>Data
                <input type="date" name="la" value="<?= crm_e(date('Y-m-d')) ?>">
            </label>
            <label class="full">Notă
                <input name="note" maxlength="255" placeholder="ex: de dus spinel">
            </label>
        </div>
        <button class="crm-btn" type="submit">Adaugă aparat</button>
    </form>
    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Model</th>
                    <th>Tip</th>
                    <th>Cant.</th>
                    <th>Stare</th>
                    <th>Data</th>
                    <th>Notă</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($machines === []): ?>
                <tr><td colspan="7" class="crm-muted">Niciun aparat înregistrat.</td></tr>
            <?php else: ?>
                <?php foreach ($machines as $machine): ?>
                    <tr>
                        <td><?= crm_e((string) $machine['model']) ?></td>
                        <td><?= crm_e(crm_aparat_tipuri()[(string) $machine['tip']] ?? (string) $machine['tip']) ?></td>
                        <td><?= (int) $machine['cantitate'] ?></td>
                        <td><?= crm_e((string) $machine['stare']) ?></td>
                        <td><?= crm_e((string) $machine['la']) ?></td>
                        <td><?= crm_e((string) $machine['note']) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Ștergi aparatul?');">
                                <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
                                <input type="hidden" name="act" value="sterge_aparat">
                                <input type="hidden" name="aparat_id" value="<?= (int) $machine['id'] ?>">
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

<section class="crm-card" id="vanzari">
    <h2>Vânzări cafea / consumabile</h2>
    <form class="crm-form" method="post" style="margin-bottom:16px">
        <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <input type="hidden" name="act" value="vanzare">
        <div class="crm-form-grid">
            <label>Produs *
                <select name="produs" required>
                    <?php foreach (crm_produs_options() as $key => $label): ?>
                        <option value="<?= crm_e($key) ?>"><?= crm_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Cutii / cantitate
                <input name="cutii" value="1">
            </label>
            <label>Tip plată *
                <select name="tip_plata" required>
                    <?php foreach (crm_tip_plata_options() as $key => $label): ?>
                        <option value="<?= crm_e($key) ?>"><?= crm_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Detaliu plată
                <input name="plata" maxlength="80" placeholder="ff + transfer / fara cec / cash">
            </label>
            <label>Total
                <input name="total" placeholder="3600">
            </label>
            <label>Preț special
                <input name="pret_special">
            </label>
            <label>Cash adunat
                <input name="cash">
            </label>
            <label>Data
                <input type="date" name="la" value="<?= crm_e(date('Y-m-d')) ?>">
            </label>
            <label class="full">Notă
                <input name="note" maxlength="500">
            </label>
        </div>
        <button class="crm-btn" type="submit">Adaugă vânzare</button>
    </form>
    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produs</th>
                    <th>Cutii</th>
                    <th>Tip plată</th>
                    <th>Detaliu</th>
                    <th>Total</th>
                    <th>Cash</th>
                    <th>Notă</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($sales === []): ?>
                <tr><td colspan="8" class="crm-muted">Nicio vânzare pentru acest client.</td></tr>
            <?php else: ?>
                <?php foreach ($sales as $sale): ?>
                    <?php $tp = (string) ($sale['tip_plata'] ?? 'oficiala'); ?>
                    <tr>
                        <td><?= crm_e((string) $sale['la']) ?></td>
                        <td><?= crm_e((string) $sale['produs']) ?></td>
                        <td><?= crm_e(rtrim(rtrim(number_format((float) $sale['cutii'], 2, ',', ' '), '0'), ',')) ?></td>
                        <td><span class="crm-badge crm-badge-<?= $tp === 'neoficiala' ? 'neoficiala' : 'oficiala' ?>"><?= crm_e(crm_tip_plata_label($tp)) ?></span></td>
                        <td><?= crm_e((string) $sale['plata']) ?></td>
                        <td><?= crm_e(crm_money_format($sale['total'] !== null ? (float) $sale['total'] : null)) ?></td>
                        <td><?= crm_e(crm_money_format($sale['cash'] !== null ? (float) $sale['cash'] : null)) ?></td>
                        <td><?= crm_e((string) $sale['note']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="crm-card">
    <h2>Zonă periculoasă</h2>
    <form method="post" onsubmit="return confirm('Ștergi definitiv clientul și aparatele asociate?');">
        <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <input type="hidden" name="act" value="sterge_client">
        <button class="crm-btn crm-btn-danger" type="submit">Șterge clientul</button>
    </form>
</section>
<?php crm_footer(); ?>
