<?php
$s = settings();
$notices = notices();
$isOpen = isOpenNow();
include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-4 pb-16">
    <div class="mb-3">
        <?php if ($isOpen): ?>
        <span class="text-sm font-semibold"><span class="open-dot on"></span><?= t('Avoinna', 'Open now') ?></span>
        <?php else: ?>
        <span class="text-sm font-semibold text-muted"><span class="open-dot off"></span><?= t('Suljettu', 'Closed') ?></span>
        <?php endif; ?>
    </div>
    <h1 class="display text-accent mb-5" style="margin-left:-0.03em">Waves</h1>
    <div class="flex items-center gap-3 mb-5">
        <div class="hero-rule"></div>
        <p class="lead max-w-2xl">
            <?= esc($s['hero_text_' . lang()] ?? '') ?>
        </p>
    </div>
    <p class="text-sm text-muted max-w-xl mb-8">
        <?= t('Satamakatu 2 B, Jyväskylä &nbsp;·&nbsp; Kesäterassi &nbsp;·&nbsp; Lounas ma–pe &nbsp;·&nbsp; Tapahtumia läpi kesän', 'Satamakatu 2 B, Jyväskylä &nbsp;·&nbsp; Summer terrace &nbsp;·&nbsp; Lunch Mon–Fri &nbsp;·&nbsp; Events all summer') ?>
    </p>
    <div class="flex flex-wrap gap-3 mb-12">
        <a href="<?= url('menu') ?>" class="btn"><?= t('Menu', 'Menu') ?><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 6h10M6 1l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        <a href="<?= url('yhteystiedot') ?>" class="btn btn-outline"><?= t('Yhteystiedot', 'Contact') ?></a>
    </div>
    <div class="rule-accent mb-10"></div>
    <div class="grid-asymmetric">
        <div>
            <p class="label mb-4"><?= t('Tietoa', 'About') ?></p>
            <div class="prose text-muted max-w-prose">
                <?= $s['intro_' . lang()] ?? '' ?>
            </div>
        </div>
        <div class="hours-card">
            <p class="label"><?= t('Aukioloajat', 'Opening Hours') ?></p>
            <?php include INCLUDES_DIR . '/opening-hours.php'; ?>
        </div>
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
