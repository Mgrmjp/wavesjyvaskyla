<?php

if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
if (!defined('DATA_DIR')) define('DATA_DIR', ROOT . '/data');
if (!defined('INCLUDES_DIR')) define('INCLUDES_DIR', ROOT . '/includes');
if (!defined('TEMPLATES_DIR')) define('TEMPLATES_DIR', ROOT . '/templates');
if (!defined('ADMIN_DIR')) define('ADMIN_DIR', ROOT . '/admin');

require_once INCLUDES_DIR . '/storage.php';

function ensureSessionStarted(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

class DataStore {
    private static function path(string $name): string {
        return DATA_DIR . '/' . $name . '.json';
    }

    public static function load(string $name): array {
        if (AppStore::isMigrated($name)) {
            return AppStore::load($name);
        }
        $path = self::path($name);
        if (!file_exists($path)) return [];
        $json = file_get_contents($path);
        return json_decode($json, true) ?: [];
    }

    public static function save(string $name, array $data): void {
        if (AppStore::isMigrated($name)) {
            AppStore::save($name, $data);
            return;
        }
        $path = self::path($name);
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public static function ensure(string $name, array $default): array {
        if (AppStore::isMigrated($name)) {
            $data = AppStore::load($name);
            if (empty($data)) {
                AppStore::save($name, $default);
                return $default;
            }
            return $data;
        }
        $data = self::load($name);
        if (empty($data)) {
            self::save($name, $default);
            return $default;
        }
        return $data;
    }
}

    class Router {
    private array $routes = [
        ''         => ['template' => 'home',      'fi' => 'Etusivu',     'en' => 'Home'],
        'menu'     => ['template' => 'menu',      'fi' => 'Menu',        'en' => 'Menu'],
        'lounas'   => ['template' => 'lunch',     'fi' => 'Lounas',      'en' => 'Lunch'],
        'lunch'    => ['template' => 'lunch',     'fi' => 'Lounas',      'en' => 'Lunch'],
        'tapahtumat'=> ['template' => 'events',   'fi' => 'Tapahtumat',  'en' => 'Events'],
        'events'   => ['template' => 'events',    'fi' => 'Tapahtumat',  'en' => 'Events'],
        'yhteystiedot'=> ['template' => 'contact','fi' => 'Yhteystiedot','en' => 'Contact'],
        'contact'  => ['template' => 'contact',   'fi' => 'Yhteystiedot','en' => 'Contact'],
        'contact-submit' => ['template' => 'contact', 'fi' => 'Yhteystiedot', 'en' => 'Contact'],
        'tietosuoja'=> ['template' => 'privacy',  'fi' => 'Tietosuoja',     'en' => 'Privacy'],
        'privacy'   => ['template' => 'privacy',   'fi' => 'Tietosuoja',     'en' => 'Privacy'],
        'kuvat'     => ['template' => 'gallery',  'fi' => 'Kuvat',        'en' => 'Gallery'],
        'gallery'   => ['template' => 'gallery',  'fi' => 'Kuvat',        'en' => 'Gallery'],
    ];

    private static array $pageMap = [
        ''       => ['fi' => '', 'en' => ''],
        'menu'   => ['fi' => 'menu', 'en' => 'menu'],
        'lounas' => ['fi' => 'lounas', 'en' => 'lunch'],
        'lunch'  => ['fi' => 'lounas', 'en' => 'lunch'],
        'tapahtumat' => ['fi' => 'tapahtumat', 'en' => 'events'],
        'events' => ['fi' => 'tapahtumat', 'en' => 'events'],
        'yhteystiedot' => ['fi' => 'yhteystiedot', 'en' => 'contact'],
        'contact' => ['fi' => 'yhteystiedot', 'en' => 'contact'],
        'tietosuoja' => ['fi' => 'tietosuoja', 'en' => 'privacy'],
        'privacy' => ['fi' => 'tietosuoja', 'en' => 'privacy'],
        'kuvat' => ['fi' => 'kuvat', 'en' => 'gallery'],
        'gallery' => ['fi' => 'kuvat', 'en' => 'gallery'],
    ];

    public static function allPages(): array {
        $seen = [];
        $pages = [];
        $entries = [
            ['slug_fi' => '', 'slug_en' => '', 'template' => 'home'],
            ['slug_fi' => 'menu', 'slug_en' => 'menu', 'template' => 'menu'],
            ['slug_fi' => 'lounas', 'slug_en' => 'lunch', 'template' => 'lunch'],
            ['slug_fi' => 'tapahtumat', 'slug_en' => 'events', 'template' => 'events'],
            ['slug_fi' => 'yhteystiedot', 'slug_en' => 'contact', 'template' => 'contact'],
            ['slug_fi' => 'tietosuoja', 'slug_en' => 'privacy', 'template' => 'privacy'],
        ];
        foreach ($entries as $e) {
            if (isset($seen[$e['slug_fi']])) continue;
            $seen[$e['slug_fi']] = true;
            $pages[] = $e;
        }
        return $pages;
    }

    public static function slugForLang(string $slug, string $lang): string {
        return self::$pageMap[$slug][$lang] ?? $slug;
    }

    public function dispatch(): void {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = trim($uri, '/');
        $parts = explode('/', $uri);

        $lang = 'fi';
        $slug = '';

        if (isset($parts[0]) && $parts[0] === 'en') {
            $lang = 'en';
            $slug = $parts[1] ?? '';
        } else {
            $slug = $parts[0] ?? '';
        }

        $GLOBALS['lang'] = $lang;

        if (!isset($this->routes[$slug])) {
            http_response_code(404);
            include TEMPLATES_DIR . '/404.php';
            return;
        }

$route = $this->routes[$slug];

        $sent = false;
        $error = '';
        if ($slug === 'contact-submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            checkCsrf();
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $honeypot = $_POST['website'] ?? '';
            if ($honeypot !== '') { http_response_code(303); header('Location: ' . url(lang() === 'en' ? 'contact' : 'yhteystiedot')); exit; }
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (strlen($ua) < 10) {
                $error = t('Palvelupyynnön lähetys epäonnistui.', 'Submission failed.');
            } else {
                $js = $_POST['js'] ?? '';
                $ts = intval($_POST['ts'] ?? 0);
                $minTime = time() - 86400;
                if ($js === '' || $ts < $minTime) {
                    $error = t('Palvelupyynnön lähetys epäonnistui.', 'Submission failed. Please enable JavaScript.');
                } elseif (time() - $ts < 3) {
                    http_response_code(303); header('Location: ' . url(lang() === 'en' ? 'contact' : 'yhteystiedot')); exit;
                }
            }
            if ($error === '') {
            if ($name === '' || $email === '' || $message === '') {
                $error = t('Kaikki kentät ovat pakollisia.', 'All fields are required.');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = t('Virheellinen sähköpostiosoite.', 'Invalid email address.');
            } elseif (strlen($message) > 5000) {
                $error = t('Viesti on liian pitkä.', 'Message is too long.');
            } else {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $messages = DataStore::load('messages');
                $recent = count(array_filter($messages, function($m) use ($ip) {
                    return ($m['ip'] ?? '') === $ip && strtotime($m['date']) > time() - 3600;
                }));
                if ($recent >= 3) {
                    $error = t('Liian monta viestiä. Yritä myöhemmin uudelleen.', 'Too many messages. Please try again later.');
                } else {
                    $msg = [
                        'id' => generateId(),
                        'date' => date('c'),
                        'name' => $name,
                        'email' => $email,
                        'message' => $message,
                        'consent' => true,
                        'ip' => $ip,
                    ];
                    $messages[] = $msg;
                    DataStore::save('messages', $messages);
                    $sent = true;
                }
            }
            }
            $slug = lang() === 'en' ? 'contact' : 'yhteystiedot';
            $route = $this->routes[$slug];
        }

        $page = [
            'slug'     => $slug,
            'template' => $route['template'],
            'title'    => $route[$lang],
            'lang'     => $lang,
            'robots'   => $slug === 'contact-submit' ? 'noindex, nofollow' : 'index, follow',
        ];

        $templateFile = TEMPLATES_DIR . '/' . $route['template'] . '.php';
        if (file_exists($templateFile)) {
            include $templateFile;
        } else {
            http_response_code(500);
            echo 'Template missing: ' . htmlspecialchars($route['template']);
        }
    }
}

function t(string $fi, string $en): string {
    return ($GLOBALS['lang'] ?? 'fi') === 'fi' ? $fi : $en;
}

function lang(): string {
    return $GLOBALS['lang'] ?? 'fi';
}

function url(string $path = ''): string {
    $base = lang() === 'en' ? '/en/' : '/';
    $result = rtrim($base . $path, '/');
    return $result === '' ? '/' : $result;
}

function asset(string $path): string {
    return publicAsset('/assets/' . ltrim($path, '/'));
}

function publicAsset(string $path): string {
    $publicPath = '/' . ltrim($path, '/');
    $filePath = ROOT . $publicPath;

    if (!is_file($filePath)) {
        return $publicPath;
    }

    return $publicPath . '?v=' . filemtime($filePath);
}

function ensureUploadDirectory(): bool {
    $dir = ROOT . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return false;
    }

    return is_writable($dir);
}

function settings(): array {
    return DataStore::ensure('settings', defaultSettings());
}

function siteBaseUrl(): string {
    return 'https://wavesjyvaskyla.fi';
}

function seoPageKey(string $slug): string {
    return match ($slug) {
        '', 'home' => 'home',
        'menu' => 'menu',
        'lounas', 'lunch' => 'lunch',
        'tapahtumat', 'events' => 'events',
        'yhteystiedot', 'contact', 'contact-submit' => 'contact',
        'tietosuoja', 'privacy' => 'privacy',
        'kuvat', 'gallery' => 'gallery',
        default => 'generic',
    };
}

function seoPageDefaults(array $page = []): array {
    $defaults = [
        'fi' => [
            'home' => [
                'title' => 'Waves Jyväskylä | Ravintola Jyväskylän satamassa',
                'description' => 'Waves on konttiravintola Jyväskylän satamassa. Katso ruokalista, lounas, tapahtumat, aukioloajat ja yhteystiedot.',
            ],
            'menu' => [
                'title' => 'Ruokalista | Waves Jyväskylä',
                'description' => 'Tutustu Wavesin ruokalistaan: tacot, burgerit, salaatit, snackit ja lasten annokset Jyväskylän satamassa.',
            ],
            'lunch' => [
                'title' => 'Lounas | Waves Jyväskylä',
                'description' => 'Katso Wavesin lounas Jyväskylässä ja tarkista viikon annokset, hinnat sekä mahdolliset erityisruokavaliot.',
            ],
            'events' => [
                'title' => 'Tapahtumat | Waves Jyväskylä',
                'description' => 'Katso Wavesin tulevat tapahtumat, keikat ja kesäillat Jyväskylän satamassa.',
            ],
            'contact' => [
                'title' => 'Yhteystiedot | Waves Jyväskylä',
                'description' => 'Löydä Wavesin osoite, kartta, puhelin ja yhteystiedot Jyväskylän satamasta.',
            ],
            'privacy' => [
                'title' => 'Tietosuoja | Waves Jyväskylä',
                'description' => 'Lue Wavesin tietosuojaseloste ja miten yhteydenottolomakkeen tietoja käsitellään.',
            ],
            'gallery' => [
                'title' => 'Kuvat | Waves Jyväskylä',
                'description' => 'Selaa kuvia Wavesin konttiravintolasta, kesäterassista ja satamatunnelmasta Jyväskylässä.',
            ],
        ],
        'en' => [
            'home' => [
                'title' => 'Waves Jyväskylä | Restaurant at Jyväskylä Harbour',
                'description' => 'Waves is a container restaurant at Jyväskylä harbour. View the menu, lunch, events, opening hours and contact details.',
            ],
            'menu' => [
                'title' => 'Menu | Waves Jyväskylä',
                'description' => 'Explore the Waves menu with tacos, burgers, salads, snacks and kids\' portions at Jyväskylä harbour.',
            ],
            'lunch' => [
                'title' => 'Lunch | Waves Jyväskylä',
                'description' => 'Check the Waves lunch menu in Jyväskylä, including weekday dishes, prices and dietary options.',
            ],
            'events' => [
                'title' => 'Events | Waves Jyväskylä',
                'description' => 'See upcoming Waves events, live music and summer evenings at Jyväskylä harbour.',
            ],
            'contact' => [
                'title' => 'Contact | Waves Jyväskylä',
                'description' => 'Find the Waves address, map, phone number and contact details at Jyväskylä harbour.',
            ],
            'privacy' => [
                'title' => 'Privacy Policy | Waves Jyväskylä',
                'description' => 'Read the Waves privacy policy and how contact form data is processed.',
            ],
            'gallery' => [
                'title' => 'Gallery | Waves Jyväskylä',
                'description' => 'Browse photos from Waves container restaurant, the summer terrace and the harbour atmosphere in Jyväskylä.',
            ],
        ],
    ];

    $langDefaults = $defaults[lang()] ?? $defaults['fi'];
    $pageKey = seoPageKey((string) ($page['slug'] ?? ''));

    if (isset($langDefaults[$pageKey])) {
        return $langDefaults[$pageKey];
    }

    $pageTitle = trim((string) ($page['title'] ?? ''));
    $homeDefaults = $langDefaults['home'];

    return [
        'title' => $pageTitle !== '' ? $pageTitle . ' | Waves Jyväskylä' : $homeDefaults['title'],
        'description' => $homeDefaults['description'],
    ];
}

function seoTitle(array $settings, array $page = []): string {
    $pageOverride = trim((string) ($page['seo_title'] ?? ''));
    if ($pageOverride !== '') {
        return $pageOverride;
    }

    $customHomeTitle = trim((string) ($settings['seo_title_' . lang()] ?? ''));
    if (seoPageKey((string) ($page['slug'] ?? '')) === 'home' && $customHomeTitle !== '') {
        return $customHomeTitle;
    }

    return seoPageDefaults($page)['title'];
}

function seoDescription(array $settings, array $page = []): string {
    $pageOverride = trim((string) ($page['seo_description'] ?? ''));
    if ($pageOverride !== '') {
        return $pageOverride;
    }

    $customHomeDescription = trim((string) ($settings['seo_description_' . lang()] ?? ''));
    if (seoPageKey((string) ($page['slug'] ?? '')) === 'home' && $customHomeDescription !== '') {
        return $customHomeDescription;
    }

    return seoPageDefaults($page)['description'];
}

function seoCanonicalUrl(array $page = []): string {
    return siteBaseUrl() . url((string) ($page['slug'] ?? ''));
}

function seoImageUrl(): string {
    return siteBaseUrl() . publicAsset('/assets/files/frontpage-hero-upscaled.png');
}

function seoImageAlt(): string {
    return t(
        'Konttiravintola Waves Jyväskylän satamassa',
        'Waves container restaurant at Jyväskylä harbour'
    );
}

function restaurantSchema(array $settings): array {
    $sameAs = array_values(array_filter(array_map(
        static fn(array $link): string => trim((string) ($link['url'] ?? '')),
        $settings['social_links'] ?? []
    )));

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Restaurant',
        '@id' => siteBaseUrl() . '/#restaurant',
        'name' => trim((string) (($settings['title_' . lang()] ?? '') ?: t('Konttiravintola Waves', 'Container Restaurant Waves'))),
        'url' => siteBaseUrl(),
        'image' => seoImageUrl(),
        'description' => seoDescription($settings, []),
        'menu' => siteBaseUrl() . '/menu',
        'telephone' => trim((string) ($settings['phone'] ?? '')),
        'priceRange' => 'EUR 10-25',
        'servesCuisine' => ['Street Food', 'Burgers', 'Tacos'],
        'acceptsReservations' => false,
        'sameAs' => $sameAs,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Satamakatu 2 B',
            'addressLocality' => 'Jyväskylä',
            'postalCode' => '40100',
            'addressCountry' => 'FI',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => 62.2386,
            'longitude' => 25.7531,
        ],
    ];

    return array_filter($schema, static fn($value): bool => $value !== '' && $value !== [] && $value !== null);
}

function notices(): array {
    $data = DataStore::load('notices');
    $out = [];
    $today = date('Y-m-d');
    foreach ($data['notices'] ?? [] as $n) {
        if (!($n['active'] ?? false)) continue;
        if (($n['start_date'] ?? '') && $today < $n['start_date']) continue;
        if (($n['end_date'] ?? '') && $today > $n['end_date']) continue;
        $out[] = $n;
    }
    return $out;
}

function isOpenNow(): bool {
    $s = settings();
    $now = new DateTime();
    $today = $now->format('N');
    $time = $now->format('H:i');
    $todayStr = $now->format('Y-m-d');

    foreach ($s['opening_exceptions'] ?? [] as $exc) {
        if (($exc['date'] ?? '') === $todayStr) {
            if ($exc['closed'] ?? false) return false;
            return $time >= ($exc['open'] ?? '00:00') && $time < ($exc['close'] ?? '00:00');
        }
    }

    $dayMap = ['1' => 'mon','2' => 'tue','3' => 'wed','4' => 'thu','5' => 'fri','6' => 'sat','7' => 'sun'];
    $dayKey = $dayMap[$today] ?? '';

    foreach ($s['opening_hours'] ?? [] as $h) {
        if (($h['day'] ?? '') === $dayKey) {
            if ($h['closed'] ?? false) return false;
            return $time >= ($h['open'] ?? '00:00') && $time < ($h['close'] ?? '00:00');
        }
    }
    return false;
}

function dayLabel(string $key): string {
    $fi = ['mon'=>'Maanantai','tue'=>'Tiistai','wed'=>'Keskiviikko','thu'=>'Torstai','fri'=>'Perjantai','sat'=>'Lauantai','sun'=>'Sunnuntai'];
    $en = ['mon'=>'Monday','tue'=>'Tuesday','wed'=>'Wednesday','thu'=>'Thursday','fri'=>'Friday','sat'=>'Saturday','sun'=>'Sunday'];
    return lang() === 'fi' ? ($fi[$key] ?? $key) : ($en[$key] ?? $key);
}

function weekdayLabel(string $key): string {
    return dayLabel(strtolower(substr($key, 0, 3)));
}

function socialIcon(string $platform): string {
    $icons = [
        'instagram' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
        'tiktok' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
        'facebook' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'x' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
    ];
    return $icons[strtolower($platform)] ?? '';
}

function defaultSettings(): array {
    return [
        'title_fi' => 'Konttiravintola Waves',
        'title_en' => 'Container Restaurant Waves',
        'hero_text_fi' => 'Konttiravintola Jyväskylän satamassa',
        'hero_text_en' => 'Container restaurant at Jyväskylä harbor',
        'intro_fi' => '<p>Waves on Jyväskylän satamassa sijaitseva konttiravintola, joka tarjoaa kesäisiä makuja, kylmiä juomia ja upean järvimaiseman.</p><p>Tule nauttimaan auringosta, ruoasta ja hyvästä seurasta!</p>',
        'intro_en' => '<p>Waves is a container restaurant located at Jyväskylä harbor, offering summer flavors, cold drinks, and a beautiful lakeside setting.</p><p>Come enjoy the sun, food, and great company!</p>',
        'phone' => '',
        'email' => '',
        'address' => 'Satamakatu 2 B, 40100 Jyväskylä',
        'social_links' => [
            ['platform' => 'instagram', 'url' => 'https://www.instagram.com/waves_jyvaskyla/'],
            ['platform' => 'tiktok', 'url' => 'https://www.tiktok.com/@wavesjkl'],
            ['platform' => 'facebook', 'url' => 'https://www.facebook.com/wavesjyvaskyla'],
            ['platform' => 'x', 'url' => 'https://x.com/wavesjyvaskyla'],
        ],
        'opening_hours' => [
            ['day' => 'mon', 'open' => '', 'close' => '', 'kitchen_closes' => '', 'closed' => true, 'note' => ''],
            ['day' => 'tue', 'open' => '', 'close' => '', 'kitchen_closes' => '', 'closed' => true, 'note' => ''],
            ['day' => 'wed', 'open' => '', 'close' => '', 'kitchen_closes' => '', 'closed' => true, 'note' => ''],
            ['day' => 'thu', 'open' => '', 'close' => '', 'kitchen_closes' => '', 'closed' => true, 'note' => ''],
            ['day' => 'fri', 'open' => '14:00', 'close' => '19:00', 'kitchen_closes' => '20:00', 'closed' => false, 'note' => ''],
            ['day' => 'sat', 'open' => '12:00', 'close' => '19:00', 'kitchen_closes' => '20:00', 'closed' => false, 'note' => ''],
            ['day' => 'sun', 'open' => '', 'close' => '', 'kitchen_closes' => '', 'closed' => true, 'note' => ''],
        ],
        'opening_exceptions' => [],
        'seo_title_fi' => '',
        'seo_title_en' => '',
        'seo_description_fi' => '',
        'seo_description_en' => '',
    ];
}

function generateId(): string {
    return bin2hex(random_bytes(4));
}

function csrf(): string {
    ensureSessionStarted();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function checkCsrf(): void {
    ensureSessionStarted();
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch');
    }
}

function adminAuth(): void {
    ensureSessionStarted();
    if (empty($_SESSION['admin_username'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function adminCheck(): bool {
    ensureSessionStarted();
    return !empty($_SESSION['admin_username']);
}

function adminUsers(): array {
    $data = DataStore::load('admin');
    return $data['users'] ?? [];
}

function adminFindUser(string $username): ?array {
    foreach (adminUsers() as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    return null;
}

function adminAuthenticate(string $username, string $password): bool {
    $user = adminFindUser($username);
    if (!$user || empty($user['password_hash'])) {
        return false;
    }
    return password_verify($password, $user['password_hash']);
}

function adminSetPassword(string $username, string $plain): void {
    $data = DataStore::load('admin');
    $users = $data['users'] ?? [];
    $found = false;
    foreach ($users as &$user) {
        if ($user['username'] === $username) {
            $user['password_hash'] = password_hash($plain, PASSWORD_BCRYPT);
            $found = true;
            break;
        }
    }
    unset($user);
    if (!$found) {
        $users[] = [
            'username' => $username,
            'password_hash' => password_hash($plain, PASSWORD_BCRYPT),
            'created_at' => date('c'),
        ];
    }
    $data['users'] = $users;
    DataStore::save('admin', $data);
}

function adminAddUser(string $username, string $plain): bool {
    if (adminFindUser($username) !== null) {
        return false;
    }
    adminSetPassword($username, $plain);
    return true;
}

function adminDeleteUser(string $username): bool {
    $data = DataStore::load('admin');
    $users = $data['users'] ?? [];
    $users = array_values(array_filter($users, fn($u) => $u['username'] !== $username));
    if (count($users) === count($data['users'] ?? [])) {
        return false;
    }
    $data['users'] = $users;
    DataStore::save('admin', $data);
    return true;
}

function adminListUsernames(): array {
    return array_map(fn($u) => $u['username'], adminUsers());
}

function defaultMenuCategories(): array {
    return [
        ['id' => 'c1', 'title_fi' => 'Summer Tacos', 'title_en' => 'Summer Tacos', 'slug' => 'summer-tacos', 'sort_order' => 1],
        ['id' => 'c2', 'title_fi' => 'Burgers with Fries', 'title_en' => 'Burgers with Fries', 'slug' => 'burgers-with-fries', 'sort_order' => 2],
        ['id' => 'c3', 'title_fi' => 'Salads', 'title_en' => 'Salads', 'slug' => 'salads', 'sort_order' => 3],
        ['id' => 'c4', 'title_fi' => 'Snacks', 'title_en' => 'Snacks', 'slug' => 'snacks', 'sort_order' => 4],
        ['id' => 'c5', 'title_fi' => 'Kids', 'title_en' => 'Kids', 'slug' => 'kids', 'sort_order' => 5],
        ['id' => 'c6', 'title_fi' => 'Dips 2 €', 'title_en' => 'Dips 2 €', 'slug' => 'dips', 'sort_order' => 6],
    ];
}

function defaultMenuItems(): array {
    return [
        ['id' => 'm0', 'name_fi' => 'Kaikki tacot sisältävät', 'name_en' => 'All tacos include', 'description_fi' => '2 kevätsipulilettua, salaattia, chiliä, pikkelikaalia ja -sipulia, korianteria, kurkkua ja kuivattua sipulia.', 'description_en' => '2 spring onion pancakes, salad, chili, pickled cabbage and onion, coriander, cucumber and crispy onion.', 'price' => 0, 'category' => 'summer-tacos', 'dietary_tags' => '', 'visible' => true],
        ['id' => 'm1', 'name_fi' => 'KUHATAKUU', 'name_en' => 'KUHATAKUU', 'description_fi' => 'Rapeaa kuhaa, lime-korianterimajoneesia.', 'description_en' => 'Crispy pike perch, lime coriander mayo.', 'price' => 18, 'category' => 'summer-tacos', 'dietary_tags' => 'L', 'visible' => true],
        ['id' => 'm2', 'name_fi' => 'KANA', 'name_en' => 'CHICKEN', 'description_fi' => 'Rapeaa kanaa, yrttistä ranch-majoneesia.', 'description_en' => 'Crispy chicken, herby ranch mayo.', 'price' => 18, 'category' => 'summer-tacos', 'dietary_tags' => 'L', 'visible' => true],
        ['id' => 'm3', 'name_fi' => 'HALLOUMI', 'name_en' => 'HALLOUMI', 'description_fi' => 'Rapeaa halloumia, Louisiana-majoneesia.', 'description_en' => 'Crispy halloumi, Louisiana mayo.', 'price' => 18, 'category' => 'summer-tacos', 'dietary_tags' => 'VL', 'visible' => true],
        ['id' => 'm4', 'name_fi' => 'TOFU', 'name_en' => 'TOFU', 'description_fi' => 'Spicy garlic -tofua, sweet chili -majoneesia.', 'description_en' => 'Spicy garlic tofu, sweet chili mayo.', 'price' => 18, 'category' => 'summer-tacos', 'dietary_tags' => 'V', 'visible' => true],
        ['id' => 'm5', 'name_fi' => 'PORK O\'CLOCK', 'name_en' => 'PORK O\'CLOCK', 'description_fi' => 'Paahdettua possunkylkeä, savuista BBQ-kastiketta ja aiolia.', 'description_en' => 'Roasted pork belly, smoky BBQ sauce and aioli.', 'price' => 18, 'category' => 'summer-tacos', 'dietary_tags' => 'L', 'visible' => true],

        ['id' => 'm5b', 'name_fi' => 'Smash-burgerien pohja', 'name_en' => 'Smash burger base', 'description_fi' => '80 g rapea pihvi, salaattia, pikkelöityä sipulia, Myrttistä ja Jukolan cheddaria.', 'description_en' => '80 g crisp patty, salad, pickled onion, Myrttinen relish and Jukola cheddar.', 'price' => 0, 'category' => 'burgers-with-fries', 'dietary_tags' => '', 'visible' => true],
        ['id' => 'm6', 'name_fi' => 'SINGLE', 'name_en' => 'SINGLE', 'description_fi' => '1 pihvi, Louisiana-majoneesia.', 'description_en' => '1 patty, Louisiana mayo.', 'price' => 14.50, 'category' => 'burgers-with-fries', 'dietary_tags' => 'VL', 'visible' => true],
        ['id' => 'm7', 'name_fi' => 'DOUBLE', 'name_en' => 'DOUBLE', 'description_fi' => '2 pihviä, Louisiana-majoneesia.', 'description_en' => '2 patties, Louisiana mayo.', 'price' => 18.50, 'category' => 'burgers-with-fries', 'dietary_tags' => 'VL', 'visible' => true],
        ['id' => 'm8', 'name_fi' => 'CHORIZO', 'name_en' => 'CHORIZO', 'description_fi' => '2 pihviä, chorizoa ja ranch-majoneesia.', 'description_en' => '2 patties, chorizo and ranch mayo.', 'price' => 18.50, 'category' => 'burgers-with-fries', 'dietary_tags' => 'L', 'visible' => true],
        ['id' => 'm9', 'name_fi' => 'HANGOVER', 'name_en' => 'HANGOVER', 'description_fi' => '2 pihviä, pekonia, pikkelöityä jalapenoa, Auraa ja ranch-majoneesia.', 'description_en' => '2 patties, bacon, pickled jalapeno, Aura blue cheese and ranch mayo.', 'price' => 20, 'category' => 'burgers-with-fries', 'dietary_tags' => 'L', 'visible' => true],
        ['id' => 'm10', 'name_fi' => 'VEGGIE CLASH', 'name_en' => 'VEGGIE CLASH', 'description_fi' => 'Beyond Meat -pihvi, Jukolan cheddar ja sweet chili -majoneesi. Saatavilla myös vegaanisena.', 'description_en' => 'Beyond Meat patty, Jukola cheddar and sweet chili mayo. Also available vegan.', 'price' => 18.50, 'category' => 'burgers-with-fries', 'dietary_tags' => 'L', 'visible' => true],
        ['id' => 'm11', 'name_fi' => 'FISHERMAN', 'name_en' => 'FISHERMAN', 'description_fi' => 'Paneroitu kuhafilee, pikkelikaalia ja -sipulia, salaattia, Myrttistä ja ranch-majoneesia.', 'description_en' => 'Breaded pike perch fillet, pickled cabbage and onion, salad, Myrttinen relish and ranch mayo.', 'price' => 18.50, 'category' => 'burgers-with-fries', 'dietary_tags' => 'L', 'visible' => true],

        ['id' => 'm12', 'name_fi' => 'KANA TAI SAVULOHI', 'name_en' => 'CHICKEN OR SMOKED SALMON', 'description_fi' => 'Vinaigrettea, tomaattia, mummonkurkkua, pikkelöityä punasipulia ja punakaalia, guacamolea ja ranch-majoneesia.', 'description_en' => 'Vinaigrette, tomato, cucumber pickles, pickled red onion and cabbage, guacamole and ranch mayo.', 'price' => 18, 'category' => 'salads', 'dietary_tags' => 'L,G', 'visible' => true],

        ['id' => 'm13', 'name_fi' => 'PARMESAN FRIES + DIPPI', 'name_en' => 'PARMESAN FRIES + DIP', 'description_fi' => '', 'description_en' => '', 'price' => 8, 'category' => 'snacks', 'dietary_tags' => '', 'visible' => true],
        ['id' => 'm14', 'name_fi' => 'FLIPPED FRIES', 'name_en' => 'FLIPPED FRIES', 'description_fi' => 'Tomaattia, Auraa, pikkelöityä punasipulia, chiliä ja Louisiana-majoneesia.', 'description_en' => 'Tomato, Aura blue cheese, pickled red onion, chili and Louisiana mayo.', 'price' => 10, 'category' => 'snacks', 'dietary_tags' => 'VL,G', 'visible' => true],
        ['id' => 'm15', 'name_fi' => 'FLIPPED NACHOS', 'name_en' => 'FLIPPED NACHOS', 'description_fi' => 'Cheddar-kastiketta, pikkelöityä punasipulia ja jalapenoa, tomaattia, guacamolea ja ranch-majoneesia.', 'description_en' => 'Cheddar sauce, pickled red onion and jalapeno, tomato, guacamole and ranch mayo.', 'price' => 10, 'category' => 'snacks', 'dietary_tags' => 'L,G', 'visible' => true],

        ['id' => 'm16', 'name_fi' => 'SMASH + FRIES', 'name_en' => 'SMASH + FRIES', 'description_fi' => '1 rapea 80 g pihvi, Jukolan cheddar, ranch-majoneesi, ketsuppi ja salaatti.', 'description_en' => '1 crisp 80 g patty, Jukola cheddar, ranch mayo, ketchup and salad.', 'price' => 10, 'category' => 'kids', 'dietary_tags' => 'L', 'visible' => true],
        ['id' => 'm17', 'name_fi' => 'RANUT + DIPPI', 'name_en' => 'FRIES + DIP', 'description_fi' => '', 'description_en' => '', 'price' => 6, 'category' => 'kids', 'dietary_tags' => 'G,L,V', 'visible' => true],
        ['id' => 'm18', 'name_fi' => 'FISH & CHIPS', 'name_en' => 'FISH & CHIPS', 'description_fi' => 'Rapeaa kuhaa, ranskalaisia ja ranch-majoneesia.', 'description_en' => 'Crispy pike perch, fries and ranch mayo.', 'price' => 10, 'category' => 'kids', 'dietary_tags' => 'L', 'visible' => true],

        ['id' => 'm19', 'name_fi' => 'Korianteri-lime', 'name_en' => 'Coriander lime', 'description_fi' => '', 'description_en' => '', 'price' => 0, 'category' => 'dips', 'dietary_tags' => 'L,G', 'visible' => true],
        ['id' => 'm20', 'name_fi' => 'Sweet chili', 'name_en' => 'Sweet chili', 'description_fi' => '', 'description_en' => '', 'price' => 0, 'category' => 'dips', 'dietary_tags' => 'V,G', 'visible' => true],
        ['id' => 'm21', 'name_fi' => 'Louisiana', 'name_en' => 'Louisiana', 'description_fi' => '', 'description_en' => '', 'price' => 0, 'category' => 'dips', 'dietary_tags' => 'VL,G', 'visible' => true],
        ['id' => 'm22', 'name_fi' => 'Aioli', 'name_en' => 'Aioli', 'description_fi' => '', 'description_en' => '', 'price' => 0, 'category' => 'dips', 'dietary_tags' => 'L,G', 'visible' => true],
        ['id' => 'm23', 'name_fi' => 'Ranch', 'name_en' => 'Ranch', 'description_fi' => '', 'description_en' => '', 'price' => 0, 'category' => 'dips', 'dietary_tags' => 'L,G', 'visible' => true],
    ];
}

function optimizeImage(string $srcPath, string $destPath, int $maxDim = 2560, int $quality = 80): bool {
    if (!file_exists($srcPath)) return false;

    $info = getimagesize($srcPath);
    if ($info === false) return false;

    $mime = $info['mime'];
    $src = null;

    switch ($mime) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($srcPath);
            break;
        case 'image/png':
            $src = imagecreatefrompng($srcPath);
            break;
        case 'image/webp':
            $src = imagecreatefromwebp($srcPath);
            break;
        case 'image/avif':
            $src = imagecreatefromavif($srcPath);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($srcPath);
            break;
        default:
            return false;
    }

    if ($src === false) return false;

    $origW = imagesx($src);
    $origH = imagesy($src);

    if ($origW <= $maxDim && $origH <= $maxDim) {
        $newW = $origW;
        $newH = $origH;
    } else {
        $ratio = min($maxDim / $origW, $maxDim / $origH);
        $newW = (int) round($origW * $ratio);
        $newH = (int) round($origH * $ratio);
    }

    $dest = imagecreatetruecolor($newW, $newH);

    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);
    }

    imagecopyresampled($dest, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    $result = imageavif($dest, $destPath, $quality);

    imagedestroy($src);
    imagedestroy($dest);

    return $result !== false;
}

function esc(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function dietaryLabel(string $tag): string {
    $map = [
        'G' => t('Gluteeniton', 'Gluten-free'),
        'L' => t('Laktoositon', 'Lactose-free'),
        'VL' => t('Vähälaktoottinen', 'Low-lactose'),
        'V' => t('Vegaaninen', 'Vegan'),
        'M' => t('Maidoton', 'Dairy-free'),
    ];
    return $map[$tag] ?? $tag;
}
