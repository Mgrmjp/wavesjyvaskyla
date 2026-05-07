<?php
$s = settings();
$notices = notices();
$isOpen = isOpenNow();
?>
<!DOCTYPE html>
<html lang="<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($s['seo_title_' . lang()] ?: $s['title_' . lang()] ?? 'Waves') ?><?php if (($page['slug'] ?? '') !== ''): ?> — <?= esc($page['title'] ?? '') ?><?php endif; ?></title>
<meta name="description" content="<?= esc($s['seo_description_' . lang()] ?: t('Konttiravintola Waves Jyväskylän satamassa – kesäisiä makuja ja kylmiä juomia merellisessä miljöössä.', 'Container Restaurant Waves at Jyväskylä harbor – summer flavors, cold drinks and maritime atmosphere.')) ?>">
<link rel="stylesheet" href="<?= asset('css/index.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="canonical" href="https://wavesjyvaskyla.fi<?= url($page['slug'] ?? '') ?>">
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#003561">
<?php
$slugFi = Router::slugForLang($page['slug'] ?? '', 'fi');
$slugEn = Router::slugForLang($page['slug'] ?? '', 'en');
?>
<link rel="alternate" hreflang="fi" href="https://wavesjyvaskyla.fi<?= $slugFi ? '/' . $slugFi : '' ?>">
<link rel="alternate" hreflang="en" href="https://wavesjyvaskyla.fi/en<?= $slugEn ? '/' . $slugEn : '' ?>">
<link rel="alternate" hreflang="x-default" href="https://wavesjyvaskyla.fi<?= $slugFi ? '/' . $slugFi : '' ?>">
<meta property="og:title" content="<?= esc((($page['slug'] ?? '') !== '' ? ($page['title'] ?? '') . ' — ' : '') . ($s['title_' . lang()] ?? 'Waves')) ?>">
<meta property="og:description" content="<?= esc($s['seo_description_' . lang()] ?? '') ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="https://wavesjyvaskyla.fi<?= url($page['slug'] ?? '') ?>">
<meta property="og:locale" content="<?= lang() === 'fi' ? 'fi_FI' : 'en_US' ?>">
<meta property="og:site_name" content="Waves">
<meta name="robots" content="<?= ($page['robots'] ?? 'index, follow') ?>">
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => 'Konttiravintola Waves',
    'url' => 'https://wavesjyvaskyla.fi',
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Satamakatu 2 B', 'addressLocality' => 'Jyväskylä', 'postalCode' => '40100', 'addressCountry' => 'FI'],
    'servesCuisine' => ['Mexican', 'Burgers', 'Finnish'],
    'priceRange' => '€€',
    'telephone' => $s['phone'] ?? '',
]) ?></script>
</head>
<body class="min-h-screen flex flex-col">

<?php if (count($notices)): ?>
<div class="max-w-5xl mx-auto px-5 pt-4">
    <?php foreach ($notices as $n): ?>
    <div class="notice-bar <?= ($n['style'] ?? 'info') === 'warning' ? '' : 'info' ?> mb-3">
        <?= esc($n['text_' . lang()] ?? '') ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<header style="width:100%" class="max-w-5xl mx-auto px-5 py-8 md:py-10 flex items-center justify-between gap-8">
    <a href="<?= url() ?>" class="shrink-0 flex items-center" aria-label="Waves">
        <?php
        $svgPath = __DIR__ . '/../assets/files/waves.svg';
        $svg = file_exists($svgPath) ? file_get_contents($svgPath) : '';
        if ($svg) {
            $svg = preg_replace('/<\?xml[^?]*\?>\s*/', '', $svg);
            $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/', '', $svg);
            $svg = preg_replace('/\s*width="[^"]*"/', '', $svg);
            $svg = preg_replace('/\s*height="[^"]*"/', '', $svg);
            $svg = preg_replace('/<svg\s/', '<svg fill="#f5f5f0" height="32" style="width:auto;display:block;" ', $svg, 1);
            echo $svg;
        } else {
            echo '<span style="font-size:1.25rem;font-weight:800;letter-spacing:-0.03em;color:#f5f5f0">WAVES</span>';
        }
        ?>
    </a>
    <button id="menu-toggle" class="md:hidden flex flex-col justify-center gap-1 w-8 h-8" aria-label="Menu" aria-expanded="false">
        <span class="block w-6 bg-text" style="height:2px"></span>
        <span class="block w-6 bg-text" style="height:2px"></span>
        <span class="block w-6 bg-text" style="height:2px"></span>
    </button>
    <nav class="hidden md:flex items-center gap-5 md:gap-8 overflow-x-auto" id="desktop-nav">
        <?php
        $nav = [
            ['slug' => '',        'fi' => 'Etusivu',    'en' => 'Home'],
            ['slug' => 'menu',    'fi' => 'Menu',       'en' => 'Menu'],
            ['slug' => 'lounas',  'fi' => 'Lounas',     'en' => 'Lunch'],
            ['slug' => 'tapahtumat','fi'=>'Tapahtumat', 'en' => 'Events'],
            ['slug' => 'kuvat',   'fi' => 'Kuvat',      'en' => 'Gallery'],
            ['slug' => 'yhteystiedot','fi'=>'Yhteystiedot','en'=>'Contact'],
        ];
        foreach ($nav as $item):
            $active = ($page['slug'] ?? '') === $item['slug'];
            $href = $item['slug'] ? url($item['slug']) : url();
        ?>
        <a href="<?= $href ?>" class="nav-link <?= $active ? 'active' : '' ?>"><?= $item[lang()] ?></a>
        <?php endforeach; ?>
<?php $alt = lang() === 'fi' ? 'en' : 'fi'; ?>
        <a href="<?= $alt === 'en' ? '/en' . ($page['slug'] ? '/' . $page['slug'] : '') : '/' . ($page['slug'] ?? '') ?>" class="text-muted hover:text-text transition-colors" aria-label="<?= strtoupper($alt) ?>"><?php if ($alt === 'fi'): ?><svg viewBox="0 0 1800 1100" width="22" height="15" style="display:block;border-radius:2px;overflow:hidden;"><rect width="1800" height="1100" fill="#fff"/><rect width="1800" height="300" y="400" fill="#003580"/><rect width="300" height="1100" x="500" fill="#003580"/></svg><?php else: ?><svg viewBox="0 0 60 30" width="22" height="15" style="display:block;border-radius:2px;overflow:hidden;"><clipPath id="s"><path d="M0,0 v30 h60 v-30 z"/></clipPath><clipPath id="t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><g clip-path="url(#s)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></g></svg><?php endif; ?></a>
    </nav>
</header>
<nav id="mobile-menu" class="hidden md:hidden border-t border-editorial">
    <div class="max-w-5xl mx-auto px-5 py-4 flex flex-col gap-4">
        <?php foreach ($nav as $item):
            $active = ($page['slug'] ?? '') === $item['slug'];
            $href = $item['slug'] ? url($item['slug']) : url();
        ?>
        <a href="<?= $href ?>" class="nav-link text-lg <?= $active ? 'active' : '' ?>"><?= $item[lang()] ?></a>
        <?php endforeach; ?>
        <?php $alt = lang() === 'fi' ? 'en' : 'fi'; ?>
        <a href="<?= $alt === 'en' ? '/en' . ($page['slug'] ? '/' . $page['slug'] : '') : '/' . ($page['slug'] ?? '') ?>" class="nav-link text-lg text-muted" aria-label="<?= strtoupper($alt) ?>"><?php if ($alt === 'fi'): ?><svg viewBox="0 0 1800 1100" width="22" height="15" style="display:block;border-radius:2px;overflow:hidden;"><rect width="1800" height="1100" fill="#fff"/><rect width="1800" height="300" y="400" fill="#003580"/><rect width="300" height="1100" x="500" fill="#003580"/></svg><?php else: ?><svg viewBox="0 0 60 30" width="22" height="15" style="display:block;border-radius:2px;overflow:hidden;"><clipPath id="s2"><path d="M0,0 v30 h60 v-30 z"/></clipPath><clipPath id="t2"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><g clip-path="url(#s2)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t2)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></g></svg><?php endif; ?></a>
    </div>
</nav>

<div id="demo-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="demo-modal-title">
  <div class="modal-dialog">
    <button class="modal-close" aria-label="<?= lang() === 'fi' ? 'Sulje' : 'Close' ?>">&times;</button>
    <h2 id="demo-modal-title" class="headline mb-2"><?= lang() === 'fi' ? 'Demo-sivusto' : 'Demo Site' ?></h2>
    <p class="text-muted mb-4"><?= lang() === 'fi' ? 'Tämä on vain demo-sivusto. Kaikki sisältö ei ole ajantasaista ja voi olla kopio vuodelta 2025.' : 'This is a demo site only. All content may not be up to date and could be copied from 2025.' ?></p>
    <label class="modal-dismiss-label"><input type="checkbox" id="demo-modal-dismiss"> <?= lang() === 'fi' ? 'Älä näytä uudelleen' : "Don't show again" ?></label>
    <button class="btn" id="demo-modal-ok"><?= lang() === 'fi' ? 'OK' : 'OK' ?></button>
  </div>
</div>

<main class="flex-1">
