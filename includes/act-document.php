<?php

declare(strict_types=1);

/** @var array $order */
/** @var array $table */
/** @var array $supplier */
/** @var array $receiver */
/** @var string $actDate */
/** @var string $actPlace */
/** @var string $back */

$logo = asset('assets/img/logo.jpg?v=4');
$totalBani = $table['total'] ?? null;
$totalWords = is_int($totalBani) ? act_lei_words($totalBani) : '';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Act de predare-primire nr. <?= e((string) ($order['id'] ?? '')) ?> · <?= e(SITE_NAME) ?></title>
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
        .toolbar a, .toolbar button {
            font-family: Outfit, "Segoe UI", sans-serif;
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
            width: min(860px, calc(100% - 24px));
            margin: 0 auto 28px;
            background: #fff;
            padding: 28px 34px 40px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        }
        .brand { text-align: center; margin-bottom: 8px; }
        .brand img { max-height: 56px; width: auto; }
        h1 {
            margin: 12px 0 4px;
            text-align: center;
            font-size: 1.35rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .meta {
            text-align: center;
            margin: 0 0 18px;
            font-size: 1rem;
        }
        .rule {
            border: 0;
            border-top: 1px solid #1a1a1a;
            margin: 14px 0;
        }
        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 28px;
            margin: 0 0 16px;
            font-size: 0.98rem;
            line-height: 1.35;
        }
        .parties p { margin: 0 0 4px; }
        .intro, .clause, .copies {
            font-size: 0.98rem;
            line-height: 1.45;
            margin: 0 0 12px;
            text-align: justify;
        }
        table.goods {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
            font-size: 0.95rem;
        }
        table.goods th, table.goods td {
            border: 1px solid #1a1a1a;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.goods th {
            background: #f4f0e8;
            font-weight: 700;
            text-align: center;
        }
        table.goods td.num, table.goods td.qty, table.goods td.money {
            text-align: right;
            white-space: nowrap;
        }
        table.goods td.center { text-align: center; }
        .totals {
            margin: 8px 0 16px;
            font-size: 1rem;
        }
        .totals p { margin: 4px 0; }
        .signs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 28px;
            font-size: 0.98rem;
        }
        .signs .box p { margin: 0 0 6px; }
        .sign-line {
            margin-top: 36px;
            border-top: 1px solid #1a1a1a;
            padding-top: 6px;
            font-size: 0.9rem;
        }
        .stamp {
            margin-top: 18px;
            font-size: 0.9rem;
        }
        .legal-note {
            margin-top: 22px;
            font-size: 0.82rem;
            color: #444;
            line-height: 1.35;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; width: 100%; margin: 0; padding: 0; }
        }
        @media (max-width: 700px) {
            .parties, .signs { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="<?= e($back) ?>">Înapoi</a>
        <button type="button" onclick="window.print()">Printează sau salvează PDF</button>
    </div>
    <article class="sheet">
        <div class="brand">
            <img src="<?= e($logo) ?>" alt="<?= e(SITE_NAME) ?>">
        </div>
        <hr class="rule">
        <h1>Act de predare-primire a mărfurilor</h1>
        <p class="meta">
            nr. <strong><?= e((string) ($order['id'] ?? '')) ?></strong>
            din <strong><?= e($actDate) ?></strong>,
            <?= e($actPlace) ?>
        </p>
        <hr class="rule">

        <div class="parties">
            <div>
                <p><strong>Predător (Furnizor):</strong></p>
                <p><?= e($supplier['nume']) ?></p>
                <p>IDNO: <?= e($supplier['idno']) ?></p>
                <p>TVA: <?= e($supplier['tva']) ?></p>
                <p>Adresa: <?= e($supplier['adresa']) ?></p>
                <p>IBAN: <?= e($supplier['iban']) ?></p>
                <p>Banca: <?= e($supplier['banca']) ?></p>
                <p>Email: <?= e($supplier['email']) ?></p>
            </div>
            <div>
                <p><strong>Primitor (Cumpărător):</strong></p>
                <p><?= e($receiver['nume'] !== '' ? $receiver['nume'] : '—') ?></p>
                <p>IDNO: <?= e($receiver['idno'] !== '' ? $receiver['idno'] : '—') ?></p>
                <?php if ($receiver['tva'] !== ''): ?>
                    <p>Cod TVA: <?= e($receiver['tva']) ?></p>
                <?php endif; ?>
                <p>Adresa juridică: <?= e($receiver['adresa'] !== '' ? $receiver['adresa'] : '—') ?></p>
                <?php if ($receiver['iban'] !== ''): ?>
                    <p>IBAN / banca: <?= e($receiver['iban']) ?></p>
                <?php endif; ?>
                <?php if ($receiver['contact'] !== '' && $receiver['contact'] !== $receiver['nume']): ?>
                    <p>Persoană de contact: <?= e($receiver['contact']) ?></p>
                <?php endif; ?>
                <?php if ($receiver['telefon'] !== '' && $receiver['telefon'] !== '-'): ?>
                    <p>Telefon: <?= e($receiver['telefon']) ?></p>
                <?php endif; ?>
                <?php if ($receiver['email'] !== ''): ?>
                    <p>Email: <?= e($receiver['email']) ?></p>
                <?php endif; ?>
                <?php if ($receiver['livrare'] !== ''): ?>
                    <p>Locul predării: <?= e($receiver['livrare']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <p class="intro">
            În temeiul comenzii / contului de plată nr. <strong><?= e((string) ($order['id'] ?? '')) ?></strong>
            din <strong><?= e($actDate) ?></strong>, părțile au întocmit prezentul act, prin care se confirmă că
            Predătorul a predat, iar Primitorul a primit mărfurile enumerate mai jos.
        </p>

        <table class="goods">
            <thead>
                <tr>
                    <th style="width:2.2em">Nr.</th>
                    <th>Denumirea mărfurilor</th>
                    <th style="width:4.5em">U.M.</th>
                    <th style="width:5em">Cant.</th>
                    <th style="width:6.5em">Preț unitar, lei</th>
                    <th style="width:6.5em">Valoare, lei</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($table['rows'] ?? []) as $index => $row): ?>
                    <?php
                    $line = ($order['linii'] ?? [])[$index] ?? [];
                    $um = act_unit_for_line(is_array($line) ? $line : []);
                    ?>
                    <tr>
                        <td class="center"><?= (int) $index + 1 ?></td>
                        <td><?= e((string) ($row['titlu'] ?? '')) ?></td>
                        <td class="center"><?= e($um) ?></td>
                        <td class="qty"><?= (int) ($row['cantitate'] ?? 0) ?></td>
                        <td class="money"><?= e(($money = lei($row['pret'] ?? null)) === 'la confirmare' ? '—' : $money) ?></td>
                        <td class="money"><?= e(($money = lei($row['suma'] ?? null)) === 'la confirmare' ? '—' : $money) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <p><strong>Valoarea totală:</strong>
                <?= e($totalBani === null ? 'la confirmare' : lei($totalBani) . ' lei') ?>
            </p>
            <?php if ($totalWords !== ''): ?>
                <p><strong>În litere:</strong> <?= e($totalWords) ?></p>
            <?php endif; ?>
            <?php if (($table['tva'] ?? null) !== null): ?>
                <p>Inclusiv TVA: <?= e(lei($table['tva'])) ?> lei</p>
            <?php endif; ?>
        </div>

        <p class="clause">
            Mărfurile au fost predate în stare corespunzătoare, în cantitatea și sortimentul indicate în prezentul act.
            Primitorul confirmă recepția și declară că nu are pretenții față de Predător privind cantitatea, calitatea
            și aspectul mărfurilor la momentul predării, în afara rezervelor menționate eventual mai jos.
        </p>
        <p class="clause">
            <strong>Rezerve / mențiuni:</strong>
            <?= e(trim((string) ($order['nota'] ?? '')) !== '' ? (string) $order['nota'] : 'Fără rezerve.') ?>
        </p>
        <p class="copies">
            Prezentul act este întocmit în două exemplare, cu aceeași putere juridică, câte unul pentru fiecare parte.
        </p>

        <div class="signs">
            <div class="box">
                <p><strong>Predat mărfuri</strong></p>
                <p><?= e($supplier['nume']) ?></p>
                <div class="sign-line">Nume, prenume / semnătura</div>
                <p class="stamp">L.Ș.</p>
            </div>
            <div class="box">
                <p><strong>Primit mărfuri</strong></p>
                <p><?= e($receiver['nume'] !== '' ? $receiver['nume'] : 'Cumpărător') ?></p>
                <div class="sign-line">Nume, prenume / semnătura</div>
                <p class="stamp">L.Ș.</p>
            </div>
        </div>

        <p class="legal-note">
            Document întocmit ca act de predare-primire a mărfurilor între entități, în conformitate cu uzanțele
            comerciale și cu cerințele generale privind documentele primare din Republica Moldova
            (denumirea părților, IDNO, obiectul predării, cantități, valori, data, locul și semnăturile).
            Nu înlocuiește factura fiscală / contul de plată.
        </p>
    </article>
</body>
</html>
