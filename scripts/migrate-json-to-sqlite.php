<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('INCLUDES_DIR', ROOT . '/includes');
define('TEMPLATES_DIR', ROOT . '/templates');
define('ADMIN_DIR', ROOT . '/admin');

require_once INCLUDES_DIR . '/bootstrap.php';
require_once INCLUDES_DIR . '/functions.php';

AppStore::migrateJsonToSqlite(true);

fwrite(STDOUT, "SQLite migration complete: " . AppStore::databasePath() . PHP_EOL);
