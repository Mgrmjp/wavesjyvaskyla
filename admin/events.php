<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

$data = DataStore::ensure('events', ['events' => []]);

function renderEventFields(array $event, string $key): void {
    ?>
    <div class="editor-item-panels">
        <input type="hidden" name="event_id[<?= esc($key) ?>]" value="<?= esc((string) ($event['id'] ?? '')) ?>">
        <section class="editor-item-panel">
            <div class="editor-item-panel__head"><h5>Perustiedot</h5></div>
            <div class="editor-item-panel__body">
            <div class="editor-item-grid">
                <div class="form-group form-group--fi span-4"><label><?= flagSvg('fi') ?> Otsikko</label><input type="text" name="title_fi[<?= esc($key) ?>]" value="<?= esc((string) ($event['title_fi'] ?? '')) ?>" placeholder="Esim. Kesäilta satamassa"></div>
                <div class="form-group form-group--en span-4"><label><?= flagSvg('gb') ?> Title</label><input type="text" name="title_en[<?= esc($key) ?>]" value="<?= esc((string) ($event['title_en'] ?? '')) ?>" placeholder="English title"></div>
                <div class="form-group span-2"><label>Päivä</label><input class="input-compact" type="date" name="date[<?= esc($key) ?>]" value="<?= esc((string) ($event['date'] ?? '')) ?>"></div>
                <div class="form-group span-1"><label>Alku</label><input class="input-compact" type="time" name="start_time[<?= esc($key) ?>]" value="<?= esc((string) ($event['start_time'] ?? '')) ?>"></div>
                <div class="form-group span-1"><label>Loppu</label><input class="input-compact" type="time" name="end_time[<?= esc($key) ?>]" value="<?= esc((string) ($event['end_time'] ?? '')) ?>"></div>
                <div class="form-group span-4"><label>Linkki/liput</label><input type="url" name="event_link[<?= esc($key) ?>]" value="<?= esc((string) ($event['link'] ?? '')) ?>" placeholder="https://..."></div>
                <div class="form-group span-2"><label>Sijainti</label><input type="text" name="event_location[<?= esc($key) ?>]" value="<?= esc((string) ($event['location'] ?? '')) ?>" placeholder="Waves satama"></div>
            </div>
            </div>
        </section>
        <section class="editor-item-panel">
            <div class="editor-item-panel__head"><h5>Kuvaukset</h5></div>
            <div class="editor-item-panel__body">
            <div class="editor-item-grid">
                <div class="form-group form-group--fi span-6"><label><?= flagSvg('fi') ?> Kuvaus</label><textarea name="desc_fi[<?= esc($key) ?>]" placeholder="Lyhyt kuvaus tapahtumasta suomeksi"><?= esc((string) ($event['description_fi'] ?? '')) ?></textarea></div>
                <div class="form-group form-group--en span-6"><label><?= flagSvg('gb') ?> Description</label><textarea name="desc_en[<?= esc($key) ?>]" placeholder="Short English event description"><?= esc((string) ($event['description_en'] ?? '')) ?></textarea></div>
            </div>
            </div>
        </section>
    </div>
    <?php
}

function renderEventListEntry(array $event, string $key): void {
    $title = trim((string) ($event['title_fi'] ?? ''));
    if ($title === '') $title = 'Nimetön tapahtuma';
    $dayLabel = 'Pvm';
    $dateLabel = '';
    if (!empty($event['date'])) {
        $dayLabel = date('d.m', strtotime((string) $event['date']));
        $dateLabel = date('d.m.Y', strtotime((string) $event['date']));
    }
    if (!empty($event['start_time'])) $dateLabel .= ($dateLabel !== '' ? ' · ' : '') . (string) $event['start_time'];
    $status = 'draft';
    if (!empty($event['visible'])) {
        if (!empty($event['date']) && $event['date'] < date('Y-m-d')) $status = 'expired';
        else $status = 'published';
    }
    ?>
    <article class="editor-list-item" data-sort-item>
        <div class="editor-list-item__row">
            <div class="editor-list-item__body">
                <div class="editor-list-item__date-badge"><strong data-summary-day><?= esc($dayLabel) ?></strong><span><?= !empty($event['date']) ? esc(date('Y', strtotime((string) $event['date']))) : 'TBD' ?></span></div>
                <div class="editor-list-item__content">
                    <h4><?= esc($title) ?></h4>
                    <div class="editor-list-item__meta">
                        <?php renderTranslationBadge($event['title_fi'] ?? '', $event['title_en'] ?? ''); ?>
                        <span class="editor-chip" data-summary-date><?= esc($dateLabel !== '' ? $dateLabel : 'Ei päivää') ?></span>
                        <?php renderStatusBadge($status); ?>
                        <?php if (!empty($event['featured'])): ?><span class="editor-chip editor-chip--soft">Featured</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="editor-list-item__tools">
                <div class="editor-reorder-group">
                    <span class="editor-order-pill"><strong data-sort-index></strong></span>
                    <div class="editor-reorder-buttons">
                        <button type="button" class="editor-tool-button" data-move="up" aria-label="Siirrä ylös">▲</button>
                        <button type="button" class="editor-tool-button" data-move="down" aria-label="Siirrä alas">▼</button>
                    </div>
                </div>
                <button type="button" class="editor-tool-button editor-tool-button--primary" data-toggle-details aria-expanded="false">Muokkaa</button>
            </div>
        </div>
        <div class="editor-list-item__details" hidden>
            <div class="editor-item-card">
                <?php renderEventFields($event, $key); ?>
                <div class="editor-item-card__footer">
                    <div class="editor-flags">
                        <label class="editor-visibility-toggle"><input type="checkbox" name="visible[<?= esc($key) ?>]" value="1" <?= !empty($event['visible']) ? 'checked' : '' ?> data-summary-visible><span>Näkyy listalla</span></label>
                        <label class="editor-visibility-toggle"><input type="checkbox" name="featured[<?= esc($key) ?>]" value="1" <?= !empty($event['featured']) ? 'checked' : '' ?>><span>Featured</span></label>
                    </div>
                </div>
            </div>
        </div>
    </article>
    <?php
}

$eventsList = $data['events'] ?? [];
$today = date('Y-m-d');
$upcoming = array_filter($eventsList, fn($e) => ($e['date'] ?? '') >= $today && !empty($e['visible']));
$draftEv = array_filter($eventsList, fn($e) => empty($e['visible']));
$past = array_filter($eventsList, fn($e) => ($e['date'] ?? '') < $today && !empty($e['visible']));

$eventCount = count($eventsList);
$visibleEventCount = count(array_filter($eventsList, fn($e) => !empty($e['visible'])));
$featuredEventCount = count(array_filter($eventsList, fn($e) => !empty($e['featured'])));

$flash = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create_event') {
        $titleFi = trim($_POST['new_title_fi'] ?? '');
        if ($titleFi !== '') {
            $data['events'][] = [
                'id' => generateId(),
                'title_fi' => $titleFi, 'title_en' => $_POST['new_title_en'] ?? '',
                'date' => $_POST['new_date'] ?? '', 'start_time' => $_POST['new_start_time'] ?? '', 'end_time' => $_POST['new_end_time'] ?? '',
                'description_fi' => $_POST['new_desc_fi'] ?? '', 'description_en' => $_POST['new_desc_en'] ?? '',
                'visible' => !empty($_POST['new_visible']), 'featured' => !empty($_POST['new_featured']),
            ];
            DataStore::save('events', $data);
            header('Location: /admin/events.php?status=event-created#existing-events'); exit;
        }
        header('Location: /admin/events.php?status=event-missing-title#new-event'); exit;
    }
    if ($action === 'save_events') {
        $data['events'] = [];
        $titles = $_POST['title_fi'] ?? [];
        foreach ($titles as $i => $titleFi) {
            if (trim((string) $titleFi) === '') continue;
            $data['events'][] = [
                'id' => $_POST['event_id'][$i] ?? generateId(),
                'title_fi' => $titleFi, 'title_en' => $_POST['title_en'][$i] ?? '',
                'date' => $_POST['date'][$i] ?? '', 'start_time' => $_POST['start_time'][$i] ?? '', 'end_time' => $_POST['end_time'][$i] ?? '',
                'description_fi' => $_POST['desc_fi'][$i] ?? '', 'description_en' => $_POST['desc_en'][$i] ?? '',
                'visible' => !empty($_POST['visible'][$i]), 'featured' => !empty($_POST['featured'][$i]),
                'link' => $_POST['event_link'][$i] ?? '', 'location' => $_POST['event_location'][$i] ?? '',
            ];
        }
        DataStore::save('events', $data);
        header('Location: /admin/events.php?status=events-saved#existing-events'); exit;
    }
}

$activeTab = $_GET['tab'] ?? 'upcoming';

$title = 'Tapahtumat';
include __DIR__ . '/includes/header.php';
?>

<?php
$flashMessages = [
    'event-created' => 'Uusi tapahtuma luotiin.',
    'event-missing-title' => 'Anna tapahtumalle vähintään suomenkielinen otsikko.',
    'events-saved' => 'Tapahtumien muutokset tallennettiin.',
];
if (isset($flashMessages[$flash])): ?><div class="alert"><?= esc($flashMessages[$flash]) ?></div><?php endif; ?>

<div class="editor-list-overview">
    <span class="editor-overview-pill"><strong><?= count($upcoming) ?></strong> tulossa</span>
    <span class="editor-overview-pill"><strong><?= count($draftEv) ?></strong> luonnosta</span>
    <span class="editor-overview-pill"><strong><?= count($past) ?></strong> mennyttä</span>
    <span class="editor-overview-pill"><strong><?= $featuredEventCount ?></strong> featured</span>
</div>

<details class="admin-collapsible card" id="new-event" <?= empty($eventsList) ? 'open' : '' ?>>
    <summary><strong>Lisää tapahtuma</strong><span class="text-sm text-gray">Täytä tärkeimmät tiedot ensin.</span></summary>
    <div class="admin-collapsible__body">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="action" value="create_event">
        <div class="editor-create-card">
            <div class="editor-item-panels">
                <section class="editor-item-panel">
                    <div class="editor-item-panel__head"><h5>Perustiedot</h5></div>
                    <div class="editor-item-panel__body">
                    <div class="editor-item-grid">
                        <div class="form-group form-group--fi span-4"><label><?= flagSvg('fi') ?> Otsikko</label><input type="text" name="new_title_fi" placeholder="Esim. DJ-ilta Wavesissa"></div>
                        <div class="form-group form-group--en span-4"><label><?= flagSvg('gb') ?> Title</label><input type="text" name="new_title_en" placeholder="English title"></div>
                        <div class="form-group span-2"><label>Päivä</label><input class="input-compact" type="date" name="new_date"></div>
                        <div class="form-group span-1"><label>Alku</label><input class="input-compact" type="time" name="new_start_time"></div>
                        <div class="form-group span-1"><label>Loppu</label><input class="input-compact" type="time" name="new_end_time"></div>
                    </div>
                    </div>
                </section>
            </div>
            <div class="editor-item-card__footer">
                <div class="editor-flags">
                    <label class="editor-visibility-toggle"><input type="checkbox" name="new_visible" value="1" checked><span>Luo näkyvänä</span></label>
                    <label class="editor-visibility-toggle"><input type="checkbox" name="new_featured" value="1"><span>Featured</span></label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn--primary mt-4">Luo tapahtuma</button>
    </form>
    </div>
</details>

<div class="card" id="existing-events">
    <div class="section-head">
        <div>
            <h2>Tapahtumat</h2>
            <p>Avaa yksi tapahtuma kerrallaan.</p>
        </div>
    </div>

    <nav class="tab-bar">
        <a href="?tab=upcoming#existing-events" class="tab-bar__item <?= $activeTab === 'upcoming' ? 'is-active' : '' ?>">Tulossa (<?= count($upcoming) ?>)</a>
        <a href="?tab=draft#existing-events" class="tab-bar__item <?= $activeTab === 'draft' ? 'is-active' : '' ?>">Luonnokset (<?= count($draftEv) ?>)</a>
        <a href="?tab=past#existing-events" class="tab-bar__item <?= $activeTab === 'past' ? 'is-active' : '' ?>">Mennyt (<?= count($past) ?>)</a>
    </nav>

    <?php
    $filtered = match($activeTab) {
        'draft' => $draftEv,
        'past' => $past,
        default => $upcoming,
    };
    if (empty($filtered) && $activeTab !== 'past') $filtered = $upcoming;
    ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="action" value="save_events">
        <div class="editor-items-stack">
            <?php if (empty($filtered)): ?>
                <?php renderEmptyState('●', 'Ei tapahtumia', 'Luo ensimmäinen tapahtuma yllä olevasta lomakkeesta.', '', 'Luo tapahtuma', 'new-event'); ?>
            <?php else: ?>
                <?php foreach ($filtered as $i => $event): ?>
                    <?php renderEventListEntry($event, (string) $i); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($filtered)): ?>
        <div class="admin-sticky-save">
            <span class="admin-sticky-save__info">Tallenna muutokset, kun olet tarkistanut tapahtumien tiedot.</span>
            <button type="submit">Tallenna muutokset</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.querySelectorAll('.editor-list-item').forEach((item) => {
    const titleInput = item.querySelector('input[name^="title_fi"]');
    const dateInput = item.querySelector('input[name^="date"]');
    const startInput = item.querySelector('input[name^="start_time"]');
    const visibleCheckbox = item.querySelector('[data-summary-visible]');
    const featuredCheckbox = item.querySelector('input[name^="featured"]');
    const titleTarget = item.querySelector('h4');
    const dayTarget = item.querySelector('[data-summary-day]');
    const dateTarget = item.querySelector('[data-summary-date]');
    const statusTarget = item.querySelector('[data-summary-status]');
    const featuredTarget = item.querySelector('[data-summary-featured]');
    const updateDateSummary = () => {
        if (!dateTarget || !dayTarget) return;
        const dateValue = dateInput?.value || '';
        const timeValue = startInput?.value || '';
        if (!dateValue) { dayTarget.textContent = 'Pvm'; dateTarget.textContent = 'Ei päivää'; return; }
        const parsed = new Date(dateValue + 'T00:00:00');
        if (Number.isNaN(parsed.getTime())) { dayTarget.textContent = 'Pvm'; dateTarget.textContent = 'Ei päivää'; return; }
        const day = String(parsed.getDate()).padStart(2, '0');
        const month = String(parsed.getMonth() + 1).padStart(2, '0');
        dayTarget.textContent = day + '.' + month;
        let label = day + '.' + month + '.' + parsed.getFullYear();
        if (timeValue) label += ' \u00b7 ' + timeValue;
        dateTarget.textContent = label;
    };
    if (titleInput && titleTarget) titleInput.addEventListener('input', () => { titleTarget.textContent = titleInput.value.trim() || 'Nimet\u00f6n tapahtuma'; });
    if (dateInput && dateTarget) dateInput.addEventListener('change', updateDateSummary);
    if (startInput && dateTarget) startInput.addEventListener('change', updateDateSummary);
    if (visibleCheckbox && statusTarget) visibleCheckbox.addEventListener('change', () => { statusTarget.textContent = visibleCheckbox.checked ? 'N\u00e4kyviss\u00e4' : 'Piilotettu'; });
    if (featuredCheckbox && featuredTarget) featuredCheckbox.addEventListener('change', () => { featuredTarget.hidden = !featuredCheckbox.checked; });
    updateDateSummary();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
