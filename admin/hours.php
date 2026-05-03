<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

$s = settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    if (isset($_POST['action']) && $_POST['action'] === 'add_exception') {
        $excDate = $_POST['exc_date'] ?? '';
        if ($excDate !== '') {
            $s['opening_exceptions'][] = [
                'date' => $excDate,
                'closed' => !empty($_POST['exc_closed']),
                'open' => $_POST['exc_open'] ?? '',
                'close' => $_POST['exc_close'] ?? '',
                'note_fi' => $_POST['exc_note_fi'] ?? '',
                'note_en' => $_POST['exc_note_en'] ?? '',
            ];
            DataStore::save('settings', $s);
            header('Location: /admin/hours.php?status=added');
            exit;
        }
        header('Location: /admin/hours.php?status=missing-date');
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'edit_exception') {
        $s['opening_exceptions'] = [];
        $dates = $_POST['exc_date'] ?? [];
        foreach ($dates as $i => $date) {
            if (empty($date)) continue;
            $s['opening_exceptions'][] = [
                'date' => $date,
                'closed' => !empty($_POST['exc_closed'][$i]),
                'open' => $_POST['exc_open'][$i] ?? '',
                'close' => $_POST['exc_close'][$i] ?? '',
                'note_fi' => $_POST['exc_note_fi'][$i] ?? '',
                'note_en' => $_POST['exc_note_en'][$i] ?? '',
            ];
        }
    } else {
        $s['opening_hours'] = [];
        $days = $_POST['oh_day'] ?? [];
        foreach ($days as $i => $day) {
            $s['opening_hours'][] = [
                'day' => $day,
                'open' => $_POST['oh_open'][$i] ?? '',
                'close' => $_POST['oh_close'][$i] ?? '',
                'kitchen_closes' => $_POST['oh_kitchen'][$i] ?? '',
                'closed' => !empty($_POST['oh_closed'][$i]),
                'note' => $_POST['oh_note'][$i] ?? '',
            ];
        }
    }

    DataStore::save('settings', $s);
    $saved = true;
}

$todayPreview = '';
$todayName = date('N');
$dayMap = ['1' => 'mon','2' => 'tue','3' => 'wed','4' => 'thu','5' => 'fri','6' => 'sat','7' => 'sun'];
$todayKey = $dayMap[$todayName] ?? '';
$todayDate = date('Y-m-d');

// Check exceptions first
foreach ($s['opening_exceptions'] ?? [] as $exc) {
    if (($exc['date'] ?? '') === $todayDate) {
        if (!empty($exc['closed'])) {
            $todayPreview = 'Tänään: Suljettu (' . ($exc['note_fi'] ?: 'poikkeus') . ')';
        } else {
            $todayPreview = 'Tänään: ' . $exc['open'] . '–' . $exc['close'];
        }
        break;
    }
}
if ($todayPreview === '') {
    foreach ($s['opening_hours'] ?? [] as $h) {
        if (($h['day'] ?? '') === $todayKey) {
            if (!empty($h['closed'])) {
                $todayPreview = 'Tänään: Suljettu';
            } else {
                $kitchen = !empty($h['kitchen_closes']) ? ' (keittiö ' . $h['kitchen_closes'] . ')' : '';
                $todayPreview = 'Tänään: ' . $h['open'] . '–' . $h['close'] . $kitchen;
            }
            break;
        }
    }
}

$flash = $_GET['status'] ?? '';

$title = 'Aukioloajat';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($saved)): ?><div class="alert">Tallennettu!</div><?php endif; ?>
<?php $flashMsgs = ['added' => 'Poikkeus lisättiin.', 'missing-date' => 'Valitse päivämäärä poikkeukselle.']; ?>
<?php if (isset($flashMsgs[$flash])): ?><div class="alert"><?= esc($flashMsgs[$flash]) ?></div><?php endif; ?>

<div class="card">
    <?php if ($todayPreview): ?>
    <div class="flex items-center gap-2 mb-4" style="padding:10px 14px;background:var(--admin-success-soft);border:1px solid #b4e4c7;border-radius:var(--admin-radius-ctrl)">
        <span style="color:var(--admin-success);font-weight:700;font-size:13px"><?= esc($todayPreview) ?></span>
    </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <div class="editor-items-stack" style="padding-bottom:0">
            <?php $dayFi = ['mon'=>'Maanantai','tue'=>'Tiistai','wed'=>'Keskiviikko','thu'=>'Torstai','fri'=>'Perjantai','sat'=>'Lauantai','sun'=>'Sunnuntai']; ?>
            <?php foreach ($s['opening_hours'] ?? [] as $i => $h): ?>
            <div class="day-card">
                <div class="day-card__header">
                    <h3><?= esc($dayFi[$h['day']] ?? $h['day']) ?></h3>
                    <label class="editor-visibility-toggle" style="padding:0.3rem 0.6rem;font-size:0.78rem">
                        <input type="checkbox" name="oh_closed[<?= $i ?>]" value="1" <?= !empty($h['closed']) ? 'checked' : '' ?> onchange="this.closest('.day-card').querySelector('.hours-fields').classList.toggle('is-disabled', this.checked)">
                        <span>Suljettu</span>
                    </label>
                </div>
                <div class="day-card__body">
                    <input type="hidden" name="oh_day[<?= $i ?>]" value="<?= esc($h['day']) ?>">
                    <div class="hours-fields <?= !empty($h['closed']) ? 'is-disabled' : '' ?>" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px">
                        <div class="form-group" style="margin-bottom:0">
                            <label>Aukeaa</label>
                            <input type="time" name="oh_open[<?= $i ?>]" value="<?= esc($h['open'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Sulkeutuu</label>
                            <input type="time" name="oh_close[<?= $i ?>]" value="<?= esc($h['close'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Keittiö sulkeutuu</label>
                            <input type="time" name="oh_kitchen[<?= $i ?>]" value="<?= esc($h['kitchen_closes'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Huomautus</label>
                            <input type="text" name="oh_note[<?= $i ?>]" value="<?= esc($h['note'] ?? '') ?>" placeholder="Esim. kesäaika">
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="admin-sticky-save">
            <span class="admin-sticky-save__info">Tallenna viikkorytmi ja poikkeukset erikseen.</span>
            <button type="submit">Tallenna viikkoaukiolo</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="section-head mb-4">
        <div>
            <h2>Poikkeusaukiolot</h2>
            <p>Lisää yksittäisiä poikkeuspäiviä, jolloin aukiolo poikkeaa normaalista.</p>
        </div>
    </div>

    <?php if (!empty($s['opening_exceptions'])): ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="action" value="edit_exception">
        <div class="editor-items-stack" style="padding-bottom:0">
            <?php usort($s['opening_exceptions'], fn($a, $b) => ($a['date'] ?? '') <=> ($b['date'] ?? '')); ?>
            <?php foreach ($s['opening_exceptions'] as $i => $e): ?>
            <div class="day-card">
                <div class="day-card__header">
                    <h3><?= esc(date('d.m.Y', strtotime($e['date']))) ?></h3>
                    <label class="editor-visibility-toggle" style="padding:0.3rem 0.6rem;font-size:0.78rem">
                        <input type="checkbox" name="exc_closed[<?= $i ?>]" value="1" <?= !empty($e['closed']) ? 'checked' : '' ?>>
                        <span>Suljettu</span>
                    </label>
                </div>
                <div class="day-card__body">
                    <input type="hidden" name="exc_date[<?= $i ?>]" value="<?= esc($e['date'] ?? '') ?>">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:0.75rem">
                        <div class="form-group" style="margin-bottom:0">
                            <label>Aukeaa</label>
                            <input type="time" name="exc_open[<?= $i ?>]" value="<?= esc($e['open'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Sulkeutuu</label>
                            <input type="time" name="exc_close[<?= $i ?>]" value="<?= esc($e['close'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Huom. FI</label>
                            <input type="text" name="exc_note_fi[<?= $i ?>]" value="<?= esc($e['note_fi'] ?? '') ?>" placeholder="Syy suomeksi">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Huom. EN</label>
                            <input type="text" name="exc_note_en[<?= $i ?>]" value="<?= esc($e['note_en'] ?? '') ?>" placeholder="Reason in English">
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="admin-sticky-save">
            <span class="admin-sticky-save__info">Tallenna poikkeusaukiolojen muutokset.</span>
            <button type="submit">Tallenna poikkeukset</button>
        </div>
    </form>
    <?php endif; ?>

    <details class="admin-collapsible card--section" style="margin-top:1rem" <?= empty($s['opening_exceptions']) ? 'open' : '' ?>>
        <summary><strong>Lisää uusi poikkeus</strong></summary>
        <div class="admin-collapsible__body">
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="action" value="add_exception">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:0.75rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label>Päivämäärä</label>
                        <input type="date" name="exc_date" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>Aukeaa</label>
                        <input type="time" name="exc_open">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>Sulkeutuu</label>
                        <input type="time" name="exc_close">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>Huom. FI</label>
                        <input type="text" name="exc_note_fi" placeholder="Syy">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>Huom. EN</label>
                        <input type="text" name="exc_note_en" placeholder="Reason">
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-2">
                    <label style="display:flex;align-items:center;gap:0.4rem;font-weight:700;font-size:0.82rem">
                        <input type="checkbox" name="exc_closed" value="1"> Suljettu koko päivän
                    </label>
                    <button type="submit" class="btn btn--primary btn--sm">Lisää poikkeus</button>
                </div>
            </form>
        </div>
    </details>
</div>

<style>
.hours-fields.is-disabled { opacity: 0.5; pointer-events: none; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
