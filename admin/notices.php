<?php
require_once __DIR__ . '/../includes/functions.php';
adminAuth();

$data = DataStore::ensure('notices', ['notices' => []]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $data['notices'] = [];
    $texts_fi = $_POST['text_fi'] ?? [];
    $texts_en = $_POST['text_en'] ?? [];
    $actives = $_POST['active'] ?? [];
    $starts = $_POST['start_date'] ?? [];
    $ends = $_POST['end_date'] ?? [];
    $styles = $_POST['style'] ?? [];
    foreach ($texts_fi as $i => $text_fi) {
        if (empty($text_fi)) continue;
        $data['notices'][] = [
            'id' => generateId(),
            'text_fi' => $text_fi,
            'text_en' => $texts_en[$i] ?? '',
            'active' => in_array((string)$i, $actives),
            'start_date' => $starts[$i] ?? '',
            'end_date' => $ends[$i] ?? '',
            'style' => $styles[$i] ?? 'info',
        ];
    }
    DataStore::save('notices', $data);
    $saved = true;
}

$title = 'Ilmoitukset';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($saved)): ?><div class="alert">Tallennettu!</div><?php endif; ?>

<div class="card">
    <h2>Väliaikaiset ilmoitukset</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <table>
            <thead><tr><th>FI</th><th>EN</th><th>Aktiivinen</th><th>Alku</th><th>Loppu</th><th>Tyyli</th></tr></thead>
            <tbody>
                <?php foreach ($data['notices'] as $i => $n): ?>
                <tr>
                    <td><input type="text" name="text_fi[]" value="<?= esc($n['text_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="text_en[]" value="<?= esc($n['text_en'] ?? '') ?>"></td>
                    <td><input type="checkbox" name="active[]" value="<?= $i ?>" <?= ($n['active'] ?? false) ? 'checked' : '' ?>></td>
                    <td><input type="date" name="start_date[]" value="<?= esc($n['start_date'] ?? '') ?>"></td>
                    <td><input type="date" name="end_date[]" value="<?= esc($n['end_date'] ?? '') ?>"></td>
                    <td>
                        <select name="style[]">
                            <option value="info" <?= ($n['style'] ?? '') === 'info' ? 'selected' : '' ?>>Info</option>
                            <option value="warning" <?= ($n['style'] ?? '') === 'warning' ? 'selected' : '' ?>>Varoitus</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" name="text_fi[]" placeholder="Uusi ilmoitus FI..."></td>
                    <td><input type="text" name="text_en[]" placeholder="New notice EN..."></td>
                    <td><input type="checkbox" name="active[]" value="<?= count($data['notices']) ?>" checked></td>
                    <td><input type="date" name="start_date[]"></td>
                    <td><input type="date" name="end_date[]"></td>
                    <td>
                        <select name="style[]">
                            <option value="info">Info</option>
                            <option value="warning">Varoitus</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="mt-4">Tallenna</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
