<?php
require_once __DIR__ . '/../includes/functions.php';
adminAuth();

$data = DataStore::ensure('events', ['events' => []]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $data['events'] = [];
    $titles = $_POST['title_fi'] ?? [];
    foreach ($titles as $i => $title_fi) {
        if (empty($title_fi)) continue;
        $data['events'][] = [
            'id' => generateId(),
            'title_fi' => $title_fi,
            'title_en' => $_POST['title_en'][$i] ?? '',
            'date' => $_POST['date'][$i] ?? '',
            'start_time' => $_POST['start_time'][$i] ?? '',
            'end_time' => $_POST['end_time'][$i] ?? '',
            'description_fi' => $_POST['desc_fi'][$i] ?? '',
            'description_en' => $_POST['desc_en'][$i] ?? '',
            'visible' => !empty($_POST['visible'][$i]),
            'featured' => !empty($_POST['featured'][$i]),
        ];
    }
    DataStore::save('events', $data);
    $saved = true;
}

$title = 'Tapahtumat';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($saved)): ?><div class="alert">Tallennettu!</div><?php endif; ?>

<div class="card">
    <h2>Tapahtumat</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <table>
            <thead><tr><th>Otsikko FI</th><th>Otsikko EN</th><th>Päivä</th><th>Alku</th><th>Loppu</th><th>Kuvaus FI</th><th>Kuvaus EN</th><th>Näkyvä</th><th>Featured</th></tr></thead>
            <tbody>
                <?php foreach ($data['events'] as $i => $e): ?>
                <tr>
                    <td><input type="text" name="title_fi[]" value="<?= esc($e['title_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="title_en[]" value="<?= esc($e['title_en'] ?? '') ?>"></td>
                    <td><input type="date" name="date[]" value="<?= esc($e['date'] ?? '') ?>"></td>
                    <td><input type="time" name="start_time[]" value="<?= esc($e['start_time'] ?? '') ?>"></td>
                    <td><input type="time" name="end_time[]" value="<?= esc($e['end_time'] ?? '') ?>"></td>
                    <td><input type="text" name="desc_fi[]" value="<?= esc($e['description_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="desc_en[]" value="<?= esc($e['description_en'] ?? '') ?>"></td>
                    <td><input type="checkbox" name="visible[]" value="1" <?= ($e['visible'] ?? false) ? 'checked' : '' ?>></td>
                    <td><input type="checkbox" name="featured[]" value="1" <?= ($e['featured'] ?? false) ? 'checked' : '' ?>></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" name="title_fi[]" placeholder="Uusi..."></td>
                    <td><input type="text" name="title_en[]"></td>
                    <td><input type="date" name="date[]"></td>
                    <td><input type="time" name="start_time[]"></td>
                    <td><input type="time" name="end_time[]"></td>
                    <td><input type="text" name="desc_fi[]"></td>
                    <td><input type="text" name="desc_en[]"></td>
                    <td><input type="checkbox" name="visible[]" value="1" checked></td>
                    <td><input type="checkbox" name="featured[]" value="1"></td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="mt-4">Tallenna</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
