<?php
require_once __DIR__ . '/../includes/functions.php';
adminAuth();

$gallery = DataStore::load('gallery');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
        $imgId = $_POST['id'] ?? '';
        foreach ($gallery as &$img) {
            if (($img['id'] ?? '') === $imgId) {
                $img['visible'] = !($img['visible'] ?? true);
                break;
            }
        }
        DataStore::save('gallery', $gallery);
        header('Location: /admin/gallery.php');
        exit;
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
        header('Location: /admin/gallery.php');
        exit;
    }

    if (!empty($_FILES['image']['tmp_name'])) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        if (!in_array($ext, $allowed)) {
            $error = 'Ei tuettu tiedostomuoto. Sallitut: jpg, png, webp, avif';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'Tiedosto on liian suuri (max 10 MB)';
        } else {
            $filename = generateId() . '.' . $ext;
            $dest = ROOT . '/uploads/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $gallery[] = [
                    'id'       => generateId(),
                    'filename' => $filename,
                    'caption_fi' => $_POST['caption_fi'] ?? '',
                    'caption_en' => $_POST['caption_en'] ?? '',
                    'visible'  => true,
                    'added'    => date('Y-m-d'),
                ];
                DataStore::save('gallery', $gallery);
                header('Location: /admin/gallery.php');
                exit;
            } else {
                $error = 'Tiedoston tallennus epäonnistui.';
            }
        }
    }
}

$title = 'Kuvagalleria';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($error)): ?><div class="alert" style="background:#fef2f2;border-color:#fecaca;color:#991b1b"><?= esc($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Lisää kuva</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="grid-2">
            <div class="form-group">
                <label>Kuva (jpg, png, webp, max 10 MB)</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" required>
            </div>
            <div class="grid-2">
                <div class="form-group"><label>Kuvateksti (FI)</label><input type="text" name="caption_fi"></div>
                <div class="form-group"><label>Kuvateksti (EN)</label><input type="text" name="caption_en"></div>
            </div>
        </div>
        <button type="submit">Lähetä</button>
    </form>
</div>

<div class="card">
    <h2>Kuvat (<?= count($gallery) ?>)</h2>
    <?php if (empty($gallery)): ?>
        <p class="text-gray">Ei kuvia vielä.</p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
            <?php foreach ($gallery as $img): ?>
            <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;">
                <div style="aspect-ratio:1;overflow:hidden;background:#e5e7eb;">
                    <?php
                    $src = '/uploads/' . ($img['filename'] ?? '');
                    if (file_exists(ROOT . '/uploads/' . ($img['filename'] ?? ''))):
                    ?>
                    <img src="<?= esc($src) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#9ca3af;font-size:0.75rem;">Puuttuu</div>
                    <?php endif; ?>
                </div>
                <div style="padding:0.5rem;">
                    <?php if (!empty($img['caption_fi'])): ?>
                        <p class="text-sm mb-1" style="margin:0;"><?= esc($img['caption_fi']) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray">
                        Lisätty <?= date('d.m.Y', strtotime($img['added'] ?? 'now')) ?>
                        &nbsp;·&nbsp;
                        <span style="color:<?= ($img['visible'] ?? true) ? '#16a34a' : '#dc2626' ?>">
                            <?= ($img['visible'] ?? true) ? 'Näkyvissä' : 'Piilotettu' ?>
                        </span>
                    </p>
                    <div class="flex gap-2" style="margin-top:0.5rem;">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?= csrf() ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= esc($img['id'] ?? '') ?>">
                            <button type="submit" style="background:#f1f5f9;border:1px solid #d1d5db;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.75rem;cursor:pointer;">
                                <?= ($img['visible'] ?? true) ? 'Piilota' : 'Näytä' ?>
                            </button>
                        </form>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Poista kuva?')">
                            <input type="hidden" name="csrf" value="<?= csrf() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= esc($img['id'] ?? '') ?>">
                            <button type="submit" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.75rem;cursor:pointer;">Poista</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
