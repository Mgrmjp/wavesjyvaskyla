<?php
$s = settings();
$data = DataStore::ensure('menu', ['categories' => defaultMenuCategories(), 'items' => defaultMenuItems()]);
$categories = $data['categories'] ?? [];
$items = $data['items'] ?? [];

$grouped = [];
foreach ($items as $item) {
    if (!($item['visible'] ?? false)) continue;
    $cat = $item['category'] ?? 'other';
    if (!isset($grouped[$cat])) $grouped[$cat] = [];
    $grouped[$cat][] = $item;
}

usort($categories, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-8 pb-20">
    <h1 class="display text-accent mb-4"><?= esc($page['title']) ?></h1>
    <p class="lead max-w-xl mb-12"><?= t('Kesäisiä makuja satamassa. Raaka-aineet tuoreina, tunnelma rentona.', 'Summer flavors at the harbor. Fresh ingredients, laid-back vibe.') ?></p>
    <div class="rule-accent mb-16"></div>

    <?php foreach ($categories as $cat):
        $slug = $cat['slug'] ?? '';
        if (empty($grouped[$slug])) continue;
    ?>
    <div class="mb-16">
        <h2 class="text-3xl font-bold tracking-tight mb-2" style="letter-spacing:-0.03em"><?= esc($cat['title_' . lang()] ?? $cat['title_fi'] ?? '') ?></h2>
        <hr class="rule mb-2">
        <div>
            <?php foreach ($grouped[$slug] as $item): ?>
            <div class="menu-row">
                <div class="flex-1 min-w-0">
                    <div class="menu-row-name">
                        <?= esc($item['name_' . lang()] ?? $item['name_fi'] ?? '') ?>
                        <?php if (!empty($item['dietary_tags'])): ?>
                            <?php foreach (explode(',', $item['dietary_tags']) as $tag): ?>
                            <span class="dietary-tag"><?= esc(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item['description_' . lang()] ?? $item['description_fi'] ?? '')): ?>
                    <div class="menu-row-desc"><?= esc($item['description_' . lang()] ?? $item['description_fi'] ?? '') ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['image'])): ?>
                <div class="menu-item-img"><img src="/uploads/<?= esc($item['image']) ?>" alt="" loading="lazy"></div>
                <?php endif; ?>
                <?php if (!empty($item['price'])): ?>
                <div class="menu-row-price"><?= number_format((float)$item['price'], 2, ',', '') ?> €</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="border border-editorial p-6 mt-16">
        <p class="label mb-2"><?= t('Merkinnyt', 'Labels') ?></p>
        <p class="text-sm text-muted">
            <strong class="text-text">G</strong> <?= t('Gluteeniton', 'Gluten-free') ?>&nbsp;&nbsp;
            <strong class="text-text">L</strong> <?= t('Laktoositon', 'Lactose-free') ?>&nbsp;&nbsp;
            <strong class="text-text">VL</strong> <?= t('Vähälaktoottinen', 'Low-lactose') ?>&nbsp;&nbsp;
            <strong class="text-text">V</strong> <?= t('Vegaaninen', 'Vegan') ?>&nbsp;&nbsp;
            <strong class="text-text">M</strong> <?= t('Maidoton', 'Dairy-free') ?>
        </p>
    </div>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>
