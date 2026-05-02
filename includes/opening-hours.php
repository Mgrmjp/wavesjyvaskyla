<?php $s = settings(); $today = date('N'); $dayMap = ['1'=>'mon','2'=>'tue','3'=>'wed','4'=>'thu','5'=>'fri','6'=>'sat','7'=>'sun']; $todayKey = $dayMap[$today] ?? ''; ?>
<div class="space-y-1 text-sm">
    <?php foreach ($s['opening_hours'] ?? [] as $h): ?>
    <div class="flex justify-between py-1 <?= ($h['day'] ?? '') === $todayKey ? 'text-accent font-semibold' : 'text-muted' ?>">
        <span><?= dayLabel($h['day'] ?? '') ?></span>
        <?php if ($h['closed'] ?? false): ?>
        <span class="text-warm"><?= t('Suljettu', 'Closed') ?></span>
        <?php else: ?>
        <span><?= esc($h['open'] ?? '') ?>–<?= esc($h['close'] ?? '') ?></span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php
$exceptions = [];
foreach ($s['opening_exceptions'] ?? [] as $exc) {
    if (($exc['date'] ?? '') >= date('Y-m-d')) $exceptions[] = $exc;
}
if (count($exceptions)):
?>
<div class="mt-6 border-l-2 border-warm pl-4">
    <p class="label mb-2"><?= t('Poikkeukset', 'Exceptions') ?></p>
    <?php foreach ($exceptions as $exc): ?>
    <p class="text-sm text-muted">
        <strong class="text-text"><?= date('d.m.', strtotime($exc['date'])) ?></strong>
        <?php if ($exc['closed'] ?? false): ?>
        <?= t('Suljettu', 'Closed') ?>
        <?php else: ?>
        <?= esc($exc['open'] ?? '') ?>–<?= esc($exc['close'] ?? '') ?>
        <?php endif; ?>
        <?php if (!empty($exc['note_' . lang()])): ?> — <?= esc($exc['note_' . lang()]) ?><?php endif; ?>
    </p>
    <?php endforeach; ?>
</div>
<?php endif; ?>
