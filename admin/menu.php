<?php
require_once __DIR__ . '/../includes/functions.php';
adminAuth();

$data = DataStore::ensure('menu', ['categories' => defaultMenuCategories(), 'items' => defaultMenuItems()]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['upload'])) {
    checkCsrf();

    $data['categories'] = [];
    $cat_slugs = $_POST['cat_slug'] ?? [];
    foreach ($cat_slugs as $i => $slug) {
        if (empty($slug)) continue;
        $data['categories'][] = [
            'id' => generateId(),
            'title_fi' => $_POST['cat_title_fi'][$i] ?? '',
            'title_en' => $_POST['cat_title_en'][$i] ?? '',
            'slug' => $slug,
            'sort_order' => (int)($_POST['cat_sort'][$i] ?? 0),
        ];
    }

    $data['items'] = [];
    $item_names = $_POST['item_name_fi'] ?? [];
    foreach ($item_names as $i => $name_fi) {
        if (empty($name_fi)) continue;
        $data['items'][] = [
            'id' => generateId(),
            'name_fi' => $name_fi,
            'name_en' => $_POST['item_name_en'][$i] ?? '',
            'description_fi' => $_POST['item_desc_fi'][$i] ?? '',
            'description_en' => $_POST['item_desc_en'][$i] ?? '',
            'price' => (float)($_POST['item_price'][$i] ?? 0),
            'category' => $_POST['item_category'][$i] ?? '',
            'dietary_tags' => $_POST['item_tags'][$i] ?? '',
            'visible' => !empty($_POST['item_visible'][$i]),
            'image' => $_POST['item_image'][$i] ?? '',
        ];
    }

    DataStore::save('menu', $data);
    $saved = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['upload'])) {
    checkCsrf();
    if (!empty($_FILES['menu_image']['tmp_name'])) {
        $file = $_FILES['menu_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) {
            $msg = 'Ei tuettu tiedostomuoto. Sallitut: jpg, png, webp';
            $msgType = 'err';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $msg = 'Tiedosto on liian suuri (max 10 MB)';
            $msgType = 'err';
        } else {
            $filename = 'menu_' . generateId() . '.' . $ext;
            $dest = ROOT . '/uploads/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $msg = 'Kuva ladattu: ' . $filename . ' (kopioi kenttään)';
                $msgType = 'ok';
            } else {
                $msg = 'Tallennus epäonnistui.';
                $msgType = 'err';
            }
        }
    }
}

function defaultMenuCategories(): array {
    return [
        ['id'=>'c1','title_fi'=>'Summer Tacos','title_en'=>'Summer Tacos','slug'=>'summer-tacos','sort_order'=>1],
        ['id'=>'c2','title_fi'=>'Burgers','title_en'=>'Burgers','slug'=>'burgers','sort_order'=>2],
        ['id'=>'c3','title_fi'=>'Saldet','title_en'=>'Salads','slug'=>'saldet','sort_order'=>3],
        ['id'=>'c4','title_fi'=>'Kids','title_en'=>'Kids','slug'=>'kids','sort_order'=>4],
        ['id'=>'c5','title_fi'=>'Ranut','title_en'=>'Fries','slug'=>'ranut','sort_order'=>5],
        ['id'=>'c6','title_fi'=>'Dipit','title_en'=>'Dips','slug'=>'dipit','sort_order'=>6],
    ];
}

function defaultMenuItems(): array {
    return [
        ['id'=>'m1','name_fi'=>'KUHATAKU','name_en'=>'KUHATAKU','description_fi'=>'Rapea kuha ja lime-korianterimajo','description_en'=>'Crispy zander and lime-cilantro mayo','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m2','name_fi'=>'KANA','name_en'=>'CHICKEN','description_fi'=>'Rapea kana ja paprikamajo','description_en'=>'Crispy chicken and pepper mayo','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m3','name_fi'=>'HALLOUMI','name_en'=>'HALLOUMI','description_fi'=>'Rapea halloumi ja sweet & chili -majo','description_en'=>'Crispy halloumi and sweet & chili mayo','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m4','name_fi'=>'TOFU','name_en'=>'TOFU','description_fi'=>'Spicy garlic tofu ja sweet & chili -majo','description_en'=>'Spicy garlic tofu and sweet & chili mayo','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'V','visible'=>true],
        ['id'=>'m5','name_fi'=>'PORK O\'CLOCK','name_en'=>'PORK O\'CLOCK','description_fi'=>'Possun kylkeä, BBQ-kastike ja aioli','description_en'=>'Pork belly, BBQ sauce and aioli','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m6','name_fi'=>'SMASH Single','name_en'=>'SMASH Single','description_fi'=>'80g rapea pihvi, salde, pikkelisipuli, myrttinen, Juukolan cheddar ja paprikamajo','description_en'=>'80g crispy patty, salad, pickled onion, myrtle, Juukola cheddar and pepper mayo','price'=>14,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m7','name_fi'=>'SMASH Double','name_en'=>'SMASH Double','description_fi'=>'2 x 80g rapea pihvi, salde, pikkelisipuli, myrttinen, Juukolan cheddar ja paprikamajo','description_en'=>'2 x 80g crispy patty, salad, pickled onion, myrtle, Juukola cheddar and pepper mayo','price'=>18.50,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m8','name_fi'=>'Hangover','name_en'=>'Hangover','description_fi'=>'2 pihviä + pekoni, pikkelijalaopeno ja auramajo','description_en'=>'2 patties + bacon, pickled jalapeño and blue cheese mayo','price'=>20,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m9','name_fi'=>'VEGGIE CLASH','name_en'=>'VEGGIE CLASH','description_fi'=>'Beyond meat -pihvi, Juukolan cheddar ja sweet & chili -majo','description_en'=>'Beyond meat patty, Juukola cheddar and sweet & chili mayo','price'=>18.50,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m10','name_fi'=>'FISHERMAN','name_en'=>'FISHERMAN','description_fi'=>'Sandwich kuhafile, pikkelikaali ja sipuli, salde, myrttinen ja lime-korianterimajo','description_en'=>'Sandwich zander fillet, pickled cabbage and onion, salad, myrtle and lime-cilantro mayo','price'=>18.50,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m11','name_fi'=>'KANA-VUOHENJUUSTO','name_en'=>'CHICKEN-GOAT CHEESE','description_fi'=>'Lemon vinaigrette ja jalapenohillo','description_en'=>'Lemon vinaigrette and jalapeño jam','price'=>18,'category'=>'saldet','dietary_tags'=>'VL,G','visible'=>true],
        ['id'=>'m12','name_fi'=>'SPICY GARLIC TOFU','name_en'=>'SPICY GARLIC TOFU','description_fi'=>'Lemon vinaigrette, balsamico ja paahdettu saksanpähkinä','description_en'=>'Lemon vinaigrette, balsamic and roasted walnut','price'=>18,'category'=>'saldet','dietary_tags'=>'V,G','visible'=>true],
        ['id'=>'m13','name_fi'=>'SMASH Kids','name_en'=>'SMASH Kids','description_fi'=>'1 rapea 80g pihvi, Juukolan cheddar, paprikamajo, ketsuppi ja salde. Ranut.','description_en'=>'1 crispy 80g patty, Juukola cheddar, pepper mayo, ketchup and salad. Fries.','price'=>10,'category'=>'kids','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m14','name_fi'=>'RANUT + DIPPI','name_en'=>'FRIES + DIP','description_fi'=>'','description_en'=>'','price'=>6,'category'=>'kids','dietary_tags'=>'G,L,V','visible'=>true],
        ['id'=>'m15','name_fi'=>'PARMESAANIRANUT','name_en'=>'PARMESAN FRIES','description_fi'=>'Sisältää dipin','description_en'=>'Includes dip','price'=>8,'category'=>'ranut','dietary_tags'=>'','visible'=>true],
        ['id'=>'m16','name_fi'=>'TUUNATUT RANUT','name_en'=>'LOADED FRIES','description_fi'=>'Aurajuusto, pikkelijalaopeno ja sipuli, paprikamajo sekä kuivattu sipuli','description_en'=>'Blue cheese, pickled jalapeño and onion, pepper mayo and dried onion','price'=>10,'category'=>'ranut','dietary_tags'=>'G,L','visible'=>true],
        ['id'=>'m17','name_fi'=>'DIPIT','name_en'=>'DIPS','description_fi'=>'Korianteri-limemajo, Sweet & chili -majo, Paprikamajo, Auramajo, BBQ-sauce, Aioli','description_en'=>'Cilantro-lime mayo, Sweet & chili mayo, Pepper mayo, Blue cheese mayo, BBQ sauce, Aioli','price'=>2,'category'=>'dipit','dietary_tags'=>'','visible'=>true],
    ];
}

$title = 'Menu';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($saved)): ?><div class="alert">Tallennettu!</div><?php endif; ?>

<div class="card">
    <h2>Lataa kuva menuun</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="upload" value="1">
        <div style="display:flex;align-items:end;gap:1rem;">
            <div class="form-group" style="margin-bottom:0;flex:1;">
                <label>Kuva (jpg, png, webp, max 10 MB)</label>
                <input type="file" name="menu_image" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit">Lähetä</button>
        </div>
        <?php if (!empty($msg)): ?>
        <p style="margin-top:0.5rem;font-size:0.8125rem;font-weight:600;color:<?= $msgType === 'ok' ? '#16a34a' : '#991b1b' ?>"><?= esc($msg) ?></p>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h2>Kategoriat</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <table>
            <thead><tr><th>Nimi FI</th><th>Nimi EN</th><th>Slug</th><th>Järjestys</th></tr></thead>
            <tbody>
                <?php foreach ($data['categories'] as $i => $cat): ?>
                <tr>
                    <td><input type="text" name="cat_title_fi[]" value="<?= esc($cat['title_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="cat_title_en[]" value="<?= esc($cat['title_en'] ?? '') ?>"></td>
                    <td><input type="text" name="cat_slug[]" value="<?= esc($cat['slug'] ?? '') ?>"></td>
                    <td><input type="number" name="cat_sort[]" value="<?= esc($cat['sort_order'] ?? 0) ?>" style="width:60px"></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" name="cat_title_fi[]" placeholder="Uusi kategoria..."></td>
                    <td><input type="text" name="cat_title_en[]"></td>
                    <td><input type="text" name="cat_slug[]"></td>
                    <td><input type="number" name="cat_sort[]" value="0" style="width:60px"></td>
                </tr>
            </tbody>
        </table>

        <h3 class="mt-4 mb-2">Ruokalista</h3>
        <table>
            <thead><tr><th>Nimi FI</th><th>Nimi EN</th><th>Kuvaus FI</th><th>Kuvaus EN</th><th>Hinta</th><th>Kategoria</th><th>Tagit</th><th>Kuva</th><th>Näkyvä</th></tr></thead>
            <tbody>
                <?php foreach ($data['items'] as $i => $item): ?>
                <tr>
                    <td><input type="text" name="item_name_fi[]" value="<?= esc($item['name_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="item_name_en[]" value="<?= esc($item['name_en'] ?? '') ?>"></td>
                    <td><input type="text" name="item_desc_fi[]" value="<?= esc($item['description_fi'] ?? '') ?>"></td>
                    <td><input type="text" name="item_desc_en[]" value="<?= esc($item['description_en'] ?? '') ?>"></td>
                    <td><input type="number" step="0.01" name="item_price[]" value="<?= esc($item['price'] ?? '') ?>" style="width:70px"></td>
                    <td>
                        <select name="item_category[]">
                            <?php foreach ($data['categories'] as $cat): ?>
                            <option value="<?= esc($cat['slug']) ?>" <?= ($item['category'] ?? '') === $cat['slug'] ? 'selected' : '' ?>><?= esc($cat['title_fi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="item_tags[]" value="<?= esc($item['dietary_tags'] ?? '') ?>" style="width:60px"></td>
                    <td><input type="text" name="item_image[]" value="<?= esc($item['image'] ?? '') ?>" placeholder="menu_xxx.jpg" style="width:110px;font-size:0.75rem;"></td>
                    <td><input type="checkbox" name="item_visible[]" value="1" <?= ($item['visible'] ?? false) ? 'checked' : '' ?>></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" name="item_name_fi[]" placeholder="Uusi..."></td>
                    <td><input type="text" name="item_name_en[]"></td>
                    <td><input type="text" name="item_desc_fi[]"></td>
                    <td><input type="text" name="item_desc_en[]"></td>
                    <td><input type="number" step="0.01" name="item_price[]" style="width:70px"></td>
                    <td>
                        <select name="item_category[]">
                            <?php foreach ($data['categories'] as $cat): ?>
                            <option value="<?= esc($cat['slug']) ?>"><?= esc($cat['title_fi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="item_tags[]" style="width:60px"></td>
                    <td><input type="text" name="item_image[]" placeholder="menu_xxx.jpg" style="width:110px;font-size:0.75rem;"></td>
                    <td><input type="checkbox" name="item_visible[]" value="1" checked></td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="mt-4">Tallenna</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
