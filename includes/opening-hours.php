<?php
$s = settings();
$now = new DateTime();
$today = $now->format('N');
$dayMap = ['1' => 'mon', '2' => 'tue', '3' => 'wed', '4' => 'thu', '5' => 'fri', '6' => 'sat', '7' => 'sun'];
$todayKey = $dayMap[$today] ?? '';
$todayDate = $now->format('Y-m-d');
$currentTime = $now->format('H:i');
$todayHours = null;
$todayException = null;

foreach ($s['opening_exceptions'] ?? [] as $exc) {
    if (($exc['date'] ?? '') === $todayDate) {
        $todayException = $exc;
        break;
    }
}

if ($todayException !== null) {
    $todayHours = [
        'day' => $todayKey,
        'closed' => (bool) ($todayException['closed'] ?? false),
        'open' => (string) ($todayException['open'] ?? ''),
        'close' => (string) ($todayException['close'] ?? ''),
        'note' => (string) ($todayException['note_' . lang()] ?? ''),
    ];
} else {
    foreach ($s['opening_hours'] ?? [] as $h) {
        if (($h['day'] ?? '') === $todayKey) {
            $todayHours = $h;
            break;
        }
    }
}

$todayStateClass = 'is-closed';
$todayStatus = t('Suljettu tänään', 'Closed today');
$todayNote = (string) ($todayHours['note'] ?? '');

if ($todayHours !== null && !($todayHours['closed'] ?? false)) {
    $todayOpen = (string) ($todayHours['open'] ?? '');
    $todayClose = (string) ($todayHours['close'] ?? '');

    if ($todayOpen !== '' && $todayClose !== '') {
        if ($currentTime < $todayOpen) {
            $todayStatus = t('Aukeaa tänään klo ', 'Opens today at ') . $todayOpen;
            $todayStateClass = 'is-upcoming';
        } elseif ($currentTime >= $todayClose) {
            $todayStatus = t('Suljettu tältä päivältä', 'Closed for today');
            $todayStateClass = 'is-closed';
        } else {
            $todayStatus = t('Avoinna nyt klo ', 'Open now until ') . $todayClose;
            $todayStateClass = 'is-open';
        }
    } else {
        $todayStatus = t('Auki tänään', 'Open today');
        $todayStateClass = 'is-open';
    }
}
?>
<div class="hours-current">
    <p class="hours-current-value <?= $todayStateClass ?>"><?= esc($todayStatus) ?></p>
    <?php if ($todayException !== null): ?>
    <p class="hours-note"><?= t('Poikkeus aukioloajassa', 'Schedule exception today') ?></p>
    <?php endif; ?>
    <?php if ($todayNote !== ''): ?>
    <p class="hours-note"><?= esc($todayNote) ?></p>
    <?php endif; ?>
</div>

<div class="hours-week">
    <p class="label"><?= t('Viikon aukioloajat', 'Weekly hours') ?></p>
    <div class="hours-list">
        <?php foreach ($s['opening_hours'] ?? [] as $h): ?>
        <div class="hours-row <?= ($h['day'] ?? '') === $todayKey ? 'is-today' : '' ?>">
            <span class="hours-row-day"><?= dayLabel($h['day'] ?? '') ?></span>
            <?php if ($h['closed'] ?? false): ?>
            <span class="hours-row-value is-closed"><?= t('Suljettu', 'Closed') ?></span>
            <?php else: ?>
            <span class="hours-row-value"><?= esc($h['open'] ?? '') ?>–<?= esc($h['close'] ?? '') ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$exceptions = [];
foreach ($s['opening_exceptions'] ?? [] as $exc) {
    if (($exc['date'] ?? '') >= date('Y-m-d')) $exceptions[] = $exc;
}
if (count($exceptions)):
?>
<div class="hours-exceptions">
    <p class="label mb-2"><?= t('Poikkeukset', 'Exceptions') ?></p>
    <?php foreach ($exceptions as $exc): ?>
    <p class="text-sm text-muted">
        <strong class="text-text"><?= date('d.m.', strtotime($exc['date'])) ?></strong>
        <?php if ($exc['closed'] ?? false): ?>
        <?= t('Suljettu', 'Closed') ?>
        <?php else: ?>
        <?= esc($exc['open'] ?? '') ?>–<?= esc($exc['close'] ?? '') ?>
        <?php endif; ?>
        <?php if (!empty($exc['note_' . lang()])): ?> - <?= esc($exc['note_' . lang()]) ?><?php endif; ?>
    </p>
    <?php endforeach; ?>
</div>
<?php endif; ?>
