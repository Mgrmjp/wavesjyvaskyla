<?php
require_once __DIR__ . '/../includes/functions.php';
adminAuth();

$data = DataStore::ensure('lunch', ['items' => []]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $data['items'] = [];
    $names = $_POST['name_fi'] ?? [];
    foreach ($names as $i => $name_fi) {
        if (empty($name_fi)) continue;
        $data['items'][] = [
            'id' => generateId(),
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
    $saved = true;
}

$title = 'Lounas';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($saved)): ?><div class="alert">Tallennettu!</div><?php endif; ?>

<div class="card">
    <h2>Viikon lounaat</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <table>
            <thead><tr><th>Päivä</th><th>Nimi FI</th><th>Nimi EN</th><th>Kuvaus FI</th><th>Kuvaus EN</th><th>Hinta</th><th>Tagit</th><th>Näkyvä</th></tr></thead>
            <tbody>
                <?php foreach ($data['items'] as $i => $item): ?>
                <tr>
                    <td>
                        <select name="weekday[]">
                            <?php foreach (['mon'=>'Maanantai','tue'=>'Tiistai','wed'=>'Keskiviikko','thu'=>'Torstai','fri'=>'Perjantai'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($item['weekday'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="name_fi[]" value="<?= esc($item['name_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="name_en[]" value="<?= esc($item['name_en'] ?? '') ?>"></td>
                    <td><input type="text" name="desc_fi[]" value="<?= esc($item['description_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="desc_en[]" value="<?= esc($item['description_en'] ?? '') ?>"></td>
                    <td><input type="number" step="0.01" name="price[]" value="<?= esc($item['price'] ?? '') ?>" style="width:70px"></td>
                    <td><input type="text" name="tags[]" value="<?= esc($item['dietary_tags'] ?? '') ?>" style="width:80px"></td>
                    <td><input type="checkbox" name="visible[]" value="1" <?= ($item['visible'] ?? false) ? 'checked' : '' ?>></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td>
                        <select name="weekday[]">
                            <option value="mon">Maanantai</option>
                            <option value="tue">Tiistai</option>
                            <option value="wed">Keskiviikko</option>
                            <option value="thu">Torstai</option>
                            <option value="fri">Perjantai</option>
                        </select>
                    </td>
                    <td><input type="text" name="name_fi[]" placeholder="Uusi..."></td>
                    <td><input type="text" name="name_en[]"></td>
                    <td><input type="text" name="desc_fi[]"></td>
                    <td><input type="text" name="desc_en[]"></td>
                    <td><input type="number" step="0.01" name="price[]" style="width:70px"></td>
                    <td><input type="text" name="tags[]" style="width:80px"></td>
                    <td><input type="checkbox" name="visible[]" value="1" checked></td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="mt-4">Tallenna</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
