<?php

class DataStore {
    private static function path(string $name): string {
        return DATA_DIR . '/' . $name . '.json';
    }

    public static function load(string $name): array {
        $path = self::path($name);
        if (!file_exists($path)) return [];
        $json = file_get_contents($path);
        return json_decode($json, true) ?: [];
    }

    public static function save(string $name, array $data): void {
        $path = self::path($name);
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public static function ensure(string $name, array $default): array {
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
    return '/assets/' . ltrim($path, '/');
}

function settings(): array {
    return DataStore::ensure('settings', defaultSettings());
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
        'intro_fi' => '<p>Waves on Jyväskylän satamassa sijaitseva konttiravintola, joka tarjoaa kesäisiä makuja, kylmiä juomia ja upean merellisen miljöön.</p><p>Tule nauttimaan auringosta, ruoasta ja hyvästä seurasta!</p>',
        'intro_en' => '<p>Waves is a container restaurant located at Jyväskylä harbor, offering summer flavors, cold drinks, and a beautiful maritime setting.</p><p>Come enjoy the sun, food, and great company!</p>',
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
            ['day' => 'mon', 'open' => '11:00', 'close' => '22:00', 'kitchen_closes' => '21:00', 'closed' => false, 'note' => ''],
            ['day' => 'tue', 'open' => '11:00', 'close' => '22:00', 'kitchen_closes' => '21:00', 'closed' => false, 'note' => ''],
            ['day' => 'wed', 'open' => '11:00', 'close' => '22:00', 'kitchen_closes' => '21:00', 'closed' => false, 'note' => ''],
            ['day' => 'thu', 'open' => '11:00', 'close' => '23:00', 'kitchen_closes' => '22:00', 'closed' => false, 'note' => ''],
            ['day' => 'fri', 'open' => '11:00', 'close' => '23:00', 'kitchen_closes' => '22:00', 'closed' => false, 'note' => ''],
            ['day' => 'sat', 'open' => '12:00', 'close' => '23:00', 'kitchen_closes' => '22:00', 'closed' => false, 'note' => ''],
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
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function checkCsrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch');
    }
}

function adminAuth(): void {
    if (empty($_SESSION['admin'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function adminCheck(): bool {
    return !empty($_SESSION['admin']);
}

function adminHash(): string {
    $admin = DataStore::ensure('admin', ['password_hash' => '']);
    return $admin['password_hash'] ?? '';
}

function adminSetPassword(string $plain): void {
    DataStore::save('admin', ['password_hash' => password_hash($plain, PASSWORD_BCRYPT)]);
}

function defaultMenuCategories(): array {
    return [
        ['id'=>'c1','title_fi'=>'Summer Tacos','title_en'=>'Summer Tacos','slug'=>'summer-tacos','sort_order'=>1],
        ['id'=>'c2','title_fi'=>'Burgers','title_en'=>'Burgers','slug'=>'burgers','sort_order'=>2],
        ['id'=>'c3','title_fi'=>'Saldet','title_en'=>'Salads','slug'=>'saldet','sort_order'=>3],
        ['id'=>'c4','title_fi'=>'Kids','title_en'=>'Kids','slug'=>'kids','sort_order'=>4],
        ['id'=>'c5','title_fi'=>'Ranut','title_en'=>'Fries','slug'=>'ranut','sort_order'=>5],
        ['id'=>'c6','title_fi'=>'Dipit','title_en'=>'Dips','slug'=>'dipit','sort_order'=>6],
    ];
}

function defaultMenuItems(): array {
    return [
        ['id'=>'m1','name_fi'=>'KUHATAKU','name_en'=>'KUHATAKU','description_fi'=>'Rapea kuha ja lime-korianterimajo','description_en'=>'Crispy zander and lime-cilantro mayo','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m2','name_fi'=>'KANA','name_en'=>'CHICKEN','description_fi'=>'Rapea kana ja paprikamajo','description_en'=>'Crispy chicken and pepper mayo','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m3','name_fi'=>'HALLOUMI','name_en'=>'HALLOUMI','description_fi'=>'Rapea halloumi ja sweet & chili -majo','description_en'=>'Crispy halloumi and sweet & chili mayo','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m4','name_fi'=>'TOFU','name_en'=>'TOFU','description_fi'=>'Spicy garlic tofu ja sweet & chili -majo','description_en'=>'Spicy garlic tofu and sweet & chili mayo','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'V','visible'=>true],
        ['id'=>'m5','name_fi'=>'PORK O\'CLOCK','name_en'=>'PORK O\'CLOCK','description_fi'=>'Possun kylkeä, BBQ-kastike ja aioli','description_en'=>'Pork belly, BBQ sauce and aioli','price'=>18,'category'=>'summer-tacos','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m6','name_fi'=>'SMASH Single','name_en'=>'SMASH Single','description_fi'=>'80g rapea pihvi, salde, pikkelisipuli, myrttinen, Juukolan cheddar ja paprikamajo','description_en'=>'80g crispy patty, salad, pickled onion, myrtle, Juukola cheddar and pepper mayo','price'=>14,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m7','name_fi'=>'SMASH Double','name_en'=>'SMASH Double','description_fi'=>'2 x 80g rapea pihvi, salde, pikkelisipuli, myrttinen, Juukolan cheddar ja paprikamajo','description_en'=>'2 x 80g crispy patty, salad, pickled onion, myrtle, Juukola cheddar and pepper mayo','price'=>18.50,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m8','name_fi'=>'Hangover','name_en'=>'Hangover','description_fi'=>'2 pihviä + pekoni, pikkelijalaopeno ja auramajo','description_en'=>'2 patties + bacon, pickled jalapeño and blue cheese mayo','price'=>20,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m9','name_fi'=>'VEGGIE CLASH','name_en'=>'VEGGIE CLASH','description_fi'=>'Beyond meat -pihvi, Juukolan cheddar ja sweet & chili -majo','description_en'=>'Beyond meat patty, Juukola cheddar and sweet & chili mayo','price'=>18.50,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m10','name_fi'=>'FISHERMAN','name_en'=>'FISHERMAN','description_fi'=>'Sandwich kuhafile, pikkelikaali ja sipuli, salde, myrttinen ja lime-korianterimajo','description_en'=>'Sandwich zander fillet, pickled cabbage and onion, salad, myrtle and lime-cilantro mayo','price'=>18.50,'category'=>'burgers','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m11','name_fi'=>'KANA-VUOHENJUUSTO','name_en'=>'CHICKEN-GOAT CHEESE','description_fi'=>'Lemon vinaigrette ja jalapenohillo','description_en'=>'Lemon vinaigrette and jalapeño jam','price'=>18,'category'=>'saldet','dietary_tags'=>'VL,G','visible'=>true],
        ['id'=>'m12','name_fi'=>'SPICY GARLIC TOFU','name_en'=>'SPICY GARLIC TOFU','description_fi'=>'Lemon vinaigrette, balsamico ja paahdettu saksanpähkinä','description_en'=>'Lemon vinaigrette, balsamic and roasted walnut','price'=>18,'category'=>'saldet','dietary_tags'=>'V,G','visible'=>true],
        ['id'=>'m13','name_fi'=>'SMASH Kids','name_en'=>'SMASH Kids','description_fi'=>'1 rapea 80g pihvi, Juukolan cheddar, paprikamajo, ketsuppi ja salde. Ranut.','description_en'=>'1 crispy 80g patty, Juukola cheddar, pepper mayo, ketchup and salad. Fries.','price'=>10,'category'=>'kids','dietary_tags'=>'L','visible'=>true],
        ['id'=>'m14','name_fi'=>'RANUT + DIPPI','name_en'=>'FRIES + DIP','description_fi'=>'','description_en'=>'','price'=>6,'category'=>'kids','dietary_tags'=>'G,L,V','visible'=>true],
        ['id'=>'m15','name_fi'=>'PARMESAANIRANUT','name_en'=>'PARMESAN FRIES','description_fi'=>'Sisältää dipin','description_en'=>'Includes dip','price'=>8,'category'=>'ranut','dietary_tags'=>'','visible'=>true],
        ['id'=>'m16','name_fi'=>'TUUNATUT RANUT','name_en'=>'LOADED FRIES','description_fi'=>'Aurajuusto, pikkelijalaopeno ja sipuli, paprikamajo sekä kuivattu sipuli','description_en'=>'Blue cheese, pickled jalapeño and onion, pepper mayo and dried onion','price'=>10,'category'=>'ranut','dietary_tags'=>'G,L','visible'=>true],
        ['id'=>'m17','name_fi'=>'DIPIT','name_en'=>'DIPS','description_fi'=>'Korianteri-limemajo, Sweet & chili -majo, Paprikamajo, Auramajo, BBQ-sauce, Aioli','description_en'=>'Cilantro-lime mayo, Sweet & chili mayo, Pepper mayo, Blue cheese mayo, BBQ sauce, Aioli','price'=>2,'category'=>'dipit','dietary_tags'=>'','visible'=>true],
    ];
}

function esc(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
