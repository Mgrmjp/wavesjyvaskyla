<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

$gallery = DataStore::load('gallery');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
        $imgId = $_POST['id'] ?? '';
        foreach ($gallery as &$img) {
            if (($img['id'] ?? '') === $imgId) { $img['visible'] = !($img['visible'] ?? true); break; }
        }
        DataStore::save('gallery', $gallery);
        header('Location: /admin/gallery.php'); exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $imgId = $_POST['id'] ?? '';
        foreach ($gallery as &$img) {
            if (($img['id'] ?? '') === $imgId) {
                $img['caption_fi'] = $_POST['caption_fi'] ?? '';
                $img['caption_en'] = $_POST['caption_en'] ?? '';
                $img['alt_fi'] = $_POST['alt_fi'] ?? '';
                $img['alt_en'] = $_POST['alt_en'] ?? '';
                break;
            }
        }
        DataStore::save('gallery', $gallery);
        header('Location: /admin/gallery.php'); exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $imgId = $_POST['id'] ?? '';
        foreach ($gallery as $i => $img) {
            if (($img['id'] ?? '') === $imgId) {
                $path = ROOT . '/uploads/' . ($img['filename'] ?? '');
                if (file_exists($path)) unlink($path);
                array_splice($gallery, $i, 1);
                break;
            }
        }
        DataStore::save('gallery', $gallery);
        header('Location: /admin/gallery.php'); exit;
    }
    if (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_OK);
        if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) $error = 'Tiedosto on liian suuri (max 10 MB)';
        elseif ($uploadError !== UPLOAD_ERR_OK || empty($file['tmp_name'])) $error = 'Kuvan lataus epäonnistui.';
        else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
            if (!in_array($ext, $allowed)) $error = 'Ei tuettu tiedostomuoto. Sallitut: jpg, png, webp, avif';
            elseif ($file['size'] > 10 * 1024 * 1024) $error = 'Tiedosto on liian suuri (max 10 MB)';
            else {
                $tmpDest = ROOT . '/uploads/tmp_' . generateId() . '.' . $ext;
                $avifFilename = generateId() . '.avif';
                $avifDest = ROOT . '/uploads/' . $avifFilename;
                if (move_uploaded_file($file['tmp_name'], $tmpDest) && optimizeImage($tmpDest, $avifDest)) {
                    @unlink($tmpDest);
                    $gallery[] = [
                        'id' => generateId(), 'filename' => $avifFilename,
                        'caption_fi' => $_POST['caption_fi'] ?? '', 'caption_en' => $_POST['caption_en'] ?? '',
                        'alt_fi' => $_POST['alt_fi'] ?? '', 'alt_en' => $_POST['alt_en'] ?? '',
                        'visible' => true, 'added' => date('Y-m-d'),
                    ];
                    DataStore::save('gallery', $gallery);
                    header('Location: /admin/gallery.php'); exit;
                } else {
                    @unlink($tmpDest);
                    $error = 'Kuvan optimointi epäonnistui.';
                }
            }
        }
    }
}

// Usage tracking: check which menu items use this image
$menuData = DataStore::load('menu');
$menuImages = [];
foreach ($menuData['items'] ?? [] as $item) {
    if (!empty($item['image'])) {
        $menuImages[$item['image']][] = $item['name_fi'] ?? '';
    }
}

$title = 'Kuvagalleria';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= esc($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Lisää kuva</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
        <div class="admin-dropzone" id="gallery-dropzone">
            <input type="file" name="image" id="gallery-file" accept="image/jpeg,image/png,image/webp,image/avif" required>
            <div class="admin-dropzone__icon">▢</div>
            <p class="admin-dropzone__text">Raahaa kuva tähän tai klikkaa valitaksesi</p>
            <p class="admin-dropzone__hint">jpg, png, webp, avif · max 10 MB</p>
            <div class="admin-dropzone__preview" style="display:none"></div>
        </div>
        <div class="grid-2 mt-4">
            <div class="form-group form-group--fi"><label><?= flagSvg('fi') ?> Kuvateksti</label><input type="text" name="caption_fi"></div>
            <div class="form-group form-group--en"><label><?= flagSvg('gb') ?> Caption</label><input type="text" name="caption_en"></div>
            <div class="form-group form-group--fi"><label><?= flagSvg('fi') ?> ALT-teksti</label><input type="text" name="alt_fi" placeholder="Kuvaileva teksti näkövammaisille"></div>
            <div class="form-group form-group--en"><label><?= flagSvg('gb') ?> ALT text</label><input type="text" name="alt_en" placeholder="Descriptive text for accessibility"></div>
        </div>
        <button type="submit" class="btn btn--primary mt-4">Lähetä</button>
    </form>
</div>

<div class="card">
    <div class="section-head">
        <div>
            <h2>Kuvat (<?= count($gallery) ?>)</h2>
        </div>
    </div>
    <?php if (empty($gallery)): ?>
    <?php renderEmptyState('▢', 'Ei kuvia vielä', 'Lisää ensimmäinen kuva yllä olevasta latausalueesta.'); ?>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($gallery as $img): ?>
        <?php
        $src = '/uploads/' . ($img['filename'] ?? '');
        $fileExists = file_exists(ROOT . '/uploads/' . ($img['filename'] ?? ''));
        $usage = $menuImages[$img['filename'] ?? ''] ?? [];
        ?>
        <div class="gallery-card">
            <div class="gallery-card__image">
                <?php if ($fileExists): ?>
                <img src="<?= esc($src) ?>" alt="" loading="lazy">
                <?php else: ?>
                <span class="gallery-card__placeholder">Puuttuu</span>
                <?php endif; ?>
            </div>
            <div class="gallery-card__body">
                <?php if (!empty($img['caption_fi'])): ?><p class="gallery-card__caption"><?= esc($img['caption_fi']) ?></p><?php endif; ?>
                <div class="gallery-card__meta">
                    <span>Lisätty <?= date('d.m.Y', strtotime($img['added'] ?? 'now')) ?></span>
                    <span>&middot;</span>
                    <span class="<?= ($img['visible'] ?? true) ? 'gallery-card__status--visible' : 'gallery-card__status--hidden' ?>"><?= ($img['visible'] ?? true) ? 'Näkyvissä' : 'Piilotettu' ?></span>
                </div>
                <?php if (!empty($usage)): ?>
                <div class="text-xs text-gray mt-2">Käytössä menussa: <?= esc(implode(', ', $usage)) ?></div>
                <?php endif; ?>
                <details class="admin-collapsible" style="margin-top:0.5rem;border:1px solid var(--line);border-radius:8px">
                    <summary style="padding:0.4rem 0.65rem;font-size:0.75rem">Muokkaa</summary>
                    <div style="padding:0.65rem">
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= csrf() ?>">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= esc($img['id'] ?? '') ?>">
                            <div class="form-group form-group--fi" style="margin-bottom:0.5rem"><label style="font-size:0.7rem"><?= flagSvg('fi') ?> Kuvateksti</label><input type="text" name="caption_fi" value="<?= esc($img['caption_fi'] ?? '') ?>"></div>
                            <div class="form-group form-group--en" style="margin-bottom:0.5rem"><label style="font-size:0.7rem"><?= flagSvg('gb') ?> Caption</label><input type="text" name="caption_en" value="<?= esc($img['caption_en'] ?? '') ?>"></div>
                            <div class="form-group form-group--fi" style="margin-bottom:0.5rem"><label style="font-size:0.7rem"><?= flagSvg('fi') ?> ALT-teksti</label><input type="text" name="alt_fi" value="<?= esc($img['alt_fi'] ?? '') ?>"></div>
                            <div class="form-group form-group--en" style="margin-bottom:0.5rem"><label style="font-size:0.7rem"><?= flagSvg('gb') ?> ALT text</label><input type="text" name="alt_en" value="<?= esc($img['alt_en'] ?? '') ?>"></div>
                            <button type="submit" class="btn btn--secondary btn--sm">Tallenna</button>
                        </form>
                    </div>
                </details>
                <div class="gallery-card__actions">
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf" value="<?= csrf() ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= esc($img['id'] ?? '') ?>">
                        <button type="submit" class="gallery-btn"><?= ($img['visible'] ?? true) ? 'Piilota' : 'Näytä' ?></button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Poista tämä kuva? <?= !empty($usage) ? 'Sitä käytetään menussa (' . esc(implode(', ', $usage)) . ').' : '' ?>')">
                        <input type="hidden" name="csrf" value="<?= csrf() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= esc($img['id'] ?? '') ?>">
                        <button type="submit" class="gallery-btn gallery-btn--danger">Poista</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
