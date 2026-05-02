<?php
$title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h2>Tervetuloa Waves-hallintaan</h2>
    <p class="text-sm text-gray">Täällä voit muokata ravintolan tietoja, menua, lounaita, tapahtumia ja ilmoituksia.</p>
    <div class="flex gap-2 mt-4 flex-wrap">
        <a href="/admin/settings.php" class="nav-link">Yleiset asetukset</a>
        <a href="/admin/notices.php" class="nav-link">Ilmoitukset</a>
        <a href="/admin/hours.php" class="nav-link">Aukioloajat</a>
        <a href="/admin/menu.php" class="nav-link">Menu</a>
        <a href="/admin/lunch.php" class="nav-link">Lounas</a>
        <a href="/admin/events.php" class="nav-link">Tapahtumat</a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
