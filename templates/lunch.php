<?php
$s = settings();
$data = DataStore::ensure('lunch', ['items' => []]);
$items = $data['items'] ?? [];

$days = ['mon','tue','wed','thu','fri'];
$grouped = [];
foreach ($items as $item) {
    if (!($item['visible'] ?? false)) continue;
    $day = strtolower($item['weekday'] ?? '');
    if (!in_array($day, $days)) continue;
    if (!isset($grouped[$day])) $grouped[$day] = [];
    $grouped[$day][] = $item;
}

include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-8 pb-20">
    <h1 class="display text-accent mb-4"><?= esc($page['title']) ?></h1>
    <p class="lead max-w-xl mb-12"><?= t('Maanantaista perjantaihin', 'Monday through Friday') ?></p>
    <div class="rule-accent mb-16"></div>

    <?php foreach ($days as $day): ?>
    <div class="mb-12">
        <h2 class="text-2xl font-bold mb-1" style="letter-spacing:0"><?= dayLabel($day) ?></h2>
        <hr class="rule mb-4">
        <?php if (!empty($grouped[$day])): ?>
            <?php foreach ($grouped[$day] as $item): ?>
            <div class="menu-row">
                <div class="flex-1 min-w-0">
                    <div class="menu-row-name">
                        <?= esc($item['name_' . lang()] ?? $item['name_fi'] ?? '') ?>
                        <?php if (!empty($item['dietary_tags'])): ?>
                            <?php foreach (explode(',', $item['dietary_tags']) as $tag): ?>
                            <span class="dietary-tag" data-label="<?= esc(dietaryLabel(trim($tag))) ?>"><?= esc(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item['description_' . lang()] ?? $item['description_fi'] ?? '')): ?>
                    <div class="menu-row-desc"><?= esc($item['description_' . lang()] ?? $item['description_fi'] ?? '') ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['price'])): ?>
                <div class="menu-row-price"><?= number_format((float)$item['price'], 2, ',', '') ?> €</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <p class="text-muted italic py-4"><?= t('Ei lounasta tälle päivälle.', 'No lunch for this day.') ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>
