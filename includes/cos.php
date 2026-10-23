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

function product_choices(array $item): array
{
    $variants = $item['variants'] ?? null;
    if (is_array($variants) && $variants !== []) {
        $choices = [];
        foreach ($variants as $variant) {
            if (!is_array($variant)) {
                continue;
            }
            $key = (string) ($variant['key'] ?? '');
            if (preg_match('/^[a-z0-9]{1,20}$/', $key) !== 1) {
                continue;
            }
            $choices[] = [
                'key' => $key,
                'label' => (string) ($variant['label'] ?? ''),
                'pret' => $variant['pret'] ?? null,
                'flavor' => (string) ($variant['flavor'] ?? ''),
                'pack' => (string) ($variant['pack'] ?? ''),
                'image' => (string) ($variant['image'] ?? ''),
                'offer_image' => (string) ($variant['offer_image'] ?? ''),
            ];
        }
        if ($choices !== []) {
            return $choices;
        }
    }

    $options = $item['options'] ?? [];
    $choices = [];
    if (is_array($options)) {
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }
            $key = (string) ($option['key'] ?? '');
            if (preg_match('/^[a-z0-9]{1,20}$/', $key) !== 1) {
                continue;
            }
            $choices[] = [
                'key' => $key,
                'label' => (string) ($option['label'] ?? ''),
                'pret' => $option['pret'] ?? null,
                'image' => (string) ($option['image'] ?? ''),
                'offer_image' => (string) ($option['offer_image'] ?? ''),
            ];
        }
    }
    if ($choices === []) {
        $choices[] = [
            'key' => '',
            'label' => '',
            'pret' => $item['pret'] ?? null,
            'image' => (string) ($item['image'] ?? ''),
            'offer_image' => (string) ($item['offer_image'] ?? ''),
        ];
    }

    return $choices;
}

function product_choice_image(array $item, array $choice): string
{
    $image = trim((string) ($choice['image'] ?? ''));
    if ($image !== '') {
        return $image;
    }

    return (string) ($item['image'] ?? '');
}

function product_choice_offer_image(array $item, array $choice): string
{
    $offer = trim((string) ($choice['offer_image'] ?? ''));
    if ($offer !== '') {
        return $offer;
    }
    $image = trim((string) ($choice['image'] ?? ''));
    if ($image !== '') {
        return $image;
    }
    $itemOffer = trim((string) ($item['offer_image'] ?? ''));
    if ($itemOffer !== '') {
        return $itemOffer;
    }

    return (string) ($item['image'] ?? '');
}

function product_price_label($pret): string
{
    $money = product_money($pret);

    return t('Preț') . ': ' . ($money === 'la confirmare' ? t('la confirmare') : str_replace(' lei', ' ' . t('lei'), $money));
}

function product_choice(array $item, string $key): ?array
{
    $choices = product_choices($item);
    if ($key === '') {
        return $choices[0];
    }
    foreach ($choices as $choice) {
        if ($choice['key'] === $key) {
            return $choice;
        }
    }

    return null;
}

function product_line_title(array $item, array $choice): string
{
    $title = (string) ($item['title'] ?? '');
    $label = (string) ($choice['label'] ?? '');
    if ($label === '') {
        return $title;
    }

    return $title . ', ' . $label;
}

function product_money($pret): string
{
    if (!is_numeric($pret) || (float) $pret <= 0) {
        return 'la confirmare';
    }
    $bani = (int) round(((float) $pret) * 100);
    $sign = $bani < 0 ? '-' : '';
    $bani = abs($bani);

    return $sign . number_format(intdiv($bani, 100), 0, ',', ' ') . ',' . str_pad((string) ($bani % 100), 2, '0', STR_PAD_LEFT) . ' lei';
}

function cart_storage_key(string $slug, string $option): string
{
    return $option === '' ? $slug : $slug . ':' . $option;
}

function cart_parse_key(string $key): array
{
    $parts = explode(':', $key, 2);

    return [$parts[0], $parts[1] ?? ''];
}

function cart_items(): array
{
    $cart = $_SESSION['cos'] ?? [];
    if (!is_array($cart)) {
        return [];
    }
    $clean = [];
    foreach ($cart as $key => $qty) {
        [$slug, $option] = cart_parse_key((string) $key);
        $qty = (int) $qty;
        $item = product_by_slug($slug);
        if ($item === null || $qty < 1) {
            continue;
        }
        $choice = product_choice($item, $option);
        if ($choice === null) {
            continue;
        }
        $stored = cart_storage_key($slug, (string) $choice['key']);
        $clean[$stored] = min(999, ($clean[$stored] ?? 0) + $qty);
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
    foreach (cart_items() as $key => $qty) {
        [$slug, $option] = cart_parse_key($key);
        $item = product_by_slug($slug);
        if ($item === null) {
            continue;
        }
        $choice = product_choice($item, $option);
        if ($choice === null) {
            continue;
        }
        $pret = $choice['pret'] ?? null;
        $lines[] = [
            'slug' => $key,
            'titlu' => product_line_title($item, $choice),
            'cantitate' => $qty,
            'pret' => (is_numeric($pret) && (float) $pret > 0) ? round((float) $pret, 2) : null,
            'image' => product_choice_image($item, $choice),
        ];
    }

    return $lines;
}

function cart_add(string $slug, int $qty, string $option = ''): bool
{
    $item = product_by_slug($slug);
    if ($item === null || $qty < 1) {
        return false;
    }
    $choice = product_choice($item, $option);
    if ($choice === null) {
        return false;
    }
    $key = cart_storage_key($slug, (string) $choice['key']);
    $cart = cart_items();
    $cart[$key] = min(999, ($cart[$key] ?? 0) + $qty);
    $_SESSION['cos'] = $cart;

    return true;
}

function cart_set(array $posted): void
{
    $cart = [];
    foreach ($posted as $key => $raw) {
        [$slug, $option] = cart_parse_key((string) $key);
        $item = product_by_slug($slug);
        if ($item === null) {
            continue;
        }
        $choice = product_choice($item, $option);
        if ($choice === null || (string) $choice['key'] !== $option) {
            continue;
        }
        $qty = (int) $raw;
        if ($qty > 0) {
            $stored = cart_storage_key($slug, $option);
            $cart[$stored] = min(999, $qty);
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
    if (preg_match('/^(index\.php|produse\.php|aparate\.php|cos\.php)(#[a-z0-9-]+)?$/', $target) === 1) {
        return $target;
    }

    return 'produse.php';
}

function buy_form(string $slug, string $back, string $buttonClass = 'btn btn-dark btn-sm'): void
{
    $item = product_by_slug($slug);
    if ($item === null) {
        return;
    }
    $choices = product_choices($item);
    $field = str_replace(['.', '#', ':'], '-', $slug . '-' . $back);
    $first = $choices[0];
    $flavors = $item['options'] ?? [];
    $packs = $item['packs'] ?? [];
    $hasPacks = is_array($packs) && $packs !== [] && is_array($flavors) && $flavors !== [];
    ?>
    <form class="buy-form<?= $hasPacks ? ' buy-form-split' : '' ?>" method="post" action="<?= e(url('cos.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e((string) ($_SESSION['csrf'] ?? '')) ?>">
        <input type="hidden" name="actiune" value="adauga">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">
        <input type="hidden" name="inapoi" value="<?= e($back) ?>">
        <?php if ($hasPacks): ?>
            <input type="hidden" name="optiune" value="<?= e((string) $first['key']) ?>" data-optiune>
            <?php foreach ($choices as $choice): ?>
                <span class="buy-price-entry" hidden data-key="<?= e((string) $choice['key']) ?>" data-price="<?= e(product_price_label($choice['pret'] ?? null)) ?>"></span>
            <?php endforeach; ?>
            <div class="buy-options" role="radiogroup" aria-label="<?= tr((string) ($item['choice'] ?? 'Sortiment')) ?>">
                <?php foreach ($flavors as $index => $flavor): ?>
                    <?php if (!is_array($flavor)) {
                        continue;
                    } ?>
                    <label class="buy-option">
                        <input type="radio" name="sortiment" value="<?= e((string) ($flavor['key'] ?? '')) ?>" data-part="flavor"<?= $index === 0 ? ' checked' : '' ?>>
                        <span><?= tr((string) ($flavor['label'] ?? '')) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="buy-options" role="radiogroup" aria-label="<?= tr((string) ($item['pack_choice'] ?? 'Ambalare')) ?>">
                <?php foreach ($packs as $index => $pack): ?>
                    <?php if (!is_array($pack)) {
                        continue;
                    } ?>
                    <label class="buy-option">
                        <input type="radio" name="ambalare" value="<?= e((string) ($pack['key'] ?? '')) ?>" data-part="pack"<?= $index === 0 ? ' checked' : '' ?>>
                        <span><?= tr((string) ($pack['label'] ?? '')) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php elseif (count($choices) > 1): ?>
            <div class="buy-options" role="radiogroup" aria-label="<?= tr((string) ($item['choice'] ?? 'Opțiune')) ?>">
                <?php foreach ($choices as $index => $choice): ?>
                    <?php
                    $choiceImage = product_choice_image($item, $choice);
                    ?>
                    <label class="buy-option">
                        <input type="radio" name="optiune" value="<?= e($choice['key']) ?>" data-price="<?= e(product_price_label($choice['pret'] ?? null)) ?>"<?= $choiceImage !== '' ? ' data-image="' . e(asset($choiceImage)) . '"' : '' ?><?= $index === 0 ? ' checked' : '' ?>>
                        <span><?= tr($choice['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php elseif ($first['key'] !== ''): ?>
            <input type="hidden" name="optiune" value="<?= e($first['key']) ?>">
        <?php endif; ?>
        <p class="buy-price"><?= e(product_price_label($first['pret'] ?? null)) ?></p>
        <label class="sr-only" for="cant-<?= e($field) ?>"><?= tr('Cantitate') ?></label>
        <input id="cant-<?= e($field) ?>" name="cantitate" type="number" min="1" max="999" step="1" value="1" inputmode="numeric">
        <button class="<?= e($buttonClass) ?>" type="submit"><?= tr('Adaugă în coș') ?></button>
    </form>
    <?php
}
