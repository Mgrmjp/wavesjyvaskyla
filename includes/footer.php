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
                <a href="<?= esc($link['url'] ?? '') ?>" target="_blank" rel="noopener" class="footer-link">
                    <?= esc($socialLabels[$platform] ?? ucfirst($platform)) ?>
                </a>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="footer__bottom border-t border-editorial text-xs text-muted">
            <p>&copy; <?= date('Y') ?> Konttiravintola Waves</p>
            <p><?= esc($address !== '' ? $address : t('Satamakatu 2 B, 40100 Jyväskylä', 'Satamakatu 2 B, 40100 Jyväskylä')) ?></p>
        </div>
        <p class="footer__credit text-xs text-muted opacity-50">
            <?= t('Sivusto: ', 'Site by: ') ?><a href="https://www.linkedin.com/in/miikkamgr/" target="_blank" rel="noopener" class="hover:text-text transition-colors">Miikka</a>
        </p>
    </div>
</footer>

<script defer src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($loadLeaflet)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
if (document.getElementById('map')) {
  var map = L.map('map', {scrollWheelZoom: false, zoomControl: true, attributionControl: false}).setView([62.2386, 25.7531], 15);
  L.tileLayer('https://api.thunderforest.com/mobile-atlas/{z}/{x}/{y}.png?apikey=01c622ecbd814385a3da39c682350bf3', {
    maxZoom: 19,
    attribution: 'Maps &copy; <a href="https://www.thunderforest.com">Thunderforest</a>, Data &copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>'
  }).addTo(map);
  var pinIcon = L.divIcon({
    className: '',
    html: '<div style="width:32px;height:42px;position:relative;"><svg viewBox="0 0 24 36" width="32" height="42"><path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 24 12 24s12-15 12-24C24 5.4 18.6 0 12 0z" fill="#c8d86b"/><circle cx="12" cy="12" r="5" fill="#07110f"/></svg></div>',
    iconSize: [32, 42],
    iconAnchor: [16, 42],
    popupAnchor: [0, -42]
  });
  L.marker([62.2386, 25.7531], {icon: pinIcon}).addTo(map).bindPopup('<strong>Waves</strong><br>Satamakatu 2 B');
  var pois = [
    {name:'Paviljonki',lat:62.2391,lon:25.7592,type:'landmark'},
    {name:'Rautatieasema',lat:62.2407,lon:25.7525,type:'transit'},
    {name:'Jyväskylä-kirjaimet',lat:62.2374,lon:25.7541,type:'landmark'},
    {name:'P-Matkakeskus',lat:62.2431,lon:25.7571,type:'parking'},
    {name:'P-Paviljonki',lat:62.2390,lon:25.7552,type:'parking'},
    {name:'Hiisi',lat:62.2392,lon:25.7545,type:'restaurant'},
    {name:'Faneri',lat:62.2399,lon:25.7587,type:'restaurant'},
    {name:'Sataman Viilu',lat:62.2353,lon:25.7596,type:'restaurant'}
  ];
  var typeColor = {landmark:'#ff6b35',transit:'#2563eb',parking:'#16a34a',restaurant:'#9333ea'};
  pois.forEach(function(p) {
    var c = typeColor[p.type]||'#555';
    var dotIcon = L.divIcon({
      className: '',
      html: '<div style="width:8px;height:8px;border-radius:50%;background:'+c+';border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.4);"></div>',
      iconSize: [8, 8],
      iconAnchor: [4, 4]
    });
    var marker = L.marker([p.lat, p.lon], {icon: dotIcon}).addTo(map);
    marker.bindTooltip(p.name, {
      permanent: true,
      direction: 'right',
      offset: [6, 0],
      className: 'poi-label poi-' + p.type
    });
  });
}
</script>
<?php endif; ?>
</body>
</html>
