<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
$_SESSION = [];
header('Location: /admin/login.php');
exit;
