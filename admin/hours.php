<?php
require_once __DIR__ . '/../includes/functions.php';
adminAuth();

$s = settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

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

    $s['opening_exceptions'] = [];
    $exc_dates = $_POST['exc_date'] ?? [];
    foreach ($exc_dates as $i => $date) {
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

    DataStore::save('settings', $s);
    $saved = true;
}

$title = 'Aukioloajat';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($saved)): ?><div class="alert">Tallennettu!</div><?php endif; ?>

<div class="card">
    <h2>Viikoittaiset aukioloajat</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <table>
            <thead><tr><th>Päivä</th><th>Aukeaa</th><th>Sulkeutuu</th><th>Keittiö sulkeutuu</th><th>Suljettu</th><th>Huom.</th></tr></thead>
            <tbody>
                <?php foreach ($s['opening_hours'] ?? [] as $i => $h): ?>
                <tr>
                    <td><input type="hidden" name="oh_day[]" value="<?= esc($h['day']) ?>"><?= dayLabel($h['day']) ?></td>
                    <td><input type="time" name="oh_open[]" value="<?= esc($h['open'] ?? '') ?>"></td>
                    <td><input type="time" name="oh_close[]" value="<?= esc($h['close'] ?? '') ?>"></td>
                    <td><input type="time" name="oh_kitchen[]" value="<?= esc($h['kitchen_closes'] ?? '') ?>"></td>
                    <td><input type="checkbox" name="oh_closed[]" value="1" <?= ($h['closed'] ?? false) ? 'checked' : '' ?>></td>
                    <td><input type="text" name="oh_note[]" value="<?= esc($h['note'] ?? '') ?>"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 class="mt-4 mb-2">Poikkeusaukiolot</h3>
        <table>
            <thead><tr><th>Päivämäärä</th><th>Suljettu</th><th>Aukeaa</th><th>Sulkeutuu</th><th>Huom. FI</th><th>Huom. EN</th></tr></thead>
            <tbody>
                <?php foreach ($s['opening_exceptions'] ?? [] as $i => $e): ?>
                <tr>
                    <td><input type="date" name="exc_date[]" value="<?= esc($e['date'] ?? '') ?>"></td>
                    <td><input type="checkbox" name="exc_closed[]" value="1" <?= ($e['closed'] ?? false) ? 'checked' : '' ?>></td>
                    <td><input type="time" name="exc_open[]" value="<?= esc($e['open'] ?? '') ?>"></td>
                    <td><input type="time" name="exc_close[]" value="<?= esc($e['close'] ?? '') ?>"></td>
                    <td><input type="text" name="exc_note_fi[]" value="<?= esc($e['note_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="exc_note_en[]" value="<?= esc($e['note_en'] ?? '') ?>"></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="date" name="exc_date[]"></td>
                    <td><input type="checkbox" name="exc_closed[]" value="1"></td>
                    <td><input type="time" name="exc_open[]"></td>
                    <td><input type="time" name="exc_close[]"></td>
                    <td><input type="text" name="exc_note_fi[]"></td>
                    <td><input type="text" name="exc_note_en[]"></td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="mt-4">Tallenna</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
