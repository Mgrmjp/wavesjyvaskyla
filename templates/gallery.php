<?php
$s = settings();
$gallery = DataStore::load('gallery');
$images = array_filter($gallery, fn($img) => $img['visible'] ?? true);
include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-8 pb-20">
    <h1 class="display text-accent mb-4"><?= esc($page['title']) ?></h1>
    <p class="lead max-w-xl mb-12"><?= t('Tunnelmia konttiravintola Wavesista.', 'Moments from container restaurant Waves.') ?></p>
    <div class="rule-accent mb-16"></div>

    <?php if (empty($images)): ?>
    <p class="text-muted"><?= t('Ei kuvia vielä. Tule takaisin pian!', 'No images yet. Check back soon!') ?></p>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($images as $img): ?>
        <a href="<?= asset('../../uploads/' . ($img['filename'] ?? '')) ?>" class="gallery-item" target="_blank" rel="noopener">
            <div class="gallery-thumb">
                <img src="<?= asset('../../uploads/' . ($img['filename'] ?? '')) ?>" alt="<?= esc($img['caption_' . lang()] ?? '') ?>" loading="lazy">
            </div>
            <?php if (!empty($img['caption_' . lang()])): ?>
            <p class="gallery-caption"><?= esc($img['caption_' . lang()]) ?></p>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>
