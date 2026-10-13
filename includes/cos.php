<?php

declare(strict_types=1);

function catalog(): array
{
    static $items = null;
    if (is_array($items)) {
        return $items;
    }
    if (!function_exists('product_catalog')) {
        require_once __DIR__ . '/products.php';
    }
    $items = product_catalog();

    return $items;
}

function product_by_slug(string $slug): ?array
{
    foreach (catalog() as $item) {
        if (($item['slug'] ?? '') === $slug) {
            return $item;
        }
    }

    return null;
}

function cart_items(): array
{
    $cart = $_SESSION['cos'] ?? [];
    if (!is_array($cart)) {
        return [];
    }
    $clean = [];
    foreach ($cart as $slug => $qty) {
        $slug = (string) $slug;
        $qty = (int) $qty;
        if (product_by_slug($slug) === null || $qty < 1) {
            continue;
        }
        $clean[$slug] = min(999, $qty);
    }
    $_SESSION['cos'] = $clean;

    return $clean;
}

function cart_count(): int
{
    return array_sum(cart_items());
}

function cart_lines(): array
{
    $lines = [];
    foreach (cart_items() as $slug => $qty) {
        $item = product_by_slug($slug);
        if ($item === null) {
            continue;
        }
        $pret = (isset($item['pret']) && is_numeric($item['pret']) && (float) $item['pret'] > 0)
            ? round((float) $item['pret'], 2)
            : null;
        $lines[] = [
            'slug' => $slug,
            'titlu' => (string) $item['title'],
            'cantitate' => $qty,
            'pret' => $pret,
            'image' => (string) ($item['image'] ?? ''),
        ];
    }

    return $lines;
}

function cart_add(string $slug, int $qty): bool
{
    if (product_by_slug($slug) === null || $qty < 1) {
        return false;
    }
    $cart = cart_items();
    $cart[$slug] = min(999, ($cart[$slug] ?? 0) + $qty);
    $_SESSION['cos'] = $cart;

    return true;
}

function cart_set(array $posted): void
{
    $cart = [];
    foreach ($posted as $slug => $raw) {
        $slug = (string) $slug;
        if (product_by_slug($slug) === null) {
            continue;
        }
        $qty = (int) $raw;
        if ($qty > 0) {
            $cart[$slug] = min(999, $qty);
        }
    }
    $_SESSION['cos'] = $cart;
}

function cart_clear(): void
{
    $_SESSION['cos'] = [];
}

function cart_return(string $target): string
{
    if (preg_match('/^(index\.php|produse\.php|cos\.php)(#[a-z0-9-]+)?$/', $target) === 1) {
        return $target;
    }

    return 'produse.php';
}

function buy_form(string $slug, string $back, string $buttonClass = 'btn btn-dark btn-sm'): void
{
    ?>
    <form class="buy-form" method="post" action="<?= e(url('cos.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <input type="hidden" name="actiune" value="adauga">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">
        <input type="hidden" name="inapoi" value="<?= e($back) ?>">
        <label class="sr-only" for="cant-<?= e($slug) ?>-<?= e(str_replace(['.', '#'], '-', $back)) ?>">Cantitate</label>
        <input id="cant-<?= e($slug) ?>-<?= e(str_replace(['.', '#'], '-', $back)) ?>" name="cantitate" type="number" min="1" max="999" step="1" value="1" inputmode="numeric">
        <button class="<?= e($buttonClass) ?>" type="submit">Adaugă în coș</button>
    </form>
    <?php
}
