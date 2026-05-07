<?php

final class AppStore
{
    private static ?PDO $pdo = null;
    private const MIGRATED_NAMES = ['settings', 'notices', 'menu', 'lunch', 'events', 'gallery', 'admin'];

    public static function isMigrated(string $name): bool
    {
        return in_array($name, self::MIGRATED_NAMES, true);
    }

    public static function databasePath(): string
    {
        $envPath = trim((string) getenv('APP_DB_PATH'));
        if ($envPath !== '') {
            return $envPath;
        }

        if (defined('DATA_DIR')) {
            return DATA_DIR . '/waves.sqlite';
        }

        return ROOT . '/data/waves.sqlite';
    }

    public static function load(string $name): array
    {
        return match ($name) {
            'settings' => self::loadSettings(),
            'notices' => self::loadNotices(),
            'menu' => self::loadMenu(),
            'lunch' => self::loadLunch(),
            'events' => self::loadEvents(),
            'gallery' => self::loadGallery(),
            'admin' => self::loadAdminUsers(),
            default => throw new InvalidArgumentException('Unsupported store: ' . $name),
        };
    }

    public static function save(string $name, array $data): void
    {
        match ($name) {
            'settings' => self::saveSettings($data),
            'notices' => self::saveNotices($data),
            'menu' => self::saveMenu($data),
            'lunch' => self::saveLunch($data),
            'events' => self::saveEvents($data),
            'gallery' => self::saveGallery($data),
            'admin' => self::saveAdminUsers($data),
            default => throw new InvalidArgumentException('Unsupported store: ' . $name),
        };
    }

    public static function ensureMigrated(): void
    {
        self::connection();
    }

    public static function migrateJsonToSqlite(bool $force = false): void
    {
        $pdo = self::connection();
        if (!$force && self::meta('json_imported_at') !== null) {
            return;
        }

        self::importLegacyJson($pdo);
    }

    private static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException(
                'SQLite storage requires the pdo_sqlite PHP extension. ' .
                'Set APP_DB_PATH if needed and enable pdo_sqlite in the runtime.'
            );
        }

        $path = self::databasePath();
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create SQLite directory: ' . $dir);
        }

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        self::createSchema($pdo);
        self::$pdo = $pdo;

        if (self::meta('json_imported_at') === null) {
            self::importLegacyJson($pdo);
        }

        return self::$pdo;
    }

    private static function createSchema(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_meta (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                singleton_id INTEGER PRIMARY KEY CHECK (singleton_id = 1),
                title_fi TEXT NOT NULL DEFAULT "",
                title_en TEXT NOT NULL DEFAULT "",
                hero_text_fi TEXT NOT NULL DEFAULT "",
                hero_text_en TEXT NOT NULL DEFAULT "",
                intro_fi TEXT NOT NULL DEFAULT "",
                intro_en TEXT NOT NULL DEFAULT "",
                phone TEXT NOT NULL DEFAULT "",
                email TEXT NOT NULL DEFAULT "",
                address TEXT NOT NULL DEFAULT "",
                seo_title_fi TEXT NOT NULL DEFAULT "",
                seo_title_en TEXT NOT NULL DEFAULT "",
                seo_description_fi TEXT NOT NULL DEFAULT "",
                seo_description_en TEXT NOT NULL DEFAULT ""
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS social_links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                row_order INTEGER NOT NULL,
                platform TEXT NOT NULL DEFAULT "",
                url TEXT NOT NULL DEFAULT ""
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS opening_hours (
                day TEXT PRIMARY KEY,
                row_order INTEGER NOT NULL,
                open_time TEXT NOT NULL DEFAULT "",
                close_time TEXT NOT NULL DEFAULT "",
                kitchen_closes TEXT NOT NULL DEFAULT "",
                is_closed INTEGER NOT NULL DEFAULT 0,
                note TEXT NOT NULL DEFAULT ""
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS opening_exceptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                row_order INTEGER NOT NULL,
                date TEXT NOT NULL DEFAULT "",
                is_closed INTEGER NOT NULL DEFAULT 0,
                open_time TEXT NOT NULL DEFAULT "",
                close_time TEXT NOT NULL DEFAULT "",
                note_fi TEXT NOT NULL DEFAULT "",
                note_en TEXT NOT NULL DEFAULT ""
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS notices (
                id TEXT PRIMARY KEY,
                row_order INTEGER NOT NULL,
                text_fi TEXT NOT NULL DEFAULT "",
                text_en TEXT NOT NULL DEFAULT "",
                is_active INTEGER NOT NULL DEFAULT 0,
                start_date TEXT NOT NULL DEFAULT "",
                end_date TEXT NOT NULL DEFAULT "",
                style TEXT NOT NULL DEFAULT "info"
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS menu_categories (
                id TEXT PRIMARY KEY,
                row_order INTEGER NOT NULL,
                title_fi TEXT NOT NULL DEFAULT "",
                title_en TEXT NOT NULL DEFAULT "",
                slug TEXT NOT NULL DEFAULT "",
                sort_order INTEGER NOT NULL DEFAULT 0
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS menu_items (
                id TEXT PRIMARY KEY,
                row_order INTEGER NOT NULL,
                name_fi TEXT NOT NULL DEFAULT "",
                name_en TEXT NOT NULL DEFAULT "",
                description_fi TEXT NOT NULL DEFAULT "",
                description_en TEXT NOT NULL DEFAULT "",
                price REAL NOT NULL DEFAULT 0,
                category TEXT NOT NULL DEFAULT "",
                dietary_tags TEXT NOT NULL DEFAULT "",
                is_visible INTEGER NOT NULL DEFAULT 0,
                image TEXT NOT NULL DEFAULT "",
                updated_at TEXT NOT NULL DEFAULT ""
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS lunch_items (
                id TEXT PRIMARY KEY,
                row_order INTEGER NOT NULL,
                weekday TEXT NOT NULL DEFAULT "",
                name_fi TEXT NOT NULL DEFAULT "",
                name_en TEXT NOT NULL DEFAULT "",
                description_fi TEXT NOT NULL DEFAULT "",
                description_en TEXT NOT NULL DEFAULT "",
                price REAL NOT NULL DEFAULT 0,
                dietary_tags TEXT NOT NULL DEFAULT "",
                is_visible INTEGER NOT NULL DEFAULT 0
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS events (
                id TEXT PRIMARY KEY,
                row_order INTEGER NOT NULL,
                title_fi TEXT NOT NULL DEFAULT "",
                title_en TEXT NOT NULL DEFAULT "",
                date TEXT NOT NULL DEFAULT "",
                start_time TEXT NOT NULL DEFAULT "",
                end_time TEXT NOT NULL DEFAULT "",
                description_fi TEXT NOT NULL DEFAULT "",
                description_en TEXT NOT NULL DEFAULT "",
                is_visible INTEGER NOT NULL DEFAULT 0,
                is_featured INTEGER NOT NULL DEFAULT 0,
                link TEXT NOT NULL DEFAULT "",
                location TEXT NOT NULL DEFAULT ""
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS gallery_images (
                id TEXT PRIMARY KEY,
                row_order INTEGER NOT NULL,
                filename TEXT NOT NULL DEFAULT "",
                caption_fi TEXT NOT NULL DEFAULT "",
                caption_en TEXT NOT NULL DEFAULT "",
                alt_fi TEXT NOT NULL DEFAULT "",
                alt_en TEXT NOT NULL DEFAULT "",
                is_visible INTEGER NOT NULL DEFAULT 1,
                added TEXT NOT NULL DEFAULT ""
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS admin_users (
                username TEXT PRIMARY KEY,
                password_hash TEXT NOT NULL DEFAULT "",
                created_at TEXT NOT NULL DEFAULT ""
            )'
        );
    }

    private static function meta(string $key): ?string
    {
        $stmt = self::$pdo?->prepare('SELECT value FROM app_meta WHERE key = :key');
        if ($stmt === null) {
            return null;
        }

        $stmt->execute([':key' => $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    private static function setMeta(PDO $pdo, string $key, string $value): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO app_meta (key, value) VALUES (:key, :value)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value'
        );
        $stmt->execute([':key' => $key, ':value' => $value]);
    }

    private static function importLegacyJson(PDO $pdo): void
    {
        $payloads = [
            'settings' => self::readLegacyJson('settings'),
            'notices' => self::readLegacyJson('notices'),
            'menu' => self::readLegacyJson('menu'),
            'lunch' => self::readLegacyJson('lunch'),
            'events' => self::readLegacyJson('events'),
            'gallery' => self::readLegacyJson('gallery'),
            'admin' => self::readLegacyJson('admin'),
        ];

        $pdo->beginTransaction();
        try {
            foreach ($payloads as $name => $payload) {
                if ($payload === []) {
                    continue;
                }
                self::save($name, $payload);
            }
            self::setMeta($pdo, 'json_imported_at', date('c'));
            $pdo->commit();
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $throwable;
        }
    }

    private static function readLegacyJson(string $name): array
    {
        $path = DATA_DIR . '/' . $name . '.json';
        if (!is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function loadSettings(): array
    {
        $pdo = self::connection();
        $stmt = $pdo->query('SELECT * FROM settings WHERE singleton_id = 1');
        $row = $stmt->fetch() ?: [];

        $settings = [];
        if ($row !== []) {
            $settings = [
                'title_fi' => (string) $row['title_fi'],
                'title_en' => (string) $row['title_en'],
                'hero_text_fi' => (string) $row['hero_text_fi'],
                'hero_text_en' => (string) $row['hero_text_en'],
                'intro_fi' => (string) $row['intro_fi'],
                'intro_en' => (string) $row['intro_en'],
                'phone' => (string) $row['phone'],
                'email' => (string) $row['email'],
                'address' => (string) $row['address'],
                'seo_title_fi' => (string) $row['seo_title_fi'],
                'seo_title_en' => (string) $row['seo_title_en'],
                'seo_description_fi' => (string) $row['seo_description_fi'],
                'seo_description_en' => (string) $row['seo_description_en'],
            ];
        }

        $settings['social_links'] = self::fetchAll(
            'SELECT platform, url FROM social_links ORDER BY row_order ASC, id ASC',
            static fn(array $item): array => [
                'platform' => (string) $item['platform'],
                'url' => (string) $item['url'],
            ]
        );
        $settings['opening_hours'] = self::fetchAll(
            'SELECT day, open_time, close_time, kitchen_closes, is_closed, note
             FROM opening_hours ORDER BY row_order ASC, day ASC',
            static fn(array $item): array => [
                'day' => (string) $item['day'],
                'open' => (string) $item['open_time'],
                'close' => (string) $item['close_time'],
                'kitchen_closes' => (string) $item['kitchen_closes'],
                'closed' => (bool) $item['is_closed'],
                'note' => (string) $item['note'],
            ]
        );
        $settings['opening_exceptions'] = self::fetchAll(
            'SELECT date, is_closed, open_time, close_time, note_fi, note_en
             FROM opening_exceptions ORDER BY row_order ASC, id ASC',
            static fn(array $item): array => [
                'date' => (string) $item['date'],
                'closed' => (bool) $item['is_closed'],
                'open' => (string) $item['open_time'],
                'close' => (string) $item['close_time'],
                'note_fi' => (string) $item['note_fi'],
                'note_en' => (string) $item['note_en'],
            ]
        );

        if ($row === [] && $settings['social_links'] === [] && $settings['opening_hours'] === [] && $settings['opening_exceptions'] === []) {
            return [];
        }

        return $settings;
    }

    private static function saveSettings(array $settings): void
    {
        $pdo = self::connection();
        self::transactional($pdo, static function () use ($pdo, $settings): void {
            $stmt = $pdo->prepare(
                'INSERT INTO settings (
                    singleton_id, title_fi, title_en, hero_text_fi, hero_text_en, intro_fi, intro_en,
                    phone, email, address, seo_title_fi, seo_title_en, seo_description_fi, seo_description_en
                ) VALUES (
                    1, :title_fi, :title_en, :hero_text_fi, :hero_text_en, :intro_fi, :intro_en,
                    :phone, :email, :address, :seo_title_fi, :seo_title_en, :seo_description_fi, :seo_description_en
                )
                ON CONFLICT(singleton_id) DO UPDATE SET
                    title_fi = excluded.title_fi,
                    title_en = excluded.title_en,
                    hero_text_fi = excluded.hero_text_fi,
                    hero_text_en = excluded.hero_text_en,
                    intro_fi = excluded.intro_fi,
                    intro_en = excluded.intro_en,
                    phone = excluded.phone,
                    email = excluded.email,
                    address = excluded.address,
                    seo_title_fi = excluded.seo_title_fi,
                    seo_title_en = excluded.seo_title_en,
                    seo_description_fi = excluded.seo_description_fi,
                    seo_description_en = excluded.seo_description_en'
            );
            $stmt->execute([
                ':title_fi' => (string) ($settings['title_fi'] ?? ''),
                ':title_en' => (string) ($settings['title_en'] ?? ''),
                ':hero_text_fi' => (string) ($settings['hero_text_fi'] ?? ''),
                ':hero_text_en' => (string) ($settings['hero_text_en'] ?? ''),
                ':intro_fi' => (string) ($settings['intro_fi'] ?? ''),
                ':intro_en' => (string) ($settings['intro_en'] ?? ''),
                ':phone' => (string) ($settings['phone'] ?? ''),
                ':email' => (string) ($settings['email'] ?? ''),
                ':address' => (string) ($settings['address'] ?? ''),
                ':seo_title_fi' => (string) ($settings['seo_title_fi'] ?? ''),
                ':seo_title_en' => (string) ($settings['seo_title_en'] ?? ''),
                ':seo_description_fi' => (string) ($settings['seo_description_fi'] ?? ''),
                ':seo_description_en' => (string) ($settings['seo_description_en'] ?? ''),
            ]);

            $pdo->exec('DELETE FROM social_links');
            $socialStmt = $pdo->prepare(
                'INSERT INTO social_links (row_order, platform, url) VALUES (:row_order, :platform, :url)'
            );
            foreach (array_values($settings['social_links'] ?? []) as $index => $link) {
                $socialStmt->execute([
                    ':row_order' => $index,
                    ':platform' => (string) ($link['platform'] ?? ''),
                    ':url' => (string) ($link['url'] ?? ''),
                ]);
            }

            $pdo->exec('DELETE FROM opening_hours');
            $hoursStmt = $pdo->prepare(
                'INSERT INTO opening_hours (day, row_order, open_time, close_time, kitchen_closes, is_closed, note)
                 VALUES (:day, :row_order, :open_time, :close_time, :kitchen_closes, :is_closed, :note)'
            );
            foreach (array_values($settings['opening_hours'] ?? []) as $index => $hour) {
                $hoursStmt->execute([
                    ':day' => (string) ($hour['day'] ?? ''),
                    ':row_order' => $index,
                    ':open_time' => (string) ($hour['open'] ?? ''),
                    ':close_time' => (string) ($hour['close'] ?? ''),
                    ':kitchen_closes' => (string) ($hour['kitchen_closes'] ?? ''),
                    ':is_closed' => !empty($hour['closed']) ? 1 : 0,
                    ':note' => (string) ($hour['note'] ?? ''),
                ]);
            }

            $pdo->exec('DELETE FROM opening_exceptions');
            $exceptionsStmt = $pdo->prepare(
                'INSERT INTO opening_exceptions (
                    row_order, date, is_closed, open_time, close_time, note_fi, note_en
                 ) VALUES (
                    :row_order, :date, :is_closed, :open_time, :close_time, :note_fi, :note_en
                 )'
            );
            foreach (array_values($settings['opening_exceptions'] ?? []) as $index => $exception) {
                $exceptionsStmt->execute([
                    ':row_order' => $index,
                    ':date' => (string) ($exception['date'] ?? ''),
                    ':is_closed' => !empty($exception['closed']) ? 1 : 0,
                    ':open_time' => (string) ($exception['open'] ?? ''),
                    ':close_time' => (string) ($exception['close'] ?? ''),
                    ':note_fi' => (string) ($exception['note_fi'] ?? ''),
                    ':note_en' => (string) ($exception['note_en'] ?? ''),
                ]);
            }
        });
    }

    private static function loadNotices(): array
    {
        $notices = [
            'notices' => self::fetchAll(
                'SELECT id, text_fi, text_en, is_active, start_date, end_date, style
                 FROM notices ORDER BY row_order ASC, id ASC',
                static fn(array $item): array => [
                    'id' => (string) $item['id'],
                    'text_fi' => (string) $item['text_fi'],
                    'text_en' => (string) $item['text_en'],
                    'active' => (bool) $item['is_active'],
                    'start_date' => (string) $item['start_date'],
                    'end_date' => (string) $item['end_date'],
                    'style' => (string) $item['style'],
                ]
            ),
        ];

        return $notices['notices'] === [] ? [] : $notices;
    }

    private static function saveNotices(array $data): void
    {
        $pdo = self::connection();
        self::transactional($pdo, static function () use ($pdo, $data): void {
            $pdo->exec('DELETE FROM notices');
            $stmt = $pdo->prepare(
                'INSERT INTO notices (
                    id, row_order, text_fi, text_en, is_active, start_date, end_date, style
                 ) VALUES (
                    :id, :row_order, :text_fi, :text_en, :is_active, :start_date, :end_date, :style
                 )'
            );
            foreach (array_values($data['notices'] ?? []) as $index => $notice) {
                $stmt->execute([
                    ':id' => (string) ($notice['id'] ?? ''),
                    ':row_order' => $index,
                    ':text_fi' => (string) ($notice['text_fi'] ?? ''),
                    ':text_en' => (string) ($notice['text_en'] ?? ''),
                    ':is_active' => !empty($notice['active']) ? 1 : 0,
                    ':start_date' => (string) ($notice['start_date'] ?? ''),
                    ':end_date' => (string) ($notice['end_date'] ?? ''),
                    ':style' => (string) ($notice['style'] ?? 'info'),
                ]);
            }
        });
    }

    private static function loadMenu(): array
    {
        $menu = [
            'categories' => self::fetchAll(
                'SELECT id, title_fi, title_en, slug, sort_order
                 FROM menu_categories ORDER BY row_order ASC, id ASC',
                static fn(array $item): array => [
                    'id' => (string) $item['id'],
                    'title_fi' => (string) $item['title_fi'],
                    'title_en' => (string) $item['title_en'],
                    'slug' => (string) $item['slug'],
                    'sort_order' => (int) $item['sort_order'],
                ]
            ),
            'items' => self::fetchAll(
                'SELECT id, name_fi, name_en, description_fi, description_en, price, category,
                        dietary_tags, is_visible, image, updated_at
                 FROM menu_items ORDER BY row_order ASC, id ASC',
                static fn(array $item): array => [
                    'id' => (string) $item['id'],
                    'name_fi' => (string) $item['name_fi'],
                    'name_en' => (string) $item['name_en'],
                    'description_fi' => (string) $item['description_fi'],
                    'description_en' => (string) $item['description_en'],
                    'price' => (float) $item['price'],
                    'category' => (string) $item['category'],
                    'dietary_tags' => (string) $item['dietary_tags'],
                    'visible' => (bool) $item['is_visible'],
                    'image' => (string) $item['image'],
                    'updated_at' => (string) $item['updated_at'],
                ]
            ),
        ];

        return $menu['categories'] === [] && $menu['items'] === [] ? [] : $menu;
    }

    private static function saveMenu(array $data): void
    {
        $pdo = self::connection();
        self::transactional($pdo, static function () use ($pdo, $data): void {
            $pdo->exec('DELETE FROM menu_categories');
            $categoriesStmt = $pdo->prepare(
                'INSERT INTO menu_categories (id, row_order, title_fi, title_en, slug, sort_order)
                 VALUES (:id, :row_order, :title_fi, :title_en, :slug, :sort_order)'
            );
            foreach (array_values($data['categories'] ?? []) as $index => $category) {
                $categoriesStmt->execute([
                    ':id' => (string) ($category['id'] ?? ''),
                    ':row_order' => $index,
                    ':title_fi' => (string) ($category['title_fi'] ?? ''),
                    ':title_en' => (string) ($category['title_en'] ?? ''),
                    ':slug' => (string) ($category['slug'] ?? ''),
                    ':sort_order' => (int) ($category['sort_order'] ?? 0),
                ]);
            }

            $pdo->exec('DELETE FROM menu_items');
            $itemsStmt = $pdo->prepare(
                'INSERT INTO menu_items (
                    id, row_order, name_fi, name_en, description_fi, description_en, price,
                    category, dietary_tags, is_visible, image, updated_at
                 ) VALUES (
                    :id, :row_order, :name_fi, :name_en, :description_fi, :description_en, :price,
                    :category, :dietary_tags, :is_visible, :image, :updated_at
                 )'
            );
            foreach (array_values($data['items'] ?? []) as $index => $item) {
                $itemsStmt->execute([
                    ':id' => (string) ($item['id'] ?? ''),
                    ':row_order' => $index,
                    ':name_fi' => (string) ($item['name_fi'] ?? ''),
                    ':name_en' => (string) ($item['name_en'] ?? ''),
                    ':description_fi' => (string) ($item['description_fi'] ?? ''),
                    ':description_en' => (string) ($item['description_en'] ?? ''),
                    ':price' => (float) ($item['price'] ?? 0),
                    ':category' => (string) ($item['category'] ?? ''),
                    ':dietary_tags' => (string) ($item['dietary_tags'] ?? ''),
                    ':is_visible' => !empty($item['visible']) ? 1 : 0,
                    ':image' => (string) ($item['image'] ?? ''),
                    ':updated_at' => (string) ($item['updated_at'] ?? ''),
                ]);
            }
        });
    }

    private static function loadLunch(): array
    {
        $lunch = [
            'items' => self::fetchAll(
                'SELECT id, weekday, name_fi, name_en, description_fi, description_en, price,
                        dietary_tags, is_visible
                 FROM lunch_items ORDER BY row_order ASC, id ASC',
                static fn(array $item): array => [
                    'id' => (string) $item['id'],
                    'weekday' => (string) $item['weekday'],
                    'name_fi' => (string) $item['name_fi'],
                    'name_en' => (string) $item['name_en'],
                    'description_fi' => (string) $item['description_fi'],
                    'description_en' => (string) $item['description_en'],
                    'price' => (float) $item['price'],
                    'dietary_tags' => (string) $item['dietary_tags'],
                    'visible' => (bool) $item['is_visible'],
                ]
            ),
        ];

        return $lunch['items'] === [] ? [] : $lunch;
    }

    private static function saveLunch(array $data): void
    {
        $pdo = self::connection();
        self::transactional($pdo, static function () use ($pdo, $data): void {
            $pdo->exec('DELETE FROM lunch_items');
            $stmt = $pdo->prepare(
                'INSERT INTO lunch_items (
                    id, row_order, weekday, name_fi, name_en, description_fi, description_en,
                    price, dietary_tags, is_visible
                 ) VALUES (
                    :id, :row_order, :weekday, :name_fi, :name_en, :description_fi, :description_en,
                    :price, :dietary_tags, :is_visible
                 )'
            );
            foreach (array_values($data['items'] ?? []) as $index => $item) {
                $stmt->execute([
                    ':id' => (string) ($item['id'] ?? ''),
                    ':row_order' => $index,
                    ':weekday' => (string) ($item['weekday'] ?? ''),
                    ':name_fi' => (string) ($item['name_fi'] ?? ''),
                    ':name_en' => (string) ($item['name_en'] ?? ''),
                    ':description_fi' => (string) ($item['description_fi'] ?? ''),
                    ':description_en' => (string) ($item['description_en'] ?? ''),
                    ':price' => (float) ($item['price'] ?? 0),
                    ':dietary_tags' => (string) ($item['dietary_tags'] ?? ''),
                    ':is_visible' => !empty($item['visible']) ? 1 : 0,
                ]);
            }
        });
    }

    private static function loadEvents(): array
    {
        $events = [
            'events' => self::fetchAll(
                'SELECT id, title_fi, title_en, date, start_time, end_time, description_fi,
                        description_en, is_visible, is_featured, link, location
                 FROM events ORDER BY row_order ASC, id ASC',
                static fn(array $item): array => [
                    'id' => (string) $item['id'],
                    'title_fi' => (string) $item['title_fi'],
                    'title_en' => (string) $item['title_en'],
                    'date' => (string) $item['date'],
                    'start_time' => (string) $item['start_time'],
                    'end_time' => (string) $item['end_time'],
                    'description_fi' => (string) $item['description_fi'],
                    'description_en' => (string) $item['description_en'],
                    'visible' => (bool) $item['is_visible'],
                    'featured' => (bool) $item['is_featured'],
                    'link' => (string) $item['link'],
                    'location' => (string) $item['location'],
                ]
            ),
        ];

        return $events['events'] === [] ? [] : $events;
    }

    private static function saveEvents(array $data): void
    {
        $pdo = self::connection();
        self::transactional($pdo, static function () use ($pdo, $data): void {
            $pdo->exec('DELETE FROM events');
            $stmt = $pdo->prepare(
                'INSERT INTO events (
                    id, row_order, title_fi, title_en, date, start_time, end_time,
                    description_fi, description_en, is_visible, is_featured, link, location
                 ) VALUES (
                    :id, :row_order, :title_fi, :title_en, :date, :start_time, :end_time,
                    :description_fi, :description_en, :is_visible, :is_featured, :link, :location
                 )'
            );
            foreach (array_values($data['events'] ?? []) as $index => $event) {
                $stmt->execute([
                    ':id' => (string) ($event['id'] ?? ''),
                    ':row_order' => $index,
                    ':title_fi' => (string) ($event['title_fi'] ?? ''),
                    ':title_en' => (string) ($event['title_en'] ?? ''),
                    ':date' => (string) ($event['date'] ?? ''),
                    ':start_time' => (string) ($event['start_time'] ?? ''),
                    ':end_time' => (string) ($event['end_time'] ?? ''),
                    ':description_fi' => (string) ($event['description_fi'] ?? ''),
                    ':description_en' => (string) ($event['description_en'] ?? ''),
                    ':is_visible' => !empty($event['visible']) ? 1 : 0,
                    ':is_featured' => !empty($event['featured']) ? 1 : 0,
                    ':link' => (string) ($event['link'] ?? ''),
                    ':location' => (string) ($event['location'] ?? ''),
                ]);
            }
        });
    }

    private static function loadGallery(): array
    {
        return self::fetchAll(
            'SELECT id, filename, caption_fi, caption_en, alt_fi, alt_en, is_visible, added
             FROM gallery_images ORDER BY row_order ASC, id ASC',
            static fn(array $item): array => [
                'id' => (string) $item['id'],
                'filename' => (string) $item['filename'],
                'caption_fi' => (string) $item['caption_fi'],
                'caption_en' => (string) $item['caption_en'],
                'alt_fi' => (string) $item['alt_fi'],
                'alt_en' => (string) $item['alt_en'],
                'visible' => (bool) $item['is_visible'],
                'added' => (string) $item['added'],
            ]
        );
    }

    private static function saveGallery(array $gallery): void
    {
        $pdo = self::connection();
        self::transactional($pdo, static function () use ($pdo, $gallery): void {
            $pdo->exec('DELETE FROM gallery_images');
            $stmt = $pdo->prepare(
                'INSERT INTO gallery_images (
                    id, row_order, filename, caption_fi, caption_en, alt_fi, alt_en, is_visible, added
                 ) VALUES (
                    :id, :row_order, :filename, :caption_fi, :caption_en, :alt_fi, :alt_en, :is_visible, :added
                 )'
            );
            foreach (array_values($gallery) as $index => $image) {
                $stmt->execute([
                    ':id' => (string) ($image['id'] ?? ''),
                    ':row_order' => $index,
                    ':filename' => (string) ($image['filename'] ?? ''),
                    ':caption_fi' => (string) ($image['caption_fi'] ?? ''),
                    ':caption_en' => (string) ($image['caption_en'] ?? ''),
                    ':alt_fi' => (string) ($image['alt_fi'] ?? ''),
                    ':alt_en' => (string) ($image['alt_en'] ?? ''),
                    ':is_visible' => !empty($image['visible']) ? 1 : 0,
                    ':added' => (string) ($image['added'] ?? ''),
                ]);
            }
        });
    }

    private static function loadAdminUsers(): array
    {
        return [
            'users' => self::fetchAll(
                'SELECT username, password_hash, created_at FROM admin_users ORDER BY created_at ASC, username ASC',
                static fn(array $item): array => [
                    'username' => (string) $item['username'],
                    'password_hash' => (string) $item['password_hash'],
                    'created_at' => (string) $item['created_at'],
                ]
            ),
        ];
    }

    private static function saveAdminUsers(array $data): void
    {
        $pdo = self::connection();
        self::transactional($pdo, static function () use ($pdo, $data): void {
            $pdo->exec('DELETE FROM admin_users');
            $stmt = $pdo->prepare(
                'INSERT INTO admin_users (username, password_hash, created_at)
                 VALUES (:username, :password_hash, :created_at)'
            );
            foreach (array_values($data['users'] ?? []) as $user) {
                $stmt->execute([
                    ':username' => (string) ($user['username'] ?? ''),
                    ':password_hash' => (string) ($user['password_hash'] ?? ''),
                    ':created_at' => (string) ($user['created_at'] ?? ''),
                ]);
            }
        });
    }

    private static function fetchAll(string $sql, callable $mapper): array
    {
        $pdo = self::connection();
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
        return array_map($mapper, $rows);
    }

    private static function transactional(PDO $pdo, callable $callback): void
    {
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $callback();
            if ($startedTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $throwable) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $throwable;
        }
    }
}
