<?php
require_once __DIR__ . '/../includes/functions.php';
adminAuth();

$s = settings();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $s['title_fi'] = $_POST['title_fi'] ?? '';
    $s['title_en'] = $_POST['title_en'] ?? '';
    $s['hero_text_fi'] = $_POST['hero_text_fi'] ?? '';
    $s['hero_text_en'] = $_POST['hero_text_en'] ?? '';
    $s['intro_fi'] = $_POST['intro_fi'] ?? '';
    $s['intro_en'] = $_POST['intro_en'] ?? '';
    $s['phone'] = $_POST['phone'] ?? '';
    $s['email'] = $_POST['email'] ?? '';
    $s['address'] = $_POST['address'] ?? '';
    $s['seo_title_fi'] = $_POST['seo_title_fi'] ?? '';
    $s['seo_title_en'] = $_POST['seo_title_en'] ?? '';
    $s['seo_description_fi'] = $_POST['seo_description_fi'] ?? '';
    $s['seo_description_en'] = $_POST['seo_description_en'] ?? '';

    $s['social_links'] = [];
    $platforms = $_POST['social_platform'] ?? [];
    $urls = $_POST['social_url'] ?? [];
    foreach ($platforms as $i => $p) {
        if (!empty($p) && !empty($urls[$i])) {
            $s['social_links'][] = ['platform' => $p, 'url' => $urls[$i]];
        }
    }

    DataStore::save('settings', $s);
    $saved = true;
}

$title = 'Asetukset';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($saved)): ?><div class="alert">Tallennettu!</div><?php endif; ?>

<div class="card">
    <h2>Yleiset asetukset</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <h3 class="mb-2">Perustiedot</h3>
        <div class="grid-2">
            <div class="form-group"><label>Otsikko (FI)</label><input type="text" name="title_fi" value="<?= esc($s['title_fi'] ?? '') ?>"></div>
            <div class="form-group"><label>Otsikko (EN)</label><input type="text" name="title_en" value="<?= esc($s['title_en'] ?? '') ?>"></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>Hero-teksti (FI)</label><input type="text" name="hero_text_fi" value="<?= esc($s['hero_text_fi'] ?? '') ?>"></div>
            <div class="form-group"><label>Hero-teksti (EN)</label><input type="text" name="hero_text_en" value="<?= esc($s['hero_text_en'] ?? '') ?>"></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>Esittely (FI)</label><textarea name="intro_fi"><?= esc($s['intro_fi'] ?? '') ?></textarea></div>
            <div class="form-group"><label>Esittely (EN)</label><textarea name="intro_en"><?= esc($s['intro_en'] ?? '') ?></textarea></div>
        </div>

        <h3 class="mb-2 mt-4">Yhteystiedot</h3>
        <div class="grid-2">
            <div class="form-group"><label>Puhelin</label><input type="text" name="phone" value="<?= esc($s['phone'] ?? '') ?>"></div>
            <div class="form-group"><label>Sähköposti</label><input type="email" name="email" value="<?= esc($s['email'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label>Osoite</label><textarea name="address"><?= esc($s['address'] ?? '') ?></textarea></div>

        <h3 class="mb-2 mt-4">Sosiaalinen media</h3>
        <div id="social-links">
            <?php foreach ($s['social_links'] ?? [] as $i => $link): ?>
            <div class="grid-2 mb-2">
                <div class="form-group">
                    <select name="social_platform[]">
                        <option value="">Valitse...</option>
                        <option value="instagram" <?= ($link['platform'] ?? '') === 'instagram' ? 'selected' : '' ?>>Instagram</option>
                        <option value="tiktok" <?= ($link['platform'] ?? '') === 'tiktok' ? 'selected' : '' ?>>TikTok</option>
                        <option value="facebook" <?= ($link['platform'] ?? '') === 'facebook' ? 'selected' : '' ?>>Facebook</option>
                        <option value="x" <?= ($link['platform'] ?? '') === 'x' ? 'selected' : '' ?>>X / Twitter</option>
                    </select>
                </div>
                <div class="form-group"><input type="url" name="social_url[]" value="<?= esc($link['url'] ?? '') ?>" placeholder="https://..."></div>
            </div>
            <?php endforeach; ?>
            <div class="grid-2 mb-2">
                <div class="form-group">
                    <select name="social_platform[]">
                        <option value="">Valitse...</option>
                        <option value="instagram">Instagram</option>
                        <option value="tiktok">TikTok</option>
                        <option value="facebook">Facebook</option>
                        <option value="x">X / Twitter</option>
                    </select>
                </div>
                <div class="form-group"><input type="url" name="social_url[]" placeholder="https://..."></div>
            </div>
        </div>

        <h3 class="mb-2 mt-4">SEO</h3>
        <div class="grid-2">
            <div class="form-group"><label>SEO-otsikko (FI)</label><input type="text" name="seo_title_fi" value="<?= esc($s['seo_title_fi'] ?? '') ?>"></div>
            <div class="form-group"><label>SEO-otsikko (EN)</label><input type="text" name="seo_title_en" value="<?= esc($s['seo_title_en'] ?? '') ?>"></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>SEO-kuvaus (FI)</label><textarea name="seo_description_fi"><?= esc($s['seo_description_fi'] ?? '') ?></textarea></div>
            <div class="form-group"><label>SEO-kuvaus (EN)</label><textarea name="seo_description_en"><?= esc($s['seo_description_en'] ?? '') ?></textarea></div>
        </div>

        <button type="submit">Tallenna</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
