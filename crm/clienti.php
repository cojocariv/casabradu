<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
crm_require_login();

$error = '';
$showForm = isset($_GET['nou']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!crm_csrf_ok((string) ($_POST['csrf'] ?? ''))) {
        $error = 'Sesiunea a expirat.';
        $showForm = true;
    } else {
        try {
            $id = crm_client_save($_POST);
            crm_flash_set('Clientul a fost salvat.');
            header('Location: ' . crm_url('client.php?id=' . $id));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            $showForm = true;
        }
    }
}

$q = crm_text((string) ($_GET['q'] ?? ''), 80);
$status = crm_text((string) ($_GET['status'] ?? ''), 20);
$rows = crm_clients_list($q, $status);

crm_header('Clienți', 'clienti');
?>
<div class="crm-head">
    <div>
        <h1>Clienți</h1>
        <p>Contacte, preț special, aparate în comodat — ca în foaia Google.</p>
    </div>
    <a class="crm-btn" href="<?= crm_e(crm_url('clienti.php?nou=1')) ?>">Client nou</a>
</div>

<?php if ($error !== ''): ?>
    <p class="crm-flash crm-flash-error" role="alert"><?= crm_e($error) ?></p>
<?php endif; ?>

<?php if ($showForm): ?>
<section class="crm-card">
    <h2>Client nou</h2>
    <form class="crm-form" method="post" action="<?= crm_e(crm_url('clienti.php?nou=1')) ?>">
        <input type="hidden" name="csrf" value="<?= crm_e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <div class="crm-form-grid">
            <label class="full">Companie / client *
                <input name="companie" required maxlength="160" value="<?= crm_e((string) ($_POST['companie'] ?? '')) ?>">
            </label>
            <label>Persoană de contact
                <input name="contact" maxlength="120" value="<?= crm_e((string) ($_POST['contact'] ?? '')) ?>">
            </label>
            <label>Telefon
                <input name="telefon" maxlength="80" value="<?= crm_e((string) ($_POST['telefon'] ?? '')) ?>">
            </label>
            <label>Preț / cod / notă (ex: 1170 sau 8 lei capsula)
                <input name="pret_nota" maxlength="120" value="<?= crm_e((string) ($_POST['pret_nota'] ?? '')) ?>">
            </label>
            <label>Status
                <select name="status">
                    <?php foreach (crm_status_labels() as $key => $label): ?>
                        <option value="<?= crm_e($key) ?>"<?= ((string) ($_POST['status'] ?? 'activ') === $key) ? ' selected' : '' ?>><?= crm_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="full">Note
                <textarea name="note" rows="3"><?= crm_e((string) ($_POST['note'] ?? '')) ?></textarea>
            </label>
        </div>
        <div class="crm-actions">
            <button class="crm-btn" type="submit">Salvează</button>
            <a class="crm-btn crm-btn-line" href="<?= crm_e(crm_url('clienti.php')) ?>">Anulează</a>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="crm-card">
    <form class="crm-toolbar" method="get" action="<?= crm_e(crm_url('clienti.php')) ?>">
        <input type="search" name="q" placeholder="Caută companie, telefon, notă…" value="<?= crm_e($q) ?>">
        <select name="status">
            <option value="">Toate statusurile</option>
            <?php foreach (crm_status_labels() as $key => $label): ?>
                <option value="<?= crm_e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= crm_e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="crm-btn crm-btn-line" type="submit">Filtrează</button>
    </form>
    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Preț / notă</th>
                    <th>Companie</th>
                    <th>Contact</th>
                    <th>Telefon</th>
                    <th>Aparate comodat</th>
                    <th>Ultima vânzare</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="7" class="crm-muted">Niciun client pe aceste filtre.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $client): ?>
                    <?php $st = (string) ($client['status'] ?? 'activ'); ?>
                    <tr>
                        <td><?= crm_e((string) $client['pret_nota']) ?></td>
                        <td><a href="<?= crm_e(crm_url('client.php?id=' . (int) $client['id'])) ?>"><?= crm_e((string) $client['companie']) ?></a></td>
                        <td><?= crm_e((string) $client['contact']) ?></td>
                        <td><?= crm_e((string) $client['telefon']) ?></td>
                        <td><?= (int) ($client['aparate_nr'] ?? 0) ?></td>
                        <td><?= crm_e((string) ($client['ultima_vanzare'] ?: '—')) ?></td>
                        <td><span class="crm-badge crm-badge-<?= crm_e($st) ?>"><?= crm_e(crm_status_labels()[$st] ?? $st) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php crm_footer(); ?>
