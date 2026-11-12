<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
crm_require_login();

$stats = crm_stats();
$recentSales = crm_sales_list(date('Y-m-01'), date('Y-m-d'));
$recentClients = array_slice(crm_clients_list(), 0, 8);

crm_header('Panou', 'acasa');
?>
<div class="crm-head">
    <div>
        <h1>Panou CRM</h1>
        <p>Clienți, aparate în comodat și vânzări de cafea.</p>
    </div>
    <div class="crm-actions">
        <a class="crm-btn" href="<?= crm_e(crm_url('clienti.php?nou=1')) ?>">Client nou</a>
        <a class="crm-btn crm-btn-line" href="<?= crm_e(crm_url('vanzari.php?nou=1')) ?>">Vânzare nouă</a>
    </div>
</div>

<div class="crm-stats">
    <div class="crm-stat">
        <span>Clienți</span>
        <strong><?= (int) $stats['clienti'] ?></strong>
    </div>
    <div class="crm-stat">
        <span>Aparate în comodat</span>
        <strong><?= (int) $stats['aparate_comodat'] ?></strong>
    </div>
    <div class="crm-stat">
        <span>Vânzări luna asta</span>
        <strong><?= crm_e(crm_money_format((float) $stats['vanzari_luna'])) ?></strong>
    </div>
    <div class="crm-stat">
        <span>Cash luna asta</span>
        <strong><?= crm_e(crm_money_format((float) $stats['cash_luna'])) ?></strong>
    </div>
</div>

<section class="crm-card">
    <h2>Vânzări luna curentă (<?= (int) $stats['livrari_luna'] ?>)</h2>
    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Companie</th>
                    <th>Produs</th>
                    <th>Cutii</th>
                    <th>Plată</th>
                    <th>Total</th>
                    <th>Cash</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recentSales === []): ?>
                <tr><td colspan="7" class="crm-muted">Nicio vânzare înregistrată luna asta.</td></tr>
            <?php else: ?>
                <?php foreach ($recentSales as $sale): ?>
                    <tr>
                        <td><?= crm_e((string) $sale['la']) ?></td>
                        <td><?= crm_e((string) ($sale['client_companie'] ?: $sale['companie'])) ?></td>
                        <td><?= crm_e((string) $sale['produs']) ?></td>
                        <td><?= crm_e(rtrim(rtrim(number_format((float) $sale['cutii'], 2, ',', ' '), '0'), ',')) ?></td>
                        <td><?= crm_e((string) $sale['plata']) ?></td>
                        <td><?= crm_e(crm_money_format($sale['total'] !== null ? (float) $sale['total'] : null)) ?></td>
                        <td><?= crm_e(crm_money_format($sale['cash'] !== null ? (float) $sale['cash'] : null)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="crm-card">
    <h2>Clienți recenți / listă</h2>
    <div class="crm-table-wrap">
        <table class="crm-table">
            <thead>
                <tr>
                    <th>Companie</th>
                    <th>Contact</th>
                    <th>Telefon</th>
                    <th>Preț / notă</th>
                    <th>Aparate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recentClients === []): ?>
                <tr><td colspan="6" class="crm-muted">Nu există clienți. Adaugă primul client.</td></tr>
            <?php else: ?>
                <?php foreach ($recentClients as $client): ?>
                    <?php $st = (string) ($client['status'] ?? 'activ'); ?>
                    <tr>
                        <td><a href="<?= crm_e(crm_url('client.php?id=' . (int) $client['id'])) ?>"><?= crm_e((string) $client['companie']) ?></a></td>
                        <td><?= crm_e((string) $client['contact']) ?></td>
                        <td><?= crm_e((string) $client['telefon']) ?></td>
                        <td><?= crm_e((string) $client['pret_nota']) ?></td>
                        <td><?= (int) ($client['aparate_nr'] ?? 0) ?></td>
                        <td><span class="crm-badge crm-badge-<?= crm_e($st) ?>"><?= crm_e(crm_status_labels()[$st] ?? $st) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php crm_footer(); ?>
