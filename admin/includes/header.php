<?php
require_once __DIR__ . '/../includes/functions.php';
adminAuth();
$s = settings();
?>
<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? 'Admin') ?> — Waves</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;margin:0;padding:0;color:#0f172a}
.admin-header{background:#004B7C;color:#fff;padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between}
.admin-header h1{margin:0;font-size:1.25rem}
.admin-header nav a{color:#fff/80;text-decoration:none;margin-left:1.5rem;font-size:0.875rem}
.admin-header nav a:hover{color:#fff}
.container{max-width:1000px;margin:0 auto;padding:2rem}
.card{background:#fff;border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.1)}
.card h2{margin-top:0;font-size:1.25rem;color:#004B7C}
.form-group{margin-bottom:1rem}
label{display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.25rem}
input,textarea,select{width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;font-size:0.875rem;font-family:inherit}
textarea{min-height:80px;resize:vertical}
button[type=submit]{background:#0088C2;color:#fff;border:none;padding:0.6rem 1.2rem;border-radius:6px;font-size:0.875rem;font-weight:600;cursor:pointer}
button[type=submit]:hover{background:#004B7C}
.btn-danger{background:#dc2626 !important}
.btn-danger:hover{background:#991b1b !important}
table{width:100%;border-collapse:collapse;font-size:0.875rem}
th,td{padding:0.5rem;text-align:left;border-bottom:1px solid #e5e7eb}
th{font-weight:600;color:#374151}
tr:hover{background:#f9fafb}
.alert{background:#dcfce7;border:1px solid #86efac;color:#166534;padding:0.75rem;border-radius:6px;margin-bottom:1rem}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:640px){.grid-2{grid-template-columns:1fr}}
.nav-link{display:inline-block;padding:0.4rem 0.8rem;background:#f1f5f9;border-radius:6px;text-decoration:none;color:#334155;font-size:0.875rem;margin-right:0.5rem;margin-bottom:0.5rem}
.nav-link:hover{background:#e2e8f0}
.nav-link.active{background:#0088C2;color:#fff}
.mb-1{margin-bottom:0.25rem}.mb-2{margin-bottom:0.5rem}.mb-4{margin-bottom:1rem}
.text-sm{font-size:0.875rem}.text-xs{font-size:0.75rem}.text-gray{color:#6b7280}
.flex{display:flex}.items-center{align-items:center}.justify-between{justify-content:space-between}
.gap-2{gap:0.5rem}
</style>
</head>
<body>
<header class="admin-header">
    <h1>Waves Admin</h1>
    <nav>
        <a href="/admin/">Dashboard</a>
        <a href="/admin/settings.php">Asetukset</a>
        <a href="/admin/notices.php">Ilmoitukset</a>
        <a href="/admin/hours.php">Aukioloajat</a>
        <a href="/admin/menu.php">Menu</a>
        <a href="/admin/lunch.php">Lounas</a>
        <a href="/admin/events.php">Tapahtumat</a>
        <a href="/admin/gallery.php">Kuvat</a>
        <a href="/admin/logout.php">Kirjaudu ulos</a>
    </nav>
</header>
<div class="container">
