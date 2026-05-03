<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

if (adminCheck()) {
    header('Location: /admin/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    $hash = adminHash();
    if (empty($hash)) {
        adminSetPassword($pass);
        $_SESSION['admin'] = true;
        header('Location: /admin/');
        exit;
    }
    if (password_verify($pass, $hash)) {
        $_SESSION['admin'] = true;
        header('Location: /admin/');
        exit;
    }
    $error = 'Väärä salasana / Wrong password';
}
?>
<!DOCTYPE html>
<html lang="fi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — Waves</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,-apple-system,sans-serif;background:#f5f5f5;margin:0;padding:0;display:flex;align-items:center;justify-content:center;min-height:100vh}
.login-box{background:#fff;padding:2rem;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);width:100%;max-width:360px}
h1{margin:0 0 1.5rem;font-size:1.5rem;color:#004B7C}
input{width:100%;padding:0.75rem;margin-bottom:1rem;border:1px solid #ddd;border-radius:6px;font-size:1rem}
button{width:100%;padding:0.75rem;background:#0088C2;color:#fff;border:none;border-radius:6px;font-size:1rem;font-weight:600;cursor:pointer}
button:hover{background:#004B7C}
.error{color:#dc2626;font-size:0.875rem;margin-bottom:1rem}
p.note{font-size:0.8rem;color:#666;margin-top:1rem}
</style>
</head>
<body>
<div class="login-box">
    <h1>Waves Admin</h1>
    <?php if ($error): ?><div class="error"><?= esc($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="password" name="password" placeholder="Salasana / Password" required autofocus>
        <button type="submit">Kirjaudu / Login</button>
    </form>
    <?php if (empty(adminHash())): ?>
    <p class="note">Aseta salasana ensimmäisellä kirjautumisella.<br>Set password on first login.</p>
    <?php endif; ?>
</div>
</body>
</html>
