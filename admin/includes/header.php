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
        ['href' => '/admin/revisions.php', 'label' => 'Revisiot'],
        ['href' => '/admin/users.php', 'label' => 'Käyttäjät'],
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
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="stylesheet" href="/admin/assets/admin.css?v=9">
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
            <a href="/admin/logout.php" title="Kirjaudu ulos" aria-label="Kirjaudu ulos">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 2.5V1.5C10 1.22386 9.77614 1 9.5 1H2.5C2.22386 1 2 1.22386 2 1.5V14.5C2 14.7761 2.22386 15 2.5 15H9.5C9.77614 15 10 14.7761 10 14.5V13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M6 8H14M14 8L11 5M14 8L11 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
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
                <a href="/" class="admin-topbar__pill" target="_blank">Sivusto</a>
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
