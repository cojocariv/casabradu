<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/products.php';
require_once __DIR__ . '/includes/cos.php';
require_once __DIR__ . '/includes/cont.php';
require_once __DIR__ . '/includes/oferta.php';

if (!admin_logged_in()) {
    header('Location: ' . url('admin.php'));
    exit;
}

$errors = [];
$flash = flash_get();

function oferta_form_state(array $post = []): array
{
    $state = [
        'client' => '',
        'contact' => '',
        'valabilitate' => date('d.m.Y', strtotime('+14 days')),
        'intro' => 'Vă propunem cafea artizanală Casa Bradu Caffè, import Italia, aromată și fină.',
        'lines' => [],
    ];
    if ($post !== []) {
        $state['client'] = text_line((string) ($post['client'] ?? ''), 120);
        $state['contact'] = text_line((string) ($post['contact'] ?? ''), 120);
        $state['valabilitate'] = text_line((string) ($post['valabilitate'] ?? ''), 40);
        $state['intro'] = text_line((string) ($post['intro'] ?? ''), 240);
        if (isset($post['titlu']) && is_array($post['titlu'])) {
            foreach ($post['titlu'] as $index => $title) {
                $state['lines'][] = [
                    'slug' => (string) ($post['slug'][$index] ?? ''),
                    'titlu' => (string) $title,
                    'varianta' => (string) ($post['varianta'][$index] ?? ''),
                    'grup' => (string) ($post['grup'][$index] ?? ''),
                    'image' => (string) ($post['image'][$index] ?? ''),
                    'cantitate' => (string) ($post['cantitate'][$index] ?? ''),
                    'pret' => (string) ($post['pret'][$index] ?? ''),
                ];
            }
        }
    }
    if ($state['lines'] === []) {
        $state['lines'] = oferta_default_lines();
    }

    return $state;
}

function oferta_grouped_lines(array $lines): array
{
    $groups = [];
    foreach ($lines as $index => $line) {
        if (!is_array($line)) {
            continue;
        }
        $grup = (string) ($line['grup'] ?? '');
        if ($grup === '') {
            $slug = (string) ($line['slug'] ?? '');
            $base = explode(':', $slug, 2)[0];
            $item = $base !== '' ? product_by_slug($base) : null;
            $grup = is_array($item) ? (string) ($item['title'] ?? $base) : 'Produse';
        }
        if (!isset($groups[$grup])) {
            $groups[$grup] = ['title' => $grup, 'lines' => []];
        }
        $line['_index'] = $index;
        $groups[$grup]['lines'][] = $line;
    }

    return $groups;
}

$form = oferta_form_state(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : []);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfOk = hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));
    if (!$csrfOk) {
        $errors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $parsed = oferta_parse_lines($_POST);
        if (isset($parsed['error'])) {
            $errors['linii'] = $parsed['error'];
        } elseif ($parsed['lines'] === []) {
            $errors['linii'] = 'Adaugă cantitate la cel puțin un produs.';
        } else {
            $table = oferta_table($parsed['lines']);
            $numar = 'OF-' . date('Ymd-Hi');
            $pdf = oferta_pdf([
                'numar' => $numar,
                'data' => date('d.m.Y'),
                'client' => $form['client'],
                'contact' => $form['contact'],
                'valabilitate' => $form['valabilitate'],
                'intro' => $form['intro'],
            ], $table);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $numar . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }
    }
}

$page = 'admin';
$noindex = true;
$pageTitle = 'Ofertă comercială';
$pageDescription = 'Generează oferte comerciale PDF din cabinetul administratorului.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Administrator</p>
        <h1>Ofertă comercială.</h1>
        <p class="lede">Selectează produsele, cantitățile și prețurile. Generezi un PDF atractiv, cu fotografie, pentru client.</p>
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

        <form class="contact-form invoice-builder offer-builder" method="post" action="<?= e(url('admin-oferta.php')) ?>" accept-charset="UTF-8">
            <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">

            <div class="price-board">
                <p class="kicker">Client</p>
                <h2>Datele ofertei</h2>
                <p class="form-note">Toate câmpurile sunt opționale, în afară de produsele cu cantitate.</p>
                <div class="invoice-meta-grid">
                    <div class="invoice-meta-field">
                        <label for="client">Companie / client</label>
                        <input id="client" name="client" type="text" maxlength="120" value="<?= e($form['client']) ?>" placeholder="opțional">
                    </div>
                    <div class="invoice-meta-field">
                        <label for="contact">Persoană de contact</label>
                        <input id="contact" name="contact" type="text" maxlength="120" value="<?= e($form['contact']) ?>" placeholder="opțional">
                    </div>
                    <div class="invoice-meta-field">
                        <label for="valabilitate">Valabilă până la</label>
                        <input id="valabilitate" name="valabilitate" type="text" maxlength="40" value="<?= e($form['valabilitate']) ?>" placeholder="zz.ll.aaaa">
                    </div>
                    <div class="invoice-meta-field invoice-meta-full">
                        <label for="intro">Text introductiv</label>
                        <textarea id="intro" name="intro" rows="2" maxlength="240"><?= e($form['intro']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="price-board" id="produse-oferta">
                <p class="kicker">Produse</p>
                <h2>Asortimentul din ofertă</h2>
                <p class="form-note">Completează cantitatea doar la produsele pe care le incluzi. Prețul se poate modifica pe loc.</p>
                <?php if (isset($errors['linii'])): ?>
                    <p class="notice notice-error" role="alert"><?= e($errors['linii']) ?></p>
                <?php endif; ?>

                <div class="invoice-groups" id="offer-lines">
                    <?php foreach (oferta_grouped_lines($form['lines']) as $group): ?>
                        <section class="invoice-group">
                            <h3 class="invoice-group-title"><?= e((string) $group['title']) ?></h3>
                            <div class="invoice-lines-grid offer-lines-grid">
                                <?php foreach ($group['lines'] as $line): ?>
                                    <?php
                                    $index = (int) ($line['_index'] ?? 0);
                                    $qtyShow = (int) ($line['cantitate'] ?? 0);
                                    $priceParsed = product_parse_price((string) ($line['pret'] ?? ''));
                                    $sumShow = ($qtyShow > 0 && is_string($priceParsed) && $priceParsed !== 'invalid')
                                        ? number_format($qtyShow * (float) $priceParsed, 2, ',', '')
                                        : '';
                                    $variant = trim((string) ($line['varianta'] ?? ''));
                                    $image = (string) ($line['image'] ?? '');
                                    ?>
                                    <div class="invoice-line offer-line">
                                        <div class="offer-line-visual">
                                            <?php if ($image !== ''): ?>
                                                <img src="<?= e(str_replace(' ', '%20', asset($image))) ?>" alt="" width="56" height="56" loading="lazy">
                                            <?php else: ?>
                                                <span class="offer-line-placeholder" aria-hidden="true"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="invoice-line-head">
                                            <input type="hidden" name="slug[]" value="<?= e((string) $line['slug']) ?>">
                                            <input type="hidden" name="image[]" value="<?= e($image) ?>">
                                            <input type="hidden" name="grup[]" value="<?= e((string) ($line['grup'] ?? '')) ?>">
                                            <input type="hidden" name="varianta[]" value="<?= e($variant) ?>">
                                            <input type="hidden" name="titlu[]" value="<?= e((string) $line['titlu']) ?>">
                                            <span class="invoice-variant"><?= e($variant !== '' ? $variant : (string) $line['titlu']) ?></span>
                                        </div>
                                        <div class="invoice-line-metrics">
                                            <div>
                                                <label for="of-cant-<?= $index ?>">Cant.</label>
                                                <input id="of-cant-<?= $index ?>" class="invoice-qty" name="cantitate[]" type="number" min="0" max="99999" step="1" value="<?= e((string) $line['cantitate']) ?>" inputmode="numeric">
                                            </div>
                                            <div>
                                                <label for="of-pret-<?= $index ?>">Preț, lei</label>
                                                <input id="of-pret-<?= $index ?>" class="invoice-price" name="pret[]" type="text" inputmode="decimal" maxlength="12" value="<?= e((string) $line['pret']) ?>" placeholder="12,50">
                                            </div>
                                            <div>
                                                <span class="invoice-sum-label">Suma</span>
                                                <span class="invoice-sum" aria-live="polite"><?= e($sumShow) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="btn-row invoice-actions">
                <button class="btn btn-dark" type="submit">Generează PDF ofertă</button>
            </div>
        </form>
    </div>
</section>

<script>
(function () {
    var root = document.getElementById('offer-lines');
    if (!root) {
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
        var sumEl = row.querySelector('.invoice-sum');
        if (!qtyInput || !priceInput || !sumEl) {
            return;
        }
        var qty = parseInt(qtyInput.value, 10) || 0;
        var price = parsePrice(priceInput.value);
        sumEl.textContent = (qty > 0 && price !== null) ? formatSum(qty * price) : '';
    }

    root.addEventListener('input', function (event) {
        var target = event.target;
        if (!target || !target.classList) {
            return;
        }
        if (target.classList.contains('invoice-qty') || target.classList.contains('invoice-price')) {
            updateRow(target.closest('.invoice-line'));
        }
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
