<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

$data = DataStore::ensure('lunch', ['items' => []]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $data['items'] = [];
    $names = $_POST['name_fi'] ?? [];
    foreach ($names as $i => $name_fi) {
        if (empty($name_fi)) continue;
        $data['items'][] = [
            'id' => $_POST['item_id'][$i] ?? generateId(),
            'weekday' => $_POST['weekday'][$i] ?? 'mon',
            'name_fi' => $name_fi,
            'name_en' => $_POST['name_en'][$i] ?? '',
            'description_fi' => $_POST['desc_fi'][$i] ?? '',
            'description_en' => $_POST['desc_en'][$i] ?? '',
            'price' => (float)($_POST['price'][$i] ?? 0),
            'dietary_tags' => $_POST['tags'][$i] ?? '',
            'visible' => !empty($_POST['visible'][$i]),
        ];
    }
    DataStore::save('lunch', $data);
    header('Location: lunch.php?saved=1');
    exit;
}

$title = 'Lounas';
include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['saved'])): ?><div class="alert">Tallennettu!</div><?php endif; ?>

<?php
$days = ['mon' => 'Maanantai', 'tue' => 'Tiistai', 'wed' => 'Keskiviikko', 'thu' => 'Torstai', 'fri' => 'Perjantai'];
$itemsByDay = [];
foreach ($data['items'] as $item) {
    $wd = $item['weekday'] ?? 'mon';
    $itemsByDay[$wd][] = $item;
}
$totalItems = count($data['items']);
$visibleItems = count(array_filter($data['items'], fn($i) => !empty($i['visible'])));
?>

<div class="editor-list-overview">
    <span class="editor-overview-pill"><strong><?= $totalItems ?></strong> lounasta</span>
    <span class="editor-overview-pill"><strong><?= $visibleItems ?></strong> näkyvissä</span>
</div>

<form method="post" class="mt-4">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">

    <div class="editor-items-stack" style="padding-bottom:0">
        <?php $idx = 0; ?>
        <?php foreach ($days as $dayKey => $dayLabel): ?>
        <?php $dayItems = $itemsByDay[$dayKey] ?? []; ?>
        <details class="day-card day-card--collapsible" data-day-card="<?= esc($dayKey) ?>" <?= empty($data['items']) && $dayKey === 'mon' ? 'open' : '' ?>>
            <summary class="day-card__header">
                <div style="display:flex;align-items:center;gap:8px">
                    <h3><?= esc($dayLabel) ?></h3>
                    <?php if (!empty($dayItems)): ?>
                    <span class="editor-chip"><?= count($dayItems) ?> annosta</span>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn--secondary btn--sm day-card__add-btn" data-add-day="<?= esc($dayKey) ?>">+ Lisää</button>
            </summary>
            <div class="day-card__body">
                <div class="day-card__entries" data-day="<?= esc($dayKey) ?>">
                    <?php if (empty($dayItems)): ?>
                    <div class="empty-state" style="padding:1rem">
                        <p class="empty-state__text">Ei lounaita. Paina "Lisää" lisätäksesi annos.</p>
                    </div>
                    <?php endif; ?>
                    <?php foreach ($dayItems as $item): ?>
                    <div class="lunch-entry" data-lunch-item>
                        <input type="hidden" name="item_id[<?= $idx ?>]" value="<?= esc($item['id'] ?? '') ?>">
                        <input type="hidden" name="weekday[<?= $idx ?>]" value="<?= esc($dayKey) ?>">
                        <div class="lunch-entry__header">
                            <div class="flex items-center gap-2 flex-wrap">
                                <?php renderTranslationBadge($item['name_fi'] ?? '', $item['name_en'] ?? ''); ?>
                                <?= !empty($item['visible']) ? renderStatusBadge('published', 'Näkyvissä') : renderStatusBadge('hidden', 'Piilotettu') ?>
                            </div>
                            <label class="editor-visibility-toggle" style="padding:0.3rem 0.5rem;font-size:0.75rem">
                                <input type="checkbox" name="visible[<?= $idx ?>]" value="1" <?= !empty($item['visible']) ? 'checked' : '' ?>>
                                <span>Näkyy</span>
                            </label>
                        </div>
                        <div class="lunch-entry__fields">
                            <div class="form-group form-group--fi">
                                <label><?= flagSvg('fi') ?> Nimi</label>
                                <input type="text" name="name_fi[<?= $idx ?>]" value="<?= esc($item['name_fi'] ?? '') ?>" placeholder="Lounas FI">
                            </div>
                            <div class="form-group form-group--en">
                                <label><?= flagSvg('gb') ?> Name</label>
                                <input type="text" name="name_en[<?= $idx ?>]" value="<?= esc($item['name_en'] ?? '') ?>" placeholder="Lunch EN">
                            </div>
                            <div class="form-group form-group--fi">
                                <label><?= flagSvg('fi') ?> Kuvaus</label>
                                <input type="text" name="desc_fi[<?= $idx ?>]" value="<?= esc($item['description_fi'] ?? '') ?>" placeholder="Kuvaus suomeksi">
                            </div>
                            <div class="form-group form-group--en">
                                <label><?= flagSvg('gb') ?> Description</label>
                                <input type="text" name="desc_en[<?= $idx ?>]" value="<?= esc($item['description_en'] ?? '') ?>" placeholder="Description">
                            </div>
                            <div class="form-group">
                                <label>Hinta</label>
                                <input class="input-compact" type="number" step="0.01" name="price[<?= $idx ?>]" value="<?= esc((string) ($item['price'] ?? '')) ?>" placeholder="12.50">
                            </div>
                            <div class="form-group">
                                <label>Tagit</label>
                                <input class="input-compact" type="text" name="tags[<?= $idx ?>]" value="<?= esc($item['dietary_tags'] ?? '') ?>" placeholder="L, G, V">
                            </div>
                        </div>
                    </div>
                    <?php $idx++; ?>
                    <?php endforeach; ?>
                    <div class="lunch-entry lunch-entry--new" data-lunch-item hidden>
                        <input type="hidden" name="weekday[<?= $idx ?>]" value="<?= esc($dayKey) ?>">
                        <div class="lunch-entry__header">
                            <span class="text-xs text-gray" style="font-weight:700;letter-spacing:.05em;text-transform:uppercase">Uusi annos</span>
                        </div>
                        <div class="lunch-entry__fields">
                            <div class="form-group form-group--fi">
                                <label><?= flagSvg('fi') ?> Nimi</label>
                                <input type="text" name="name_fi[<?= $idx ?>]" placeholder="Lounas FI">
                            </div>
                            <div class="form-group form-group--en">
                                <label><?= flagSvg('gb') ?> Name</label>
                                <input type="text" name="name_en[<?= $idx ?>]" placeholder="Lunch EN">
                            </div>
                            <div class="form-group form-group--fi">
                                <label><?= flagSvg('fi') ?> Kuvaus</label>
                                <input type="text" name="desc_fi[<?= $idx ?>]" placeholder="Kuvaus suomeksi">
                            </div>
                            <div class="form-group form-group--en">
                                <label><?= flagSvg('gb') ?> Description</label>
                                <input type="text" name="desc_en[<?= $idx ?>]" placeholder="Description">
                            </div>
                            <div class="form-group">
                                <label>Hinta</label>
                                <input class="input-compact" type="number" step="0.01" name="price[<?= $idx ?>]" placeholder="12.50">
                            </div>
                            <div class="form-group">
                                <label>Tagit</label>
                                <input class="input-compact" type="text" name="tags[<?= $idx ?>]" placeholder="L, G, V">
                            </div>
                        </div>
                        <input type="hidden" name="visible[<?= $idx ?>]" value="1">
                    </div>
                    <?php $idx++; ?>
                </div>
            </div>
        </details>
        <?php endforeach; ?>
    </div>

    <div class="admin-sticky-save">
        <span class="admin-sticky-save__info">Tallenna lounaat. Tyhjät nimirivit jätetään huomiotta.</span>
        <button type="submit">Tallenna lounaat</button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
