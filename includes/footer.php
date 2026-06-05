</main>

<?php
$footerHours = array_values(array_filter(
    $s['opening_hours'] ?? [],
    static fn(array $hour): bool => !($hour['closed'] ?? false)
));
$address = trim((string) ($s['address'] ?? ''));
$addressParts = $address !== '' ? array_map('trim', explode(',', $address, 2)) : [];
$socialLabels = [
    'instagram' => 'Instagram',
    'tiktok' => 'TikTok',
    'facebook' => 'Facebook',
    'x' => 'X',
];
?>
<footer class="site-footer border-t border-editorial">
    <div class="site-footer__inner max-w-5xl mx-auto px-5">
        <div class="footer-grid">
            <div class="footer-column footer-column--brand">
                <?php
                $svgPath = __DIR__ . '/../assets/files/waves.svg';
                $svg = file_exists($svgPath) ? file_get_contents($svgPath) : '';
                if ($svg) {
                    $svg = preg_replace('/<\?xml[^?]*\?>\s*/', '', $svg);
                    $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/', '', $svg);
                    $svg = preg_replace('/\s*width="[^"]*"/', '', $svg);
                    $svg = preg_replace('/\s*height="[^"]*"/', '', $svg);
                    $svg = preg_replace('/<svg\s/', '<svg fill="#f4ead7" style="width:120px;height:auto;display:block;margin-bottom:0.75rem;" ', $svg, 1);
                    echo $svg;
                } else {
                    echo '<p class="text-xl font-extrabold mb-2" style="letter-spacing:0">WAVES</p>';
                }
                ?>
                <p class="footer-copy text-sm"><?= t('Konttiravintola Jyväskylän satamassa. Ei pöytävarauksia.', 'Container restaurant at Jyväskylä harbor. No reservations.') ?></p>
            </div>

            <div class="footer-column">
                <p class="footer-heading"><?= t('Käy', 'Visit') ?></p>
                <?php if ($addressParts !== []): ?>
                <p class="footer-copy">
                    <span><?= esc($addressParts[0] ?? '') ?></span>
                    <?php if (!empty($addressParts[1])): ?>
                    <span><?= esc($addressParts[1]) ?></span>
                    <?php endif; ?>
                </p>
                <?php else: ?>
                <p class="footer-copy">
                    <span><?= t('Satamakatu 2 B', 'Satamakatu 2 B') ?></span>
                    <span>40100 Jyväskylä</span>
                </p>
                <?php endif; ?>
            </div>

            <div class="footer-column">
                <p class="footer-heading"><?= t('Auki', 'Open') ?></p>
                <?php if ($footerHours !== []): ?>
                <div class="footer-hours">
                    <?php foreach ($footerHours as $hour): ?>
                    <p class="footer-hours-row">
                        <span><?= dayLabel($hour['day'] ?? '') ?></span>
                        <span><?= esc($hour['open'] ?? '') ?>–<?= esc($hour['close'] ?? '') ?></span>
                    </p>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="footer-copy">
                    <span><?= t('Tarkista päivän', 'Check today') ?></span>
                    <span><?= t('aukioloajat yllä.', 'hours above.') ?></span>
                </p>
                <?php endif; ?>
            </div>

            <?php if (!empty($s['social_links'])): ?>
            <div class="footer-column">
                <p class="footer-heading"><?= t('Seuraa', 'Follow') ?></p>
                <div class="footer-socials">
                <?php foreach ($s['social_links'] as $link): ?>
                <?php $platform = strtolower((string) ($link['platform'] ?? '')); ?>
                <?php $socialUrl = safeExternalUrl((string) ($link['url'] ?? '')); if ($socialUrl === '') continue; ?>
                <a href="<?= esc($socialUrl) ?>" target="_blank" rel="noopener" class="footer-link">
                    <?= esc($socialLabels[$platform] ?? ucfirst($platform)) ?>
                </a>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="footer-notice border-t border-editorial">
            <p class="footer-notice__label"><?= t('Epävirallinen sivusto', 'Unofficial website') ?></p>
            <p class="footer-notice__text"><?= t('Tämä ei ole Konttiravintola Wavesin virallinen verkkosivusto. Sivusto on toteutettu itsenäisenä projektina, ja kaikki sivuston kautta lähetetty palaute ohjataan sivuston tekijälle eikä välity Wavesin henkilöstölle.', 'This is not the official Konttiravintola Waves website. The site is maintained as an independent project, and all feedback sent through this site is directed to the site creator rather than Waves staff.') ?></p>
        </div>
        <div class="footer__bottom text-xs text-muted">
            <p><?= esc($address !== '' ? $address : t('Satamakatu 2 B, 40100 Jyväskylä', 'Satamakatu 2 B, 40100 Jyväskylä')) ?></p>
            <p>
                <?= t('Sivusto: ', 'Site by: ') ?><a href="https://www.linkedin.com/in/miikkamgr/" target="_blank" rel="noopener" class="hover:text-text transition-colors">Miikka</a>
            </p>
        </div>
    </div>
</footer>

<script defer src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($loadLeaflet)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
if (document.getElementById('map') && window.L) {
  var wavesLatLng = [62.2386, 25.7531];
  var map = L.map('map', {
    scrollWheelZoom: false,
    zoomControl: true,
    attributionControl: true
  }).setView(wavesLatLng, 17);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(map);
  var pinIcon = L.divIcon({
    className: 'waves-map-marker',
    html: '<span class="waves-map-marker__pulse"></span><span class="waves-map-marker__pin"><svg viewBox="0 0 52 64" width="52" height="64" aria-hidden="true" focusable="false"><path d="M26 4C14.4 4 6 13 6 24.4 6 40.6 26 59 26 59s20-18.4 20-34.6C46 13 37.6 4 26 4z" fill="#07110f" stroke="#f5f5dc" stroke-width="4"/><circle cx="26" cy="24" r="11" fill="#c5e063" stroke="#07110f" stroke-width="4"/><text x="26" y="29" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" font-weight="900" fill="#07110f">W</text></svg></span>',
    iconSize: [52, 64],
    iconAnchor: [26, 59],
    popupAnchor: [0, -58]
  });
  L.marker(wavesLatLng, {
    icon: pinIcon,
    title: 'Waves',
    alt: 'Waves'
  }).addTo(map).bindPopup('<strong>Waves</strong><br>Satamakatu 2 B');
  map.whenReady(function() {
    map.invalidateSize();
  });
}
</script>
<?php endif; ?>
</body>
</html>
