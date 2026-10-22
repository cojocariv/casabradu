<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/products.php';

if (!admin_logged_in()) {
    header('Location: ' . url('admin.php'));
    exit;
}

$errors = [];
$flash = flash_get();
$pricePosted = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfOk = hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''));
    if (!$csrfOk) {
        $errors['form'] = 'Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.';
    } else {
        $postedPrices = $_POST['pret'] ?? [];
        if (!is_array($postedPrices)) {
            $postedPrices = [];
        }
        $priceError = product_save_prices($postedPrices);
        if ($priceError === '') {
            flash_set('Prețurile au fost salvate.');
            header('Location: ' . url('admin-preturi.php'));
            exit;
        }
        $errors['form'] = $priceError;
        $pricePosted = $postedPrices;
    }
}

$priced = [];
foreach (product_catalog() as $item) {
    $priced[(string) $item['slug']] = $item;
}

$page = 'admin';
$noindex = true;
$pageTitle = 'Prețuri';
$pageDescription = 'Gestionează prețurile produselor Casa Bradu Caffè.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow">Administrator</p>
        <h1>Prețuri.</h1>
        <p class="lede">Prețul este în lei. Lasă câmpul gol dacă îl confirmi la comandă.</p>
        <p>
            <a class="invoice-link" href="<?= e(url('admin.php')) ?>">Înapoi la comenzi</a>
            · <a class="invoice-link" href="<?= e(url('admin-oferta.php')) ?>">Ofertă comercială</a>
        </p>
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

        <div class="price-board">
            <p class="kicker">Produse</p>
            <h2>Lista de prețuri</h2>
            <form method="post" action="<?= e(url('admin-preturi.php')) ?>">
                <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
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
                            <?php foreach (product_price_rows() as $row): ?>
                                <?php
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
                <div class="btn-row" style="margin-top:16px">
                    <button class="btn btn-dark" type="submit">Salvează prețurile</button>
                    <a class="btn btn-line" href="<?= e(url('admin.php')) ?>">Înapoi</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
