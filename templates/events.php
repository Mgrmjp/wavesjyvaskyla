<?php
$s = settings();
$data = DataStore::load('events');
$allEvents = $data['events'] ?? [];

$upcoming = [];
$past = [];
foreach ($allEvents as $e) {
    if (!($e['visible'] ?? false)) continue;
    if (($e['date'] ?? '') >= date('Y-m-d')) {
        $upcoming[] = $e;
    } else {
        $past[] = $e;
    }
}
usort($upcoming, fn($a, $b) => ($a['date'] ?? '') <=> ($b['date'] ?? ''));
usort($past, fn($a, $b) => ($b['date'] ?? '') <=> ($a['date'] ?? ''));

include INCLUDES_DIR . '/header.php';
?>

<section class="max-w-5xl mx-auto px-5 pt-8 pb-20">
    <h1 class="display text-accent mb-4"><?= esc($page['title']) ?></h1>
    <p class="lead max-w-xl mb-12"><?= t('Musiikkia, ruokaa ja merellistä tunnelmaa.', 'Music, food, and maritime atmosphere.') ?></p>
    <div class="rule-accent mb-16"></div>

    <?php if (count($upcoming)): ?>
    <div class="mb-16">
        <h2 class="label mb-6"><?= t('Tulevat', 'Upcoming') ?></h2>
        <div class="space-y-0">
            <?php foreach ($upcoming as $ev): ?>
            <div class="border-t border-editorial py-6">
                <div class="flex flex-col md:flex-row md:items-baseline md:justify-between gap-2 mb-2">
                    <h3 class="text-xl font-semibold"><?= esc($ev['title_' . lang()] ?? $ev['title_fi'] ?? '') ?></h3>
                    <span class="text-accent font-mono text-sm whitespace-nowrap"><?= date('d.m.Y', strtotime($ev['date'])) ?> <?php if (!empty($ev['start_time'])): ?><?= esc($ev['start_time']) ?><?php endif; ?></span>
                </div>
                <p class="text-muted text-sm max-w-2xl"><?= esc($ev['description_' . lang()] ?? $ev['description_fi'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($past)): ?>
    <div class="mb-16">
        <h2 class="label mb-6"><?= t('Menneet', 'Past') ?></h2>
        <div class="space-y-0 opacity-50">
            <?php foreach ($past as $ev): ?>
            <div class="border-t border-editorial py-4">
                <div class="flex flex-col md:flex-row md:items-baseline md:justify-between gap-2">
                    <h3 class="text-base font-semibold"><?= esc($ev['title_' . lang()] ?? $ev['title_fi'] ?? '') ?></h3>
                    <span class="text-muted font-mono text-sm whitespace-nowrap"><?= date('d.m.Y', strtotime($ev['date'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!count($upcoming) && !count($past)): ?>
    <p class="text-muted py-12"><?= t('Ei tapahtumia.', 'No events.') ?></p>
    <?php endif; ?>
</section>

<?php include INCLUDES_DIR . '/footer.php'; ?>
