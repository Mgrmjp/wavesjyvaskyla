<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
adminAuth();

$uploadDir = ROOT . '/uploads';
$legacyImages = [];
$avifImages = [];
$stats = ['processed' => 0, 'skipped' => 0, 'errors' => 0];

if (is_dir($uploadDir)) {
    foreach (scandir($uploadDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = $uploadDir . '/' . $f;
        if (!is_file($path)) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if ($ext === 'avif') {
            $avifImages[] = $f;
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $legacyImages[] = ['filename' => $f, 'ext' => $ext, 'size' => filesize($path), 'path' => $path];
        }
    }
}

usort($legacyImages, fn($a, $b) => $b['size'] <=> $a['size']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'optimize_all' || $action === 'optimize_selected') {
        $selected = $action === 'optimize_all'
            ? $legacyImages
            : array_filter($legacyImages, fn($img) => in_array($img['filename'], $_POST['files'] ?? []));

        foreach ($selected as $img) {
            $avifFilename = pathinfo($img['filename'], PATHINFO_FILENAME) . '.avif';
            $avifDest = $uploadDir . '/' . $avifFilename;

            if (file_exists($avifDest)) {
                $stats['skipped']++;
                continue;
            }

            if (optimizeImage($img['path'], $avifDest)) {
                $menuData = DataStore::load('menu');
                $menuChanged = false;
                foreach ($menuData['items'] ?? [] as &$item) {
                    if (($item['image'] ?? '') === $img['filename']) {
                        $item['image'] = $avifFilename;
                        $menuChanged = true;
                    }
                }
                unset($item);
                if ($menuChanged) DataStore::save('menu', $menuData);

                $galleryData = DataStore::load('gallery');
                $galleryChanged = false;
                foreach ($galleryData ?? [] as &$gImg) {
                    if (($gImg['filename'] ?? '') === $img['filename']) {
                        $gImg['filename'] = $avifFilename;
                        $galleryChanged = true;
                    }
                }
                unset($gImg);
                if ($galleryChanged) DataStore::save('gallery', $galleryData);

                @unlink($img['path']);
                $stats['processed']++;
            } else {
                $stats['errors']++;
            }
        }
    }
}

$title = 'Kuvaoptimointi';
include __DIR__ . '/includes/header.php';
?>

<?php if ($stats['processed'] > 0 || $stats['skipped'] > 0 || $stats['errors'] > 0): ?>
<div class="alert alert--success">
    Käsitelty: <?= $stats['processed'] ?> · Ohitettu: <?= $stats['skipped'] ?> · Virheet: <?= $stats['errors'] ?>
</div>
<?php endif; ?>

<div class="card">
    <h2>Optimoi olemassa olevat kuvat</h2>
    <p class="text-muted">Muuntaa jpg/png/webp-kuvat AVIF-muotoon (max 2560px, laatu 80). Päivittää viitteet menu- ja galleriadatassa. Poistaa alkuperäiset tiedostot onnistuneen muunnoksen jälkeen.</p>

    <?php if (empty($legacyImages)): ?>
        <p class="text-muted" style="margin-top:1rem">Ei optimoitavia kuvia. Kaikki kuvat ovat jo AVIF-muodossa.</p>
    <?php else: ?>
        <form method="post" style="margin-top:1.5rem">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <div style="display:flex;gap:0.75rem;margin-bottom:1rem">
                <button type="submit" name="action" value="optimize_all" class="btn btn--primary">Optimoi kaikki (<?= count($legacyImages) ?>)</button>
                <button type="submit" name="action" value="optimize_selected" class="btn btn--secondary">Optimoi valitut</button>
            </div>
            <div style="max-height:400px;overflow-y:auto;border:1px solid var(--admin-border);border-radius:6px">
                <table class="admin-table" style="margin:0">
                    <thead>
                        <tr>
                            <th style="width:40px"><input type="checkbox" id="select-all"></th>
                            <th>Tiedosto</th>
                            <th style="width:100px">Koko</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($legacyImages as $img): ?>
                        <tr>
                            <td><input type="checkbox" name="files[]" value="<?= esc($img['filename']) ?>" class="file-check"></td>
                            <td><code><?= esc($img['filename']) ?></code></td>
                            <td><?= number_format($img['size'] / 1024, 1) ?> kB</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:1.5rem">
    <h2>AVIF-kuvat (<?= count($avifImages) ?>)</h2>
    <?php if (empty($avifImages)): ?>
        <p class="text-muted">Ei AVIF-kuvia vielä.</p>
    <?php else: ?>
        <div style="max-height:300px;overflow-y:auto">
            <table class="admin-table" style="margin:0">
                <tbody>
                    <?php foreach ($avifImages as $f): ?>
                    <tr><td><code><?= esc($f) ?></code></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.file-check').forEach(cb => cb.checked = this.checked);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
