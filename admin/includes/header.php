<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/helpers.php';
adminAuth();
$s = settings();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin/', PHP_URL_PATH) ?: '/admin/';

$navGroups = [
    'Yleiskuva' => [
        ['href' => '/admin/', 'label' => 'Dashboard'],
    ],
    'Sisältö' => [
        ['href' => '/admin/menu.php', 'label' => 'Menu'],
        ['href' => '/admin/lunch.php', 'label' => 'Lounas'],
        ['href' => '/admin/events.php', 'label' => 'Tapahtumat'],
        ['href' => '/admin/notices.php', 'label' => 'Ilmoitukset'],
    ],
    'Operointi' => [
        ['href' => '/admin/hours.php', 'label' => 'Aukioloajat'],
    ],
    'Media' => [
        ['href' => '/admin/gallery.php', 'label' => 'Kuvagalleria'],
    ],
    'Asetukset' => [
        ['href' => '/admin/settings.php', 'label' => 'Asetukset'],
    ],
];

$pageDescriptions = [
    'Dashboard' => 'Yleiskuva sisällöstä ja nopeat reitit tärkeimpiin muokkauksiin.',
    'Asetukset' => 'Sivuston perusviestit, yhteystiedot ja SEO yhdestä näkymästä.',
    'Ilmoitukset' => 'Ajastetut bannerit poikkeuspäiviin, varoituksiin ja nostoihin.',
    'Aukioloajat' => 'Viikko-ohjelma ja poikkeusaukiolot.',
    'Menu' => 'Kategoriat, annokset ja kuvat saman työkalun kautta.',
    'Lounas' => 'Viikkolounaan sisältö päivän mukaan.',
    'Tapahtumat' => 'Tulevat tapahtumat, tekstit ja näkyvyys.',
    'Kuvagalleria' => 'Gallerian kuvat, näkyvyys ja kuvatekstit.',
];

$isNavActive = static function (string $href) use ($requestPath): bool {
    if ($href === '/admin/') {
        return rtrim($requestPath, '/') === '/admin' || $requestPath === '/admin/';
    }
    return $requestPath === $href;
};
?>
<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? 'Admin') ?> — Waves</title>
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <aside class="admin-sidebar" id="admin-sidebar">
        <a class="admin-sidebar__brand" href="/admin/">
            <span class="admin-sidebar__brand-mark">W</span>
            <span class="admin-sidebar__brand-text">
                <strong>Waves</strong>
                <span>Content Studio</span>
            </span>
        </a>
        <nav class="admin-sidebar__nav" aria-label="Admin navigation">
            <?php foreach ($navGroups as $groupLabel => $items): ?>
            <span class="admin-sidebar__group-label"><?= esc($groupLabel) ?></span>
            <?php foreach ($items as $item): ?>
            <a href="<?= esc($item['href']) ?>" class="admin-sidebar__link <?= $isNavActive($item['href']) ? 'is-active' : '' ?>">
                <?= esc($item['label']) ?>
            </a>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar__footer">
            <span><?= esc($s['title_fi'] ?? 'Waves') ?></span>
            <a href="/admin/logout.php">Kirjaudu ulos</a>
        </div>
    </aside>

    <main class="admin-main" id="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__start">
                <button class="admin-topbar__menu-btn" id="menu-toggle" aria-label="Avaa valikko">☰</button>
                <span class="admin-topbar__title"><?= esc($title ?? 'Admin') ?></span>
            </div>
            <div class="admin-topbar__end">
                <span class="admin-topbar__pill"><?= esc($s['title_fi'] ?? 'Waves') ?></span>
                <a href="/" class="admin-topbar__pill" target="_blank">Esikatselu</a>
                <div class="admin-topbar__status">
                    <span class="admin-topbar__status-dot"></span>
                    <span>Live</span>
                </div>
            </div>
        </header>

        <div class="admin-content">
            <section class="page-header">
                <div class="page-header__content">
                    <h1><?= esc($title ?? 'Admin') ?></h1>
                    <p><?= esc($pageDescriptions[$title ?? ''] ?? 'Hallinnoi sivuston sisältöjä, näkyvyyttä ja julkaistavia tietoja.') ?></p>
                </div>
            </section>

<script defer src="/admin/assets/admin.js"></script>
<a href="#top" class="back-to-top" aria-label="Takaisin ylös">↑</a>
<div id="top"></div>
