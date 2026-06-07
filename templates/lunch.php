<?php
$s = settings();
$data = DataStore::ensure('lunch', ['items' => []]);
$items = $data['items'] ?? [];

$days = ['mon','tue','wed','thu','fri'];
$grouped = [];
$hasAiLunchImages = false;
foreach ($items as $item) {
    if (!($item['visible'] ?? false)) continue;
    $day = strtolower($item['weekday'] ?? '');
    if (!in_array($day, $days)) continue;
    if (!isset($grouped[$day])) $grouped[$day] = [];
    $grouped[$day][] = $item;
    if (isAiGeneratedLunchImage((string) ($item['image'] ?? ''))) {
        $hasAiLunchImages = true;
    }
}

$aiImageDisclaimer = t(
    'Tekoälyllä luotu havainnekuva. Todellinen annos ja esillepano poikkeavat kuvasta.',
    'AI-generated illustrative image. Actual portions and presentation may vary.'
);

include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-8 pb-20">
    <h1 class="display text-accent mb-4"><?= esc($page['title']) ?></h1>
    <p class="lead max-w-xl mb-12"><?= t('Maanantaista perjantaihin', 'Monday through Friday') ?></p>
    <div class="lunch-summary mb-12" aria-label="<?= t('Lounaan tiedot', 'Lunch details') ?>">
        <div class="lunch-summary__item">
            <span class="lunch-summary__label"><?= t('Aika', 'Time') ?></span>
            <strong><?= t('Ma-pe klo 10:30-14', 'Mon-Fri 10:30 AM-2:00 PM') ?></strong>
        </div>
        <div class="lunch-summary__item">
            <span class="lunch-summary__label"><?= t('Sisältää', 'Includes') ?></span>
            <strong><?= t('Kahvin tai teen', 'Coffee or tea') ?></strong>
        </div>
        <div class="lunch-summary__price">
            <span><?= t('Lounas', 'Lunch') ?></span>
            <strong>14 €</strong>
        </div>
        <div class="lunch-summary__drinks">
            <span><?= t('Lounasolut: 5 € Sandels / 0,33 l', 'Lunch beer: €5 Sandels (0.33 L)') ?></span>
            <span><?= t('Lounasviini: 5 € Riesling / 12 cl', 'Lunch wine: €5 Riesling (12 cl)') ?></span>
        </div>
    </div>
    <div class="rule-accent mb-16"></div>

    <?php foreach ($days as $day): ?>
    <div class="mb-12">
        <h2 class="text-2xl font-bold mb-1" style="letter-spacing:0"><?= dayLabel($day) ?></h2>
        <hr class="rule mb-4">
        <?php if (!empty($grouped[$day])): ?>
            <?php foreach ($grouped[$day] as $item): ?>
            <?php
                $itemImageFilename = (string) ($item['image'] ?? '');
                $itemImage = uploadAsset($itemImageFilename);
                $isAiImage = isAiGeneratedLunchImage($itemImageFilename);
            ?>
            <div class="menu-row lunch-row<?= $itemImage !== '' ? ' lunch-row--with-image' : '' ?>">
                <?php if ($itemImage !== ''): ?>
                <button
                    type="button"
                    class="menu-item-img lunch-row__image<?= $isAiImage ? ' menu-item-img--ai' : '' ?>"
                    data-menu-image-trigger
                    data-menu-image-src="<?= esc($itemImage) ?>"
                    data-menu-image-title="<?= esc($item['name_' . lang()] ?? $item['name_fi'] ?? '') ?>"
                    data-menu-image-ai="<?= $isAiImage ? '1' : '0' ?>"
                    <?php if ($isAiImage): ?>data-ai-disclaimer="<?= esc($aiImageDisclaimer) ?>"<?php endif; ?>
                    aria-label="<?= esc(t('Näytä suurempi kuva annoksesta ', 'View larger image of ') . ($item['name_' . lang()] ?? $item['name_fi'] ?? '')) ?>"
                >
                    <img src="<?= esc($itemImage) ?>" alt="" loading="lazy">
                    <?php if ($isAiImage): ?>
                    <span class="menu-item-img__ai-label" aria-hidden="true">AI</span>
                    <?php endif; ?>
                </button>
                <?php endif; ?>
                <div class="lunch-row__body flex-1 min-w-0">
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

    <div class="lunch-abbreviations">
        <span><?= t('Merkinnät', 'Labels') ?></span>
        <strong>L</strong> <?= t('Laktoositon', 'Lactose-free') ?>
        <strong>G</strong> <?= t('Gluteeniton', 'Gluten-free') ?>
        <strong>VL</strong> <?= t('Vähälaktoottinen', 'Low-lactose') ?>
    </div>
    <?php if ($hasAiLunchImages): ?>
    <p class="menu-ai-disclaimer mt-4"><?= t('AI-merkityt kuvat ovat tekoälyllä luotuja havainnekuvia. Todellinen annos ja esillepano poikkeavat kuvasta.', 'Images marked AI are AI-generated illustrations. Actual portions and presentation may vary.') ?></p>
    <?php endif; ?>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>
