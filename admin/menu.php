<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/RevisionLog.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

RevisionLog::init(DATA_DIR);

$data = DataStore::ensure('menu', ['categories' => defaultMenuCategories(), 'items' => defaultMenuItems()]);
$uploadImages = array_values(array_filter(array_map(
    static function (string $path): ?array {
        if (!is_file($path)) return null;
        $filename = basename($path);
        return [
            'filename' => $filename,
            'src' => '/uploads/' . rawurlencode($filename),
            'mtime' => filemtime($path) ?: 0,
        ];
    },
    glob(ROOT . '/uploads/*') ?: []
)));

usort($uploadImages, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

function findMenuImageSrc(string $selectedImage, array $uploadImages): string {
    if ($selectedImage === '') return '';
    foreach ($uploadImages as $image) {
        if (($image['filename'] ?? '') === $selectedImage) return (string) ($image['src'] ?? '');
    }
    return '';
}

function findMenuCategoryTitle(string $slug, array $categories): string {
    foreach ($categories as $category) {
        if (($category['slug'] ?? '') === $slug) return (string) ($category['title_fi'] ?? '');
    }
    return '';
}

function renderMenuImagePicker(string $inputName, string $selectedImage, array $uploadImages, string $pickerId): void {
    $selectedImage = trim($selectedImage);
    $selectedSrc = findMenuImageSrc($selectedImage, $uploadImages);
    ?>
    <div class="menu-image-picker" id="<?= esc($pickerId) ?>">
        <input type="hidden" name="<?= esc($inputName) ?>" value="<?= esc($selectedImage) ?>" data-picker-value>
        <div class="menu-image-picker__summary">
            <div class="menu-image-picker__preview" data-picker-preview>
                <?php if ($selectedSrc !== ''): ?>
                <img src="<?= esc($selectedSrc) ?>" alt="" loading="lazy" data-picker-preview-image>
                <?php else: ?>
                <span data-picker-placeholder><?= $selectedImage !== '' ? 'Kuva puuttuu' : 'Ei kuvaa' ?></span>
                <?php endif; ?>
            </div>
            <div class="menu-image-picker__meta">
                <div class="menu-image-picker__filename" data-picker-filename><?= esc($selectedImage !== '' ? $selectedImage : 'Ei valintaa') ?></div>
                <div class="text-xs text-gray"><?= $selectedImage !== '' ? 'Valitse toinen kuva tai tyhjennä.' : 'Valitse valmis kuva alta.' ?></div>
            </div>
            <button type="button" class="menu-image-picker__clear" data-picker-clear <?= $selectedImage === '' ? 'disabled' : '' ?>>Tyhjennä</button>
        </div>
        <?php if (empty($uploadImages)): ?>
        <div class="menu-image-picker__empty">Ei kuvia vielä. Lataa kuva yllä, niin se ilmestyy tähän.</div>
        <?php else: ?>
        <div class="menu-image-picker__grid" role="listbox" aria-label="Valitse kuva">
            <?php foreach ($uploadImages as $image): ?>
            <?php $filename = $image['filename'] ?? ''; $isSelected = $filename === $selectedImage; ?>
            <button type="button" class="menu-image-option<?= $isSelected ? ' is-selected' : '' ?>" data-image-value="<?= esc($filename) ?>" data-image-src="<?= esc($image['src'] ?? '') ?>" aria-pressed="<?= $isSelected ? 'true' : 'false' ?>">
                <span class="menu-image-option__thumb"><img src="<?= esc($image['src'] ?? '') ?>" alt="" loading="lazy"></span>
                <span class="menu-image-option__name"><?= esc($filename) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

function renderMenuItemFields(array $item, string $key, array $categories, array $uploadImages): void {
    ?>
    <div class="editor-item-panels">
        <input type="hidden" name="item_id[<?= esc($key) ?>]" value="<?= esc((string) ($item['id'] ?? '')) ?>">
        <section class="editor-item-panel">
            <div class="editor-item-panel__head"><h5>Perustiedot</h5></div>
            <div class="editor-item-panel__body">
            <div class="editor-item-grid">
                <div class="form-group form-group--fi span-3"><label><?= flagSvg('fi') ?> Nimi</label><input type="text" name="item_name_fi[<?= esc($key) ?>]" value="<?= esc((string) ($item['name_fi'] ?? '')) ?>" placeholder="Esim. Smash Burger"></div>
                <div class="form-group form-group--en span-3"><label><?= flagSvg('gb') ?> Name</label><input type="text" name="item_name_en[<?= esc($key) ?>]" value="<?= esc((string) ($item['name_en'] ?? '')) ?>" placeholder="English name"></div>
                <div class="form-group span-2"><label>Hinta</label><input class="input-compact" type="number" step="0.01" name="item_price[<?= esc($key) ?>]" value="<?= esc((string) ($item['price'] ?? '')) ?>" placeholder="18.50"></div>
                <div class="form-group span-2"><label>Kategoria</label><select class="input-compact" name="item_category[<?= esc($key) ?>]"><?php foreach ($categories as $cat): ?><option value="<?= esc((string) ($cat['slug'] ?? '')) ?>" <?= ($item['category'] ?? '') === ($cat['slug'] ?? '') ? 'selected' : '' ?>><?= esc((string) ($cat['title_fi'] ?? '')) ?></option><?php endforeach; ?></select></div>
                <div class="form-group span-2"><label>Tagit</label><input class="input-compact" type="text" name="item_tags[<?= esc($key) ?>]" value="<?= esc((string) ($item['dietary_tags'] ?? '')) ?>" placeholder="L, G, V"></div>
            </div>
            </div>
        </section>
        <section class="editor-item-panel">
            <div class="editor-item-panel__head"><h5>Kuvaukset</h5></div>
            <div class="editor-item-panel__body">
            <div class="editor-item-grid">
                <div class="form-group form-group--fi span-6"><label><?= flagSvg('fi') ?> Kuvaus</label><textarea name="item_desc_fi[<?= esc($key) ?>]" placeholder="Lyhyt kuvaus suomeksi"><?= esc((string) ($item['description_fi'] ?? '')) ?></textarea></div>
                <div class="form-group form-group--en span-6"><label><?= flagSvg('gb') ?> Description</label><textarea name="item_desc_en[<?= esc($key) ?>]" placeholder="Short English description"><?= esc((string) ($item['description_en'] ?? '')) ?></textarea></div>
            </div>
            </div>
        </section>
        <section class="editor-item-panel editor-item-panel--media">
            <div class="editor-item-panel__head"><h5>Annoskuva</h5></div>
            <?php renderMenuImagePicker('item_image[' . $key . ']', (string) ($item['image'] ?? ''), $uploadImages, 'item-image-' . $key); ?>
        </section>
    </div>
    <?php
}

function renderMenuItemListEntry(array $item, string $key, array $categories, array $uploadImages): void {
    $title = trim((string) ($item['name_fi'] ?? ''));
    if ($title === '') $title = 'Nimeton annos';
    $categoryTitle = findMenuCategoryTitle((string) ($item['category'] ?? ''), $categories);
    $imageSrc = findMenuImageSrc((string) ($item['image'] ?? ''), $uploadImages);
    $tags = trim((string) ($item['dietary_tags'] ?? ''));
    $priceLabel = !empty($item['price']) ? number_format((float) $item['price'], 2, ',', '') . ' €' : 'Ei hintaa';
    $hasEn = !empty($item['name_en']);
    $updatedAt = $item['updated_at'] ?? '';
    ?>
    <article class="editor-list-item" data-sort-item>
        <div class="editor-list-item__row">
            <span class="editor-drag-handle" title="Vedä siirtääksesi">⋮⋮</span>
            <div class="editor-list-item__body">
                <div class="editor-list-item__media" data-summary-media>
                    <?php if ($imageSrc !== ''): ?><img src="<?= esc($imageSrc) ?>" alt="" loading="lazy"><?php else: ?><span><?= esc(function_exists('mb_substr') ? mb_substr($title, 0, 1) : substr($title, 0, 1)) ?></span><?php endif; ?>
                </div>
                <div class="editor-list-item__content">
                    <h4><?= esc($title) ?></h4>
                    <div class="editor-list-item__meta">
                        <?php renderTranslationBadge($item['name_fi'] ?? '', $item['name_en'] ?? ''); ?>
                        <span class="editor-chip" data-summary-category><?= esc($categoryTitle !== '' ? $categoryTitle : 'Ei kategoriaa') ?></span>
                        <?= !empty($item['visible']) ? renderStatusBadge('published', 'Näkyvissä') : renderStatusBadge('hidden', 'Piilotettu') ?>
                        <?php if (empty($item['image'])): ?><?php renderStatusBadge('missing', 'Ei kuvaa'); ?><?php endif; ?>
                        <?php if (empty($item['price'])): ?><?php renderStatusBadge('missing', 'Ei hintaa'); ?><?php endif; ?>
                        <?php if ($updatedAt !== ''): ?><span class="editor-chip editor-chip--soft"><?= esc(date('d.m.Y', strtotime($updatedAt))) ?></span><?php endif; ?>
                    </div>
                    <div class="editor-list-item__facts">
                        <div class="editor-fact"><span class="editor-fact__label">Hinta</span><strong data-summary-price><?= esc($priceLabel) ?></strong></div>
                        <div class="editor-fact"><span class="editor-fact__label">Tagit</span><strong data-summary-tags><?= esc($tags !== '' ? $tags : 'Ei tageja') ?></strong></div>
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
                <?php renderMenuItemFields($item, $key, $categories, $uploadImages); ?>
                <div class="editor-item-card__footer">
                    <label class="editor-visibility-toggle"><input type="checkbox" name="item_visible[<?= esc($key) ?>]" value="1" <?= !empty($item['visible']) ? 'checked' : '' ?> data-summary-visible><span>Näkyy listalla</span></label>
                </div>
            </div>
        </div>
    </article>
    <?php
}

$menuItemCount = count($data['items'] ?? []);
$visibleItemCount = count(array_filter($data['items'] ?? [], static fn(array $item): bool => !empty($item['visible'])));
$hiddenItemCount = $menuItemCount - $visibleItemCount;
$categoryCount = count($data['categories'] ?? []);
$itemsWithoutEn = count(array_filter($data['items'] ?? [], fn($i) => empty($i['name_en'])));
$itemsWithoutImage = count(array_filter($data['items'] ?? [], fn($i) => empty($i['image'])));
$flash = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        if (empty($_FILES['menu_image'])) { header('Location: /admin/menu.php?status=upload-missing#menu-upload'); exit; }
        $file = $_FILES['menu_image'];
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_OK);
        if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) { header('Location: /admin/menu.php?status=upload-too-large#menu-upload'); exit; }
        if ($uploadError !== UPLOAD_ERR_OK || empty($file['tmp_name'])) { header('Location: /admin/menu.php?status=upload-failed#menu-upload'); exit; }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) { header('Location: /admin/menu.php?status=upload-error#menu-upload'); exit; }
        if ($file['size'] > 10 * 1024 * 1024) { header('Location: /admin/menu.php?status=upload-too-large#menu-upload'); exit; }
        $filename = 'menu_' . generateId() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], ROOT . '/uploads/' . $filename)) {
            header('Location: /admin/menu.php?status=upload-success#menu-upload'); exit;
        }
        header('Location: /admin/menu.php?status=upload-failed#menu-upload'); exit;
    } elseif ($action === 'create_item') {
        $nameFi = trim($_POST['new_name_fi'] ?? '');
        if ($nameFi !== '') {
            $before = $data;
            $newItem = [
                'id' => generateId(), 'name_fi' => $nameFi, 'name_en' => $_POST['new_name_en'] ?? '',
                'description_fi' => $_POST['new_desc_fi'] ?? '', 'description_en' => $_POST['new_desc_en'] ?? '',
                'price' => (float)($_POST['new_price'] ?? 0), 'category' => $_POST['new_category'] ?? '',
                'dietary_tags' => $_POST['new_tags'] ?? '', 'visible' => !empty($_POST['new_visible']),
                'image' => $_POST['new_image'] ?? '', 'updated_at' => date('c'),
            ];
            $data['items'][] = $newItem;
            DataStore::save('menu', $data);
            RevisionLog::log('menu', 'created', $data, $before);
            header('Location: /admin/menu.php?status=item-created#existing-menu-items'); exit;
        }
        header('Location: /admin/menu.php?status=item-missing-title#new-menu-item'); exit;
    } elseif ($action === 'save_existing') {
        $before = $data;
        $data['categories'] = [];
        $catSlugs = $_POST['cat_slug'] ?? [];
        foreach ($catSlugs as $i => $slug) {
            if (empty($slug)) continue;
            $data['categories'][] = ['id' => generateId(), 'title_fi' => $_POST['cat_title_fi'][$i] ?? '', 'title_en' => $_POST['cat_title_en'][$i] ?? '', 'slug' => $slug, 'sort_order' => (int)($_POST['cat_sort'][$i] ?? 0)];
        }
        $data['items'] = [];
        $itemNames = $_POST['item_name_fi'] ?? [];
        foreach ($itemNames as $i => $nameFi) {
            if (trim((string) $nameFi) === '') continue;
            $data['items'][] = [
                'id' => $_POST['item_id'][$i] ?? generateId(), 'name_fi' => $nameFi, 'name_en' => $_POST['item_name_en'][$i] ?? '',
                'description_fi' => $_POST['item_desc_fi'][$i] ?? '', 'description_en' => $_POST['item_desc_en'][$i] ?? '',
                'price' => (float)($_POST['item_price'][$i] ?? 0), 'category' => $_POST['item_category'][$i] ?? '',
                'dietary_tags' => $_POST['item_tags'][$i] ?? '', 'visible' => !empty($_POST['item_visible'][$i]),
                'image' => $_POST['item_image'][$i] ?? '', 'updated_at' => date('c'),
            ];
        }
        DataStore::save('menu', $data);
        RevisionLog::log('menu', 'updated', $data, $before);
        header('Location: /admin/menu.php?status=items-saved#existing-menu-items'); exit;
    }
}

$title = 'Menu';
include __DIR__ . '/includes/header.php';
?>

<?php
$flashMessages = [
    'upload-success' => 'Kuva ladattiin. Se on nyt valittavissa annosten mediavalitsimissa.',
    'upload-error' => 'Ei tuettu tiedostomuoto. Sallitut: jpg, jpeg, png, webp.',
    'upload-too-large' => 'Tiedosto on liian suuri. Maksimikoko on 10 MB.',
    'upload-failed' => 'Kuvan tallennus epäonnistui.',
    'upload-missing' => 'Valitse ladattava kuva ensin.',
    'item-created' => 'Uusi annos lisättiin.',
    'item-missing-title' => 'Anna vähintään suomenkielinen nimi.',
    'items-saved' => 'Muutokset tallennettiin.',
];
if (isset($flashMessages[$flash])): ?><div class="alert"><?= esc($flashMessages[$flash]) ?></div><?php endif; ?>

<div class="editor-list-overview">
    <span class="editor-overview-pill"><strong><?= $menuItemCount ?></strong> annosta</span>
    <span class="editor-overview-pill"><strong><?= $visibleItemCount ?></strong> näkyvissä</span>
    <span class="editor-overview-pill"><strong><?= $hiddenItemCount ?></strong> piilotettu</span>
    <span class="editor-overview-pill"><strong><?= $categoryCount ?></strong> kategoriaa</span>
    <?php if ($itemsWithoutEn > 0): ?><span class="editor-overview-pill" style="border-color:var(--warning-line);background:var(--warning-bg);color:var(--warning)"><strong><?= $itemsWithoutEn ?></strong> ilman EN</span><?php endif; ?>
    <?php if ($itemsWithoutImage > 0): ?><span class="editor-overview-pill" style="border-color:var(--warning-line);background:var(--warning-bg);color:var(--warning)"><strong><?= $itemsWithoutImage ?></strong> ilman kuvaa</span><?php endif; ?>
</div>

<details class="admin-collapsible card" id="new-menu-item" <?= empty($data['items']) ? 'open' : '' ?>>
    <summary><strong>Lisää uusi annos</strong><span class="text-sm text-gray">Täytä perusasiat ensin.</span></summary>
    <div class="admin-collapsible__body">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="action" value="create_item">
        <input type="hidden" name="new_image" value="">
        <input type="hidden" name="new_desc_fi" value="">
        <input type="hidden" name="new_desc_en" value="">
        <div class="editor-create-card">
            <div class="editor-item-panels">
                <section class="editor-item-panel">
                    <div class="editor-item-panel__head"><h5>Perustiedot</h5></div>
                    <div class="editor-item-panel__body">
                    <div class="editor-item-grid">
                        <div class="form-group form-group--fi span-3"><label><?= flagSvg('fi') ?> Nimi</label><input type="text" name="new_name_fi" placeholder="Esim. Smash Burger"></div>
                        <div class="form-group form-group--en span-3"><label><?= flagSvg('gb') ?> Name</label><input type="text" name="new_name_en" placeholder="English name"></div>
                        <div class="form-group span-2"><label>Hinta</label><input class="input-compact" type="number" step="0.01" name="new_price" placeholder="18.50"></div>
                        <div class="form-group span-2"><label>Kategoria</label><select class="input-compact" name="new_category"><?php foreach ($data['categories'] as $category): ?><option value="<?= esc((string) ($category['slug'] ?? '')) ?>"><?= esc((string) ($category['title_fi'] ?? '')) ?></option><?php endforeach; ?></select></div>
                        <div class="form-group span-2"><label>Tagit</label><input class="input-compact" type="text" name="new_tags" placeholder="L, G, V"></div>
                    </div>
                    </div>
                </section>
            </div>
            <div class="editor-item-card__footer"><label class="editor-visibility-toggle"><input type="checkbox" name="new_visible" value="1" checked><span>Luo näkyvänä</span></label></div>
        </div>
        <button type="submit" class="btn btn--primary mt-4">Lisää annos</button>
    </form>
    </div>
</details>

<div class="card" id="existing-menu-items">
    <div class="section-head">
        <div>
            <h2>Annokset</h2>
            <p>Avaa yksi annos kerrallaan. Vedä kahvasta järjestelläksesi.</p>
        </div>
    </div>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="action" value="save_existing">
        <div class="editor-items-stack">
            <?php if (empty($data['items'])): ?>
                <?php renderEmptyState('☰', 'Ei annoksia vielä', 'Aloita luomalla ensimmäinen annos yllä olevasta lomakkeesta.', '', 'Luo annos', 'new-menu-item'); ?>
            <?php else:
                $itemsByCategory = [];
                foreach ($data['items'] as $i => $item) {
                    $cat = (string) ($item['category'] ?? '');
                    $itemsByCategory[$cat][] = ['index' => $i, 'item' => $item];
                }
                foreach ($data['categories'] as $category):
                    $catSlug = (string) ($category['slug'] ?? '');
                    $catItems = $itemsByCategory[$catSlug] ?? [];
                    if (empty($catItems)) continue;
            ?>
                <div class="editor-category-header" data-category-slug="<?= esc($catSlug) ?>"><?= esc((string) ($category['title_fi'] ?? $catSlug)) ?><span class="editor-category-header__count"><?= count($catItems) ?></span></div>
                <?php foreach ($catItems as $entry): ?>
                    <?php renderMenuItemListEntry($entry['item'], (string) $entry['index'], $data['categories'], $uploadImages); ?>
                <?php endforeach; ?>
            <?php endforeach; endif; ?>
        </div>
        <?php if (!empty($data['items'])): ?>
        <div class="admin-sticky-save">
            <span class="admin-sticky-save__info">Tallenna muutokset, kun olet tarkistanut annosten tiedot.</span>
            <button type="submit">Tallenna muutokset</button>
        </div>
        <?php endif; ?>

        <details class="admin-details mt-4">
            <summary>Kategoriat</summary>
            <div class="table-frame">
                <table>
                    <thead><tr><th>Nimi FI</th><th>Nimi EN</th><th>Slug</th><th>Järjestys</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['categories'] as $i => $cat): ?>
                        <tr>
                            <td><input type="text" name="cat_title_fi[]" value="<?= esc($cat['title_fi'] ?? '') ?>"></td>
                            <td><input type="text" name="cat_title_en[]" value="<?= esc($cat['title_en'] ?? '') ?>"></td>
                            <td><input type="text" name="cat_slug[]" value="<?= esc($cat['slug'] ?? '') ?>"></td>
                            <td><input type="number" name="cat_sort[]" value="<?= esc($cat['sort_order'] ?? 0) ?>" style="width:72px"></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="cat_title_fi[]" placeholder="Uusi kategoria..."></td>
                            <td><input type="text" name="cat_title_en[]"></td>
                            <td><input type="text" name="cat_slug[]"></td>
                            <td><input type="number" name="cat_sort[]" value="0" style="width:72px"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </form>
</div>

<div class="card" id="menu-upload">
    <div class="section-head">
        <div>
            <h2>Kuvat</h2>
            <p>Lataa annoskuvat tänne. Sen jälkeen ne näkyvät annoksen kuvavalitsimessa.</p>
        </div>
    </div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
        <div class="admin-dropzone" id="menu-dropzone">
            <input type="file" name="menu_image" id="menu-file" accept="image/jpeg,image/png,image/webp">
            <div class="admin-dropzone__icon">▢</div>
            <p class="admin-dropzone__text">Kuva (jpg, png, webp, max 10 MB)</p>
            <p class="admin-dropzone__hint">Raahaa kuva tähän tai klikkaa valitaksesi</p>
            <div class="admin-dropzone__preview" style="display:none"></div>
        </div>
        <button type="submit" class="btn btn--primary mt-4">Lähetä</button>
    </form>
</div>

<script>
document.querySelectorAll('.menu-image-picker').forEach((picker) => {
    const hiddenInput = picker.querySelector('[data-picker-value]');
    const filename = picker.querySelector('[data-picker-filename]');
    const preview = picker.querySelector('[data-picker-preview]');
    const clearButton = picker.querySelector('[data-picker-clear]');
    const renderState = (value, src) => {
        hiddenInput.value = value; filename.textContent = value || 'Ei valintaa'; preview.innerHTML = '';
        if (src) { const img = document.createElement('img'); img.src = src; img.alt = ''; img.loading = 'lazy'; preview.appendChild(img); }
        else { const p = document.createElement('span'); p.textContent = value ? 'Kuva puuttuu' : 'Ei kuvaa'; preview.appendChild(p); }
        clearButton.disabled = value === '';
        picker.querySelectorAll('.menu-image-option').forEach((o) => { const s = o.dataset.imageValue === value; o.classList.toggle('is-selected', s); o.setAttribute('aria-pressed', s ? 'true' : 'false'); });
    };
    picker.querySelectorAll('.menu-image-option').forEach((o) => { o.addEventListener('click', () => { renderState(o.dataset.imageValue || '', o.dataset.imageSrc || ''); }); });
    clearButton.addEventListener('click', () => { renderState('', ''); });
});
document.querySelectorAll('.editor-list-item').forEach((item) => {
    const titleInput = item.querySelector('input[name^="item_name_fi"]');
    const categorySelect = item.querySelector('select[name^="item_category"]');
    const priceInput = item.querySelector('input[name^="item_price"]');
    const tagsInput = item.querySelector('input[name^="item_tags"]');
    const visibleCheckbox = item.querySelector('[data-summary-visible]');
    const titleTarget = item.querySelector('h4');
    const categoryTarget = item.querySelector('[data-summary-category]');
    const priceTarget = item.querySelector('[data-summary-price]');
    const tagsTarget = item.querySelector('[data-summary-tags]');
    const statusTarget = item.querySelector('[data-summary-status]');
    const summaryMedia = item.querySelector('[data-summary-media]');
    const picker = item.querySelector('.menu-image-picker');
    const renderSummaryMedia = () => {
        if (!summaryMedia || !picker) return;
        const selected = picker.querySelector('.menu-image-option.is-selected img');
        const title = (titleInput?.value || '').trim() || 'N';
        summaryMedia.innerHTML = '';
        if (selected) { const img = document.createElement('img'); img.src = selected.getAttribute('src') || ''; img.alt = ''; img.loading = 'lazy'; summaryMedia.appendChild(img); return; }
        const p = document.createElement('span'); p.textContent = title.slice(0, 1).toUpperCase(); summaryMedia.appendChild(p);
    };
    if (titleInput && titleTarget) titleInput.addEventListener('input', () => { titleTarget.textContent = titleInput.value.trim() || 'Nimeton annos'; renderSummaryMedia(); });
    if (categorySelect && categoryTarget) categorySelect.addEventListener('change', () => { categoryTarget.textContent = categorySelect.options[categorySelect.selectedIndex]?.textContent?.trim() || 'Ei kategoriaa'; });
    if (visibleCheckbox && statusTarget) visibleCheckbox.addEventListener('change', () => { statusTarget.textContent = visibleCheckbox.checked ? 'Näkyvissä' : 'Piilotettu'; });
    if (priceInput && priceTarget) priceInput.addEventListener('input', () => { const v = Number(priceInput.value); priceTarget.textContent = Number.isFinite(v) && priceInput.value !== '' ? v.toFixed(2).replace('.', ',') + ' €' : 'Ei hintaa'; });
    if (tagsInput && tagsTarget) tagsInput.addEventListener('input', () => { tagsTarget.textContent = tagsInput.value.trim() || 'Ei tageja'; });
    if (picker) picker.querySelectorAll('.menu-image-option, [data-picker-clear]').forEach((c) => { c.addEventListener('click', () => { window.requestAnimationFrame(renderSummaryMedia); }); });
    renderSummaryMedia();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
