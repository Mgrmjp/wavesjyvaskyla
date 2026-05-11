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
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,600,700,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script async src="https://scripts.simpleanalyticscdn.com/latest.js"></script>
<link rel="canonical" href="https://wavesjyvaskyla.fi<?= url($page['slug'] ?? '') ?>">
<link rel="icon" href="<?= publicAsset('/favicon.ico') ?>">
<link rel="icon" href="<?= publicAsset('/favicon.svg') ?>" type="image/svg+xml">
<link rel="icon" href="<?= publicAsset('/favicon-16x16.png') ?>" sizes="16x16" type="image/png">
<link rel="icon" href="<?= publicAsset('/favicon-32x32.png') ?>" sizes="32x32" type="image/png">
<link rel="icon" href="<?= publicAsset('/android-chrome-192x192.png') ?>" sizes="192x192" type="image/png">
<link rel="icon" href="<?= publicAsset('/android-chrome-512x512.png') ?>" sizes="512x512" type="image/png">
<link rel="apple-touch-icon" href="<?= publicAsset('/apple-touch-icon.png') ?>">
<link rel="manifest" href="<?= publicAsset('/site.webmanifest') ?>">
<meta name="theme-color" content="#07110f">
<meta name="msapplication-TileColor" content="#07110f">
<meta name="msapplication-TileImage" content="<?= publicAsset('/mstile-150x150.png') ?>">
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

<div id="demo-disclaimer" class="demo-disclaimer" role="status" aria-live="polite">
    <p class="demo-disclaimer__text">
        <?= t('Huom: sivuston tiedot voivat olla osittain keskeneräisiä demovaiheen vuoksi.', 'Note: some site information may be incomplete or inaccurate during this demo phase.') ?>
    </p>
    <button type="button" class="demo-disclaimer__close" aria-label="<?= t('Sulje ilmoitus', 'Dismiss notice') ?>">
        <svg viewBox="0 0 12 12" aria-hidden="true" focusable="false">
            <line x1="2" y1="2" x2="10" y2="10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            <line x1="10" y1="2" x2="2" y2="10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
    </button>
</div>

<?php if (count($notices)): ?>
<div class="max-w-5xl mx-auto px-5 pt-4">
    <?php foreach ($notices as $n): ?>
    <div class="notice-bar <?= ($n['style'] ?? 'info') === 'warning' ? '' : 'info' ?> mb-3">
        <?= esc($n['text_' . lang()] ?? '') ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<header class="site-header">
    <div class="site-header__inner">
        <div class="site-brand">
            <a href="<?= url() ?>" class="site-logo" aria-label="Waves">
                <?php
                $svgPath = __DIR__ . '/../assets/files/waves.svg';
                $svg = file_exists($svgPath) ? file_get_contents($svgPath) : '';
                if ($svg) {
                    $svg = preg_replace('/<\?xml[^?]*\?>\s*/', '', $svg);
                    $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/', '', $svg);
                    $svg = preg_replace('/\s*width="[^"]*"/', '', $svg);
                    $svg = preg_replace('/\s*height="[^"]*"/', '', $svg);
                    $svg = preg_replace('/<svg\s/', '<svg fill="#f4ead7" style="width:auto;display:block;" ', $svg, 1);
                    echo $svg;
                } else {
                    echo '<span>WAVES</span>';
                }
                ?>
            </a>
        </div>
        <button id="menu-toggle" class="site-menu-toggle" aria-label="Menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="site-nav" id="desktop-nav">
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
        <a href="<?= $alt === 'en' ? '/en' . ($page['slug'] ? '/' . $page['slug'] : '') : '/' . ($page['slug'] ?? '') ?>" class="site-lang" aria-label="<?= strtoupper($alt) ?>"><?php if ($alt === 'fi'): ?><svg viewBox="0 0 1800 1100" width="22" height="15" style="display:block;border-radius:2px;overflow:hidden;"><rect width="1800" height="1100" fill="#fff"/><rect width="1800" height="300" y="400" fill="#003580"/><rect width="300" height="1100" x="500" fill="#003580"/></svg><?php else: ?><svg viewBox="0 0 60 30" width="22" height="15" style="display:block;border-radius:2px;overflow:hidden;"><clipPath id="s"><path d="M0,0 v30 h60 v-30 z"/></clipPath><clipPath id="t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><g clip-path="url(#s)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></g></svg><?php endif; ?></a>
        </nav>
    </div>
</header>
<nav id="mobile-menu" class="site-mobile-menu hidden">
    <div class="site-mobile-menu__inner">
        <?php foreach ($nav as $item):
            $active = ($page['slug'] ?? '') === $item['slug'];
            $href = $item['slug'] ? url($item['slug']) : url();
        ?>
        <a href="<?= $href ?>" class="nav-link <?= $active ? 'active' : '' ?>"><?= $item[lang()] ?></a>
        <?php endforeach; ?>
        <?php $alt = lang() === 'fi' ? 'en' : 'fi'; ?>
        <a href="<?= $alt === 'en' ? '/en' . ($page['slug'] ? '/' . $page['slug'] : '') : '/' . ($page['slug'] ?? '') ?>" class="site-lang" aria-label="<?= strtoupper($alt) ?>"><?php if ($alt === 'fi'): ?><svg viewBox="0 0 1800 1100" width="22" height="15" style="display:block;border-radius:2px;overflow:hidden;"><rect width="1800" height="1100" fill="#fff"/><rect width="1800" height="300" y="400" fill="#003580"/><rect width="300" height="1100" x="500" fill="#003580"/></svg><?php else: ?><svg viewBox="0 0 60 30" width="22" height="15" style="display:block;border-radius:2px;overflow:hidden;"><clipPath id="s2"><path d="M0,0 v30 h60 v-30 z"/></clipPath><clipPath id="t2"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><g clip-path="url(#s2)"><path d="M0,0 v30 h60 v-30 z" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t2)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></g></svg><?php endif; ?></a>
    </div>
</nav>

<main class="flex-1">
