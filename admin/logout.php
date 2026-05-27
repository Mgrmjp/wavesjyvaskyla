<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
destroyCurrentSession();
header('Location: /admin/login.php');
exit;
