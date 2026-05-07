<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('INCLUDES_DIR', ROOT . '/includes');
define('TEMPLATES_DIR', ROOT . '/templates');
define('ADMIN_DIR', ROOT . '/admin');

require_once INCLUDES_DIR . '/bootstrap.php';
require_once INCLUDES_DIR . '/functions.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line." . PHP_EOL);
    exit(1);
}

$username = trim((string) ($argv[1] ?? ''));
$password = (string) ($argv[2] ?? '');

if ($username === '' || $password === '') {
    fwrite(STDERR, "Usage: php scripts/create-admin-user.php <username> <password>" . PHP_EOL);
    exit(1);
}

if (adminFindUser($username) !== null) {
    adminSetPassword($username, $password);
    fwrite(STDOUT, "Updated password for admin user: {$username}" . PHP_EOL);
    exit(0);
}

adminAddUser($username, $password);
fwrite(STDOUT, "Created admin user: {$username}" . PHP_EOL);
