<?php
session_start();

define('ROOT', __DIR__);
define('DATA_DIR', ROOT . '/data');
define('INCLUDES_DIR', ROOT . '/includes');
define('TEMPLATES_DIR', ROOT . '/templates');
define('ADMIN_DIR', ROOT . '/admin');

require_once INCLUDES_DIR . '/functions.php';

$router = new Router();
$router->dispatch();
