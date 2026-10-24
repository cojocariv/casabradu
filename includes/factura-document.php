<!DOCTYPE html>
<html lang="<?= lang() === 'ru' ? 'ru' : 'ro' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e(sprintf(t('Cont de plată nr. %s din %s'), (string) $order['id'], $invoiceDate)) ?> · <?= e(SITE_NAME) ?></title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e7e2d8;
            color: #1a1a1a;
            font-family: "Times New Roman", Times, serif;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            padding: 16px;
        }
        .toolbar form { display: contents; }
        .mail-note {
            width: min(820px, calc(100% - 24px));
            margin: 16px auto 0;
            text-align: center;
            font-family: "Source Sans 3", "Segoe UI", sans-serif;
        }
        .mail-note.ok { color: #1c3b30; }
        .mail-note.err { color: #7a3328; }
        .toolbar a, .toolbar button {
            font-family: "Source Sans 3", "Segoe UI", sans-serif;
            border-radius: 999px;
            padding: 10px 18px;
            border: 1px solid #16352b;
            background: #16352b;
            color: #f6f1e6;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .toolbar a { background: transparent; color: #16352b; }
        .sheet {
            width: min(820px, calc(100% - 24px));
            margin: 0 auto 28px;
            background: #fff;
            padding: 28px 34px 40px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        }
        .brand { text-align: center; margin-bottom: 8px; }
        .brand img { height: 128px; width: auto; }
        h1 {
            margin: 14px 0 16px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
        }
        .rule { border: 0; border-top: 1px solid #222; margin: 8px 0 12px; }
        .parties {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
            font-size: 14px;
            line-height: 1.45;
        }
        .parties p, .payer p { margin: 0 0 2px; }
        .payer { margin-top: 14px; font-size: 14px; line-height: 1.45; }
        .warn { margin: 16px 0; text-align: center; font-size: 14px; }
        .warn strong { display: block; margin-bottom: 6px; letter-spacing: 0.04em; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border: 1px solid #222; padding: 6px 8px; vertical-align: top; }
        th { font-weight: 700; text-align: center; }
        td.num, td.qty, td.money { text-align: right; white-space: nowrap; }
        td.num, td.qty { text-align: center; }
        .totals { margin-top: 14px; font-size: 15px; }
        .totals p { margin: 0 0 4px; }
        .sign { margin-top: 28px; font-size: 15px; }
        .sign span { display: inline-block; min-width: 220px; border-bottom: 1px solid #222; margin: 0 8px; }
        @media (max-width: 640px) {
            .parties { grid-template-columns: 1fr; }
            .sheet { padding: 18px 14px 28px; }
            .sign span { min-width: 90px; }
        }
        @media print {
            body { background: #fff; }
            .toolbar, .mail-note { display: none; }
            .sheet { width: auto; margin: 0; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    <?php if (($invoiceNotice ?? '') !== ''): ?>
        <p class="mail-note ok" role="status"><?= e(t($invoiceNotice)) ?></p>
    <?php endif; ?>
    <?php if (($invoiceError ?? '') !== ''): ?>
        <p class="mail-note err" role="alert"><?= e(t($invoiceError)) ?></p>
    <?php endif; ?>
    <div class="toolbar">
        <a href="<?= e($back) ?>"><?= tr('Înapoi la cabinet') ?></a>
        <button type="button" onclick="window.print()"><?= tr('Printează sau salvează PDF') ?></button>
        <form method="post" action="<?= e(url('cont-de-plata.php?id=' . rawurlencode((string) ($order['id'] ?? '')))) ?>">
            <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
            <input type="hidden" name="actiune" value="pdf">
            <button type="submit">Descarcă PDF</button>
        </form>
        <form method="post" action="<?= e(url('cont-de-plata.php?id=' . rawurlencode((string) ($order['id'] ?? '')))) ?>">
            <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
            <input type="hidden" name="actiune" value="email">
            <button type="submit"><?= tr('Trimite contul prin email') ?></button>
        </form>
    </div>
    <article class="sheet">
        <div class="brand">
            <img src="<?= e($logo) ?>" alt="<?= e(SITE_NAME) ?>">
        </div>
        <hr class="rule">
        <h1><?= e(sprintf(t('Cont de plată nr. %s din %s'), (string) $order['id'], $invoiceDate)) ?></h1>
        <hr class="rule">
        <div class="parties">
            <div>
                <p><strong><?= tr('Furnizor:') ?></strong> <?= e(COMPANY_LEGAL) ?></p>
                <p><strong>IDNO:</strong> <?= e(COMPANY_IDNO) ?></p>
                <p><strong>TVA:</strong> <?= e(COMPANY_TVA) ?></p>
            </div>
            <div>
                <p><strong><?= tr('Rechizite bancare:') ?></strong> <?= e(COMPANY_BANK) ?></p>
                <p><strong>IBAN:</strong> <?= e(COMPANY_IBAN) ?></p>
            </div>
        </div>
        <div class="payer">
            <p><strong><?= tr('Plătitor:') ?></strong> <?= e($payer['nume']) ?></p>
            <?php if ($payer['idno'] !== ''): ?><p><strong>IDNO:</strong> <?= e($payer['idno']) ?></p><?php endif; ?>
            <?php if ($payer['tva'] !== ''): ?><p><strong>TVA:</strong> <?= e($payer['tva']) ?></p><?php endif; ?>
            <?php if ($payer['adresa'] !== ''): ?><p><strong><?= tr('Adresă:') ?></strong> <?= e($payer['adresa']) ?></p><?php endif; ?>
        </div>
        <hr class="rule">
        <div class="warn">
            <strong><?= tr('ATENȚIE') ?></strong>
            <p><?= tr('Înainte de a achita, contactează-ne și verifică dacă produsele comandate sunt disponibile. Contul este valabil 3 zile bancare. Livrarea se face numai după încasarea sumei în contul nostru.') ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th><?= tr('Nr.') ?></th>
                    <th><?= tr('Denumirea mărfii') ?></th>
                    <th><?= tr('Cant.') ?></th>
                    <th><?= tr('Preț, lei') ?></th>
                    <th><?= tr('Suma, lei') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table['rows'] as $index => $row): ?>
                    <tr>
                        <td class="num"><?= $index + 1 ?></td>
                        <td><?= tr($row['titlu']) ?></td>
                        <td class="qty"><?= (int) $row['cantitate'] ?></td>
                        <td class="money"><?= e(($money = lei($row['pret'])) === 'la confirmare' ? t('la confirmare') : $money) ?></td>
                        <td class="money"><?= e(($money = lei($row['suma'])) === 'la confirmare' ? t('la confirmare') : $money) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="totals">
            <p><strong><?= tr('Total de plată') ?>: <?= e($table['total'] === null ? t('la confirmare') : lei($table['total']) . ' ' . t('lei')) ?></strong></p>
            <?php if ($table['tva'] !== null): ?>
                <p><?= tr('Inclusiv TVA') ?>: <?= e(lei($table['tva'])) ?> <?= tr('lei') ?></p>
            <?php endif; ?>
        </div>
        <p class="sign"><?= tr('Administrator:') ?> <span></span> / Cojocari Tatiana /</p>
    </article>
</body>
</html>

