<?php
$s = settings();
$notices = notices();
$isOpen = isOpenNow();
include INCLUDES_DIR . '/header.php';
?>

<section class="home-hero-wrap" style="--home-hero-image: url('<?= esc(publicAsset('/assets/files/frontpage-hero-upscaled.avif')) ?>');">
    <div class="home-hero-bg"></div>
    <div class="max-w-5xl mx-auto px-5 home-hero-content">
        <div class="home-hero-inner">
            <div class="home-hero-main">
                <div class="home-hero-heading">
                    <h1 class="display text-accent home-display" style="margin-left:-0.03em">Waves</h1>
                    <?php if ($isOpen): ?>
                    <span class="status-chip is-open"><span class="open-dot on"></span><?= t('Avoinna nyt', 'Open now') ?></span>
                    <?php else: ?>
                    <span class="status-chip is-closed"><span class="open-dot off"></span><?= t('Suljettu', 'Closed') ?></span>
                    <?php endif; ?>
                </div>
                <div class="home-hero-subtitle">
                    <div class="hero-rule"></div>
                    <p class="lead">
                        <?= esc($s['hero_text_' . lang()] ?? '') ?>
                    </p>
                </div>
                <p class="text-sm text-muted home-meta">
                    <span><?= t('Satamakatu 2 B, Jyväskylä', 'Satamakatu 2 B, Jyväskylä') ?></span>
                    <span><?= t('Kesäterassi', 'Summer terrace') ?></span>
                    <span><?= t('Lounas ma–pe', 'Lunch Mon–Fri') ?></span>
                    <span><?= t('Tapahtumia läpi kesän', 'Events all summer') ?></span>
                </p>
                <div class="home-cta-row">
                    <a href="<?= url('menu') ?>" class="btn"><?= t('Menu', 'Menu') ?><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 6h10M6 1l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                    <a href="<?= url('yhteystiedot') ?>" class="btn btn-outline"><?= t('Yhteystiedot', 'Contact') ?></a>
                </div>
                <div class="rule-accent home-divider"></div>
                <div class="home-about-block">
                    <p class="label"><?= t('Tietoa', 'About') ?></p>
                    <div class="prose text-muted home-about-text">
                        <?= $s['intro_' . lang()] ?? '' ?>
                    </div>
                </div>
            </div>
            <aside class="hours-card home-opening-card">
                <p class="label"><?= t('Aukioloajat', 'Opening Hours') ?></p>
                <?php include INCLUDES_DIR . '/opening-hours.php'; ?>
            </aside>
        </div>
    </div>
<div class="wave-divider" aria-hidden="true">
    <svg class="wave wave--back" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,76
                 C180,54 360,96 540,76
                 C720,56 900,96 1080,77
                 C1260,56 1350,66 1440,76
                 L1440,120 L0,120 Z"></path>
    </svg>

    <svg class="wave wave--mid" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,58
                 C180,38 360,78 540,58
                 C720,38 900,78 1080,58
                 C1200,44 1320,50 1440,62
                 L1440,120 L0,120 Z"></path>
    </svg>

    <svg class="wave wave--front" viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,68
                 C240,92 360,44 540,68
                 C720,92 840,44 1020,68
                 C1200,92 1320,46 1440,68
                 L1440,120 L0,120 Z"></path>
    </svg>
</div>
</section>

<?php
$events = DataStore::load('events');
$upcoming = [];
foreach ($events['events'] ?? [] as $e) {
    if (!($e['visible'] ?? false)) continue;
    if (($e['date'] ?? '') >= date('Y-m-d')) $upcoming[] = $e;
    if (count($upcoming) >= 3) break;
}
if (count($upcoming)):
?>
<section class="border-t border-editorial">
    <div class="max-w-5xl mx-auto px-5 py-16">
        <div class="flex items-baseline justify-between mb-8">
            <h2 class="headline"><?= t('Tapahtumat', 'Events') ?></h2>
            <a href="<?= url('tapahtumat') ?>" class="text-sm text-accent hover:text-text transition-colors"><?= t('Kaikki →', 'All →') ?></a>
        </div>
        <div class="space-y-0">
            <?php foreach ($upcoming as $ev): ?>
            <div class="border-t border-editorial py-5 flex flex-col md:flex-row md:items-baseline md:justify-between gap-1">
                <div>
                    <h3 class="text-lg font-semibold"><?= esc($ev['title_' . lang()] ?? $ev['title_fi'] ?? '') ?></h3>
                    <p class="text-sm text-muted"><?= esc($ev['description_' . lang()] ?? $ev['description_fi'] ?? '') ?></p>
                </div>
                <span class="text-sm text-accent font-mono whitespace-nowrap"><?= date('d.m.', strtotime($ev['date'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include INCLUDES_DIR . '/footer.php'; ?>
