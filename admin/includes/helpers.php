<?php

if (!defined('ADMIN_DIR')) return;

function flagSvg(string $lang): string
{
    if ($lang === 'fi') {
        return '<svg class="lang-flag" viewBox="0 0 1800 1100" xmlns="http://www.w3.org/2000/svg"><rect width="1800" height="1100" fill="#fff"/><rect width="1800" height="300" y="400" fill="#003580"/><rect width="300" height="1100" x="500" fill="#003580"/></svg>';
    }
    return '<svg class="lang-flag" viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg"><clipPath id="s"><path d="M0,0 v30 h60 v-30 z"/></clipPath><clipPath id="t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><g clip-path="url(#s)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></g></svg>';
}

function renderStatusBadge(string $status, string $label = ''): void
{
    $map = [
        'published' => ['class' => 'status-badge--success', 'label' => 'Julkaistu'],
        'draft' => ['class' => 'status-badge--warning', 'label' => 'Luonnos'],
        'hidden' => ['class' => 'status-badge--muted', 'label' => 'Piilotettu'],
        'scheduled' => ['class' => 'status-badge--info', 'label' => 'Ajastettu'],
        'expired' => ['class' => 'status-badge--danger', 'label' => 'Vanhentunut'],
        'missing' => ['class' => 'status-badge--danger', 'label' => 'Puuttuu'],
        'active' => ['class' => 'status-badge--success', 'label' => 'Aktiivinen'],
    ];
    $m = $map[$status] ?? ['class' => 'status-badge--default', 'label' => $status];
    printf('<span class="status-badge %s">%s</span>', $m['class'], esc($label !== '' ? $label : $m['label']));
}

function renderTranslationBadge(string $fi, string $en): void
{
    $hasFi = $fi !== '';
    $hasEn = $en !== '';
    if ($hasFi && $hasEn) {
        echo '<span class="status-badge status-badge--success">FI/EN</span>';
    } elseif ($hasFi) {
        echo '<span class="status-badge status-badge--warning">FI vain</span>';
    } elseif ($hasEn) {
        echo '<span class="status-badge status-badge--warning">EN vain</span>';
    } else {
        echo '<span class="status-badge status-badge--danger">Ei käännöstä</span>';
    }
}

function renderEmptyState(string $icon, string $title, string $text, string $actionUrl = '', string $actionLabel = '', string $toggleTarget = ''): void
{
    ?>
    <div class="empty-state">
        <div class="empty-state__icon"><?= $icon ?></div>
        <h3 class="empty-state__title"><?= esc($title) ?></h3>
        <p class="empty-state__text"><?= esc($text) ?></p>
        <?php if ($actionLabel !== ''): ?>
            <?php if ($toggleTarget !== ''): ?>
            <button type="button" class="btn btn--primary mt-4" data-toggle-details="<?= esc($toggleTarget) ?>"><?= esc($actionLabel) ?></button>
            <?php elseif ($actionUrl !== ''): ?>
            <a href="<?= esc($actionUrl) ?>" class="btn btn--primary mt-4"><?= esc($actionLabel) ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

function renderFlashMessage(string $key, array $messages, string $defaultClass = 'alert'): void
{
    if ($key === '' || !isset($messages[$key])) return;
    $msg = $messages[$key];
    $class = $defaultClass;
    if (str_contains($key, 'error') || str_contains($key, 'missing') || str_contains($key, 'fail')) {
        $class .= ' alert--error';
    }
    printf('<div class="%s">%s</div>', $class, esc($msg));
}

function renderSectionNav(array $sections, string $active): void
{
    ?>
    <nav class="tab-bar">
        <?php foreach ($sections as $key => $label): ?>
        <a href="#section-<?= esc($key) ?>" class="tab-bar__item <?= $key === $active ? 'is-active' : '' ?>" data-tab="<?= esc($key) ?>"><?= esc($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function renderDateBadge(string $dateStr): string
{
    if ($dateStr === '') return '<span class="date-badge date-badge--empty">—</span>';
    $ts = strtotime($dateStr);
    if ($ts === false) return '<span class="date-badge date-badge--empty">—</span>';
    return sprintf('<span class="date-badge"><strong>%s</strong><span>%s</span></span>', date('d.m', $ts), date('Y', $ts));
}

function renderDaySelect(string $name, string $selected, string $attrs = ''): void
{
    $days = ['mon' => 'Maanantai', 'tue' => 'Tiistai', 'wed' => 'Keskiviikko', 'thu' => 'Torstai', 'fri' => 'Perjantai'];
    printf('<select name="%s" %s>', esc($name), $attrs);
    foreach ($days as $k => $v) {
        printf('<option value="%s" %s>%s</option>', $k, $k === $selected ? 'selected' : '', $v);
    }
    echo '</select>';
}

function adminUploadImages(): array
{
    $images = array_values(array_filter(array_map(
        static function (string $path): ?array {
            if (!is_file($path)) {
                return null;
            }

            $filename = safeUploadFilename(basename($path));
            if ($filename === '') {
                return null;
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['avif', 'jpg', 'jpeg', 'png', 'webp'], true)) {
                return null;
            }

            return [
                'filename' => $filename,
                'src' => uploadAsset($filename),
                'mtime' => filemtime($path) ?: 0,
            ];
        },
        glob(ROOT . '/uploads/*') ?: []
    )));

    usort($images, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
    return $images;
}

function adminUploadImageSrc(string $selectedImage, array $uploadImages): string
{
    $selectedImage = safeUploadFilename($selectedImage);
    if ($selectedImage === '') {
        return '';
    }

    foreach ($uploadImages as $image) {
        if (($image['filename'] ?? '') === $selectedImage) {
            return (string) ($image['src'] ?? '');
        }
    }

    return '';
}

function renderUploadImagePicker(string $inputName, string $selectedImage, array $uploadImages, string $pickerId): void
{
    $selectedImage = safeUploadFilename($selectedImage);
    $selectedSrc = adminUploadImageSrc($selectedImage, $uploadImages);
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
            <?php $filename = (string) ($image['filename'] ?? ''); $isSelected = $filename === $selectedImage; ?>
            <button type="button" class="menu-image-option<?= $isSelected ? ' is-selected' : '' ?>" data-image-value="<?= esc($filename) ?>" data-image-src="<?= esc((string) ($image['src'] ?? '')) ?>" aria-pressed="<?= $isSelected ? 'true' : 'false' ?>">
                <span class="menu-image-option__thumb"><img src="<?= esc((string) ($image['src'] ?? '')) ?>" alt="" loading="lazy"></span>
                <span class="menu-image-option__name"><?= esc($filename) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
