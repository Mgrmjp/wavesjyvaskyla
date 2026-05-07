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
