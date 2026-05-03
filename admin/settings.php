<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/RevisionLog.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

RevisionLog::init(DATA_DIR);

$s = settings();
$pwSaved = false;
$pwError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $pw1 = $_POST['admin_password'] ?? '';
        $pw2 = $_POST['admin_password_confirm'] ?? '';
        if ($pw1 === '') $pwError = 'Anna salasana.';
        elseif ($pw1 !== $pw2) $pwError = 'Salasanat eivät täsmää.';
        else { adminSetPassword($pw1); $pwSaved = true; }
    } else {
        $before = $s;
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
            if (!empty($p) && !empty($urls[$i])) $s['social_links'][] = ['platform' => $p, 'url' => $urls[$i]];
        }
        DataStore::save('settings', $s);
        RevisionLog::log('settings', 'updated', $s, $before);
        $saved = true;
    }
}

$activeTab = $_GET['tab'] ?? 'identity';

$title = 'Asetukset';
include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($saved)): ?><div class="alert">Tallennettu!</div><?php endif; ?>
<?php if ($pwSaved): ?><div class="alert">Salasana vaihdettu!</div><?php endif; ?>
<?php if ($pwError): ?><div class="alert alert--error"><?= esc($pwError) ?></div><?php endif; ?>

<nav class="tab-bar" id="settings-tabs">
    <a href="?tab=identity" class="tab-bar__item <?= $activeTab === 'identity' ? 'is-active' : '' ?>">Perustiedot</a>
    <a href="?tab=contact" class="tab-bar__item <?= $activeTab === 'contact' ? 'is-active' : '' ?>">Yhteystiedot</a>
    <a href="?tab=social" class="tab-bar__item <?= $activeTab === 'social' ? 'is-active' : '' ?>">Some-linkit</a>
    <a href="?tab=seo" class="tab-bar__item <?= $activeTab === 'seo' ? 'is-active' : '' ?>">SEO</a>
    <a href="?tab=security" class="tab-bar__item <?= $activeTab === 'security' ? 'is-active' : '' ?>">Salasana</a>
</nav>

<form method="post">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">

    <div class="card" id="section-identity" <?= $activeTab !== 'identity' ? 'hidden' : '' ?>>
        <h2>Perustiedot</h2>
        <p class="text-sm text-gray">Päivitä sivuston pääotsikko, hero-tekstit ja esittelysisältö.</p>
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
    </div>

    <div class="card" id="section-contact" <?= $activeTab !== 'contact' ? 'hidden' : '' ?>>
        <h2>Yhteystiedot</h2>
        <p class="text-sm text-gray">Pidä näkyvät yhteystiedot ajantasaisina.</p>
        <div class="grid-2">
            <div class="form-group"><label>Puhelin</label><input type="text" name="phone" value="<?= esc($s['phone'] ?? '') ?>" placeholder="+358 ..."></div>
            <div class="form-group"><label>Sähköposti</label><input type="email" name="email" value="<?= esc($s['email'] ?? '') ?>" placeholder="info@example.com"></div>
        </div>
        <div class="form-group"><label>Osoite</label><textarea name="address"><?= esc($s['address'] ?? '') ?></textarea></div>
    </div>

    <div class="card" id="section-social" <?= $activeTab !== 'social' ? 'hidden' : '' ?>>
        <h2>Sosiaalinen media</h2>
        <p class="text-sm text-gray">Jokainen rivi on yksi julkinen linkki.</p>
        <div id="social-links">
            <?php foreach ($s['social_links'] ?? [] as $i => $link): ?>
            <div class="settings-social-row mb-2">
                <div class="form-group"><label>Alusta</label><select name="social_platform[]"><option value="">Valitse...</option><option value="instagram" <?= ($link['platform'] ?? '') === 'instagram' ? 'selected' : '' ?>>Instagram</option><option value="tiktok" <?= ($link['platform'] ?? '') === 'tiktok' ? 'selected' : '' ?>>TikTok</option><option value="facebook" <?= ($link['platform'] ?? '') === 'facebook' ? 'selected' : '' ?>>Facebook</option><option value="x" <?= ($link['platform'] ?? '') === 'x' ? 'selected' : '' ?>>X / Twitter</option></select></div>
                <div class="form-group"><label>URL</label><input type="url" name="social_url[]" value="<?= esc($link['url'] ?? '') ?>" placeholder="https://..."></div>
                <button type="button" class="admin-inline-button social-remove-btn" title="Poista">&times;</button>
            </div>
            <?php endforeach; ?>
            <div class="settings-social-row mb-2" data-template hidden>
                <div class="form-group"><label>Alusta</label><select name="social_platform[]"><option value="">Valitse...</option><option value="instagram">Instagram</option><option value="tiktok">TikTok</option><option value="facebook">Facebook</option><option value="x">X / Twitter</option></select></div>
                <div class="form-group"><label>URL</label><input type="url" name="social_url[]" placeholder="https://..."></div>
                <button type="button" class="admin-inline-button social-remove-btn" title="Poista">&times;</button>
            </div>
        </div>
        <button type="button" id="add-social-link" class="btn btn--secondary btn--sm mt-2">+ Lisää linkki</button>
    </div>

    <div class="card" id="section-seo" <?= $activeTab !== 'seo' ? 'hidden' : '' ?>>
        <h2>SEO</h2>
        <p class="text-sm text-gray">Kirjoita hakukoneille tiiviit otsikot ja kuvaukset.</p>
        <div class="grid-2">
            <div class="form-group"><label>SEO-otsikko (FI)</label><input type="text" name="seo_title_fi" value="<?= esc($s['seo_title_fi'] ?? '') ?>"></div>
            <div class="form-group"><label>SEO-otsikko (EN)</label><input type="text" name="seo_title_en" value="<?= esc($s['seo_title_en'] ?? '') ?>"></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>SEO-kuvaus (FI)</label><textarea name="seo_description_fi"><?= esc($s['seo_description_fi'] ?? '') ?></textarea></div>
            <div class="form-group"><label>SEO-kuvaus (EN)</label><textarea name="seo_description_en"><?= esc($s['seo_description_en'] ?? '') ?></textarea></div>
        </div>
    </div>

    <div class="admin-sticky-save">
        <span class="admin-sticky-save__info">Tallenna asetukset.</span>
        <button type="submit">Tallenna asetukset</button>
    </div>
</form>

<div class="card" id="section-security" <?= $activeTab !== 'security' ? 'hidden' : '' ?>>
    <div class="password-section">
        <h3>Vaihda salasana</h3>
        <p>Aseta uusi admin-salasana.</p>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="change_password">
            <div class="grid-2">
                <div class="form-group"><label>Uusi salasana</label><input type="password" name="admin_password" required></div>
                <div class="form-group"><label>Vahvista salasana</label><input type="password" name="admin_password_confirm" required><div class="password-mismatch" style="display:none"></div></div>
            </div>
            <button type="submit" class="btn btn--primary" style="margin-top:0.5rem">Vaihda salasana</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
