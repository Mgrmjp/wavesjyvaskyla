<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

$menuData = DataStore::load('menu');
$events = DataStore::load('events');
$notices = DataStore::load('notices');
$gallery = DataStore::load('gallery');
$lunch = DataStore::load('lunch');
$settings = settings();

$menuItems = $menuData['items'] ?? [];
$menuVisible = count(array_filter($menuItems, fn($i) => !empty($i['visible'])));
$menuHidden = count($menuItems) - $menuVisible;
$menuWithoutEn = count(array_filter($menuItems, fn($i) => empty($i['name_en'])));
$menuWithoutImage = count(array_filter($menuItems, fn($i) => empty($i['image'])));

$eventsList = $events['events'] ?? [];
$eventCount = count($eventsList);
$eventsToday = count(array_filter($eventsList, fn($e) => ($e['date'] ?? '') === date('Y-m-d')));
$eventsUpcoming = count(array_filter($eventsList, fn($e) => ($e['date'] ?? '') >= date('Y-m-d') && !empty($e['visible'])));

$noticesList = $notices['notices'] ?? [];
$today = date('Y-m-d');
$activeNotices = count(array_filter($noticesList, function ($n) use ($today) {
    if (empty($n['active'])) return false;
    $start = $n['start_date'] ?? '';
    $end = $n['end_date'] ?? '';
    if ($start && $today < $start) return false;
    if ($end && $today > $end) return false;
    return true;
}));
$expiredNotices = count(array_filter($noticesList, function ($n) use ($today) {
    $end = $n['end_date'] ?? '';
    return $end !== '' && $today > $end;
}));

$galleryCount = count($gallery);
$galleryVisible = count(array_filter($gallery, fn($g) => !empty($g['visible'])));

// Today status
$isOpen = false;
$openStr = 'Suljettu';
$todayName = date('N'); // 1=mon..7=sun
$dayMap = ['1' => 'mon','2' => 'tue','3' => 'wed','4' => 'thu','5' => 'fri','6' => 'sat','7' => 'sun'];
$todayKey = $dayMap[$todayName] ?? '';
foreach ($settings['opening_exceptions'] ?? [] as $exc) {
    if (($exc['date'] ?? '') === $today) {
        if (!empty($exc['closed'])) { $isOpen = false; $openStr = 'Suljettu (poikkeus)'; } else { $openStr = ($exc['open'] ?? '') . '–' . ($exc['close'] ?? ''); $isOpen = true; }
        break;
    }
}
if (!$isOpen) {
    foreach ($settings['opening_hours'] ?? [] as $h) {
        if (($h['day'] ?? '') === $todayKey) {
            if (!empty($h['closed'])) { $openStr = 'Suljettu'; } else { $openStr = ($h['open'] ?? '') . '–' . ($h['close'] ?? ''); $isOpen = true; }
            break;
        }
    }
}
$kitchenCloses = '';
foreach ($settings['opening_hours'] ?? [] as $h) {
    if (($h['day'] ?? '') === $todayKey && !empty($h['kitchen_closes'])) {
        $kitchenCloses = $h['kitchen_closes'];
        break;
    }
}
$seoMissing = empty($settings['seo_title_fi']) || empty($settings['seo_title_en']) || empty($settings['seo_description_fi']) || empty($settings['seo_description_en']);

$title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="dashboard-grid">
    <div class="health-grid">
    <div class="health-card">
        <div class="health-card__icon health-card__icon--<?= $isOpen ? 'success' : 'danger' ?>"></div>
        <div class="health-card__body">
            <strong>Tänään <?= $openStr ?></strong>
            <?php if ($kitchenCloses): ?>
            <p>Keittiö sulkeutuu klo <?= esc($kitchenCloses) ?></p>
            <?php endif; ?>
            <p><?= $activeNotices > 0 ? $activeNotices . ' ilmoitusta voimassa' : 'Ei voimassa olevia ilmoituksia' ?></p>
        </div>
    </div>

    <a href="/admin/menu.php" class="health-card">
        <div class="health-card__icon health-card__icon--<?= $menuWithoutEn > 0 || $menuWithoutImage > 0 ? 'warning' : 'success' ?>"></div>
        <div class="health-card__body">
            <strong>Menu: <?= $menuVisible ?> näkyvissä</strong>
            <p>
                <?php if ($menuWithoutEn > 0): ?><?= $menuWithoutEn ?> ilman EN-käännöstä · <?php endif; ?>
                <?php if ($menuWithoutImage > 0): ?><?= $menuWithoutImage ?> ilman kuvaa · <?php endif; ?>
                <?= $menuHidden ?> piilotettuna
            </p>
        </div>
    </a>

    <a href="/admin/events.php" class="health-card">
        <div class="health-card__icon health-card__icon--<?= $eventsUpcoming > 0 ? 'success' : 'default' ?>"></div>
        <div class="health-card__body">
            <strong><?= $eventsUpcoming ?> tulevaa tapahtumaa</strong>
            <p><?= $eventsToday > 0 ? $eventsToday . ' tänään' : 'Ei tapahtumia tänään' ?> · <?= $eventCount ?> yhteensä</p>
        </div>
    </a>

    <a href="/admin/notices.php" class="health-card">
        <div class="health-card__icon health-card__icon--<?= $expiredNotices > 0 ? 'danger' : ($activeNotices > 0 ? 'success' : 'default') ?>"></div>
        <div class="health-card__body">
            <strong><?= $activeNotices ?> aktiivista ilmoitusta</strong>
            <p><?= $expiredNotices > 0 ? $expiredNotices . ' vanhentunutta' : 'Ei vanhentuneita' ?></p>
        </div>
    </a>

    <a href="/admin/gallery.php" class="health-card">
        <div class="health-card__icon health-card__icon--default"></div>
        <div class="health-card__body">
            <strong><?= $galleryVisible ?> kuvaa näkyvissä</strong>
            <p><?= $galleryCount ?> yhteensä galleriassa</p>
        </div>
    </a>

    <?php if ($seoMissing): ?>
    <a href="/admin/settings.php#section-seo" class="health-card">
        <div class="health-card__icon health-card__icon--warning"></div>
        <div class="health-card__body">
            <strong>SEO puutteellinen</strong>
            <p>Täytä SEO-otsikot ja -kuvaukset molemmille kielille</p>
        </div>
    </a>
    <?php endif; ?>
    </div>

    <div>
    <div class="card">
        <h2>Pikatoiminnot</h2>
        <div class="toolbar" style="margin-bottom:0">
            <a href="/admin/menu.php#new-menu-item" class="btn btn--secondary btn--sm">+ Menu-annos</a>
            <a href="/admin/lunch.php" class="btn btn--secondary btn--sm">+ Lounas</a>
            <a href="/admin/events.php#new-event" class="btn btn--secondary btn--sm">+ Tapahtuma</a>
            <a href="/admin/notices.php" class="btn btn--secondary btn--sm">+ Ilmoitus</a>
            <a href="/admin/gallery.php" class="btn btn--secondary btn--sm">+ Kuva</a>
            <a href="/admin/hours.php" class="btn btn--secondary btn--sm">Aukioloajat</a>
            <a href="/admin/settings.php" class="btn btn--secondary btn--sm">Asetukset</a>
            <a href="/" class="btn btn--primary btn--sm" target="_blank">Esikatsele sivustoa</a>
        </div>
    </div>

    <div class="card">
        <h2>Viimeisin aktiviteetti</h2>
        <p class="text-sm text-gray">Seuraa muokkaustapahtumia. Yhteenveto tallennuksista ja päivityksistä.</p>
        <div class="activity-list mt-4">
            <div class="activity-item">
                <span class="activity-item__time">—</span>
                <span>Aktiviteettiloki tulee myöhemmässä vaiheessa. Tällä hetkellä muutokset tallennetaan suoraan.</span>
            </div>
        </div>
    </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
