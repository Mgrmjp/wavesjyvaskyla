<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';
adminAuth();

$title = 'Käyttäjät';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');

    if ($action === 'add') {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        if (empty($username) || empty($password)) {
            $message = 'Käyttäjätunnus ja salasana vaaditaan';
            $messageType = 'error';
        } elseif ($password !== $passwordConfirm) {
            $message = 'Salasanat eivät täsmää';
            $messageType = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Salanan tulee olla vähintään 6 merkkiä';
            $messageType = 'error';
        } elseif (adminAddUser($username, $password)) {
            $message = "Käyttäjä '$username' lisätty";
            $messageType = 'success';
        } else {
            $message = "Käyttäjä '$username' on jo olemassa";
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $currentUsername = $_SESSION['admin_username'] ?? '';
        if ($username === $currentUsername) {
            $message = 'Et voi poistaa omaa käyttäätunnustasi';
            $messageType = 'error';
        } elseif (adminDeleteUser($username)) {
            $message = "Käyttäjä '$username' poistettu";
            $messageType = 'success';
        } else {
            $message = "Käyttäjää '$username' ei löydy";
            $messageType = 'error';
        }
    } elseif ($action === 'change_password') {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        if (empty($password)) {
            $message = 'Salasana vaaditaan';
            $messageType = 'error';
        } elseif ($password !== $passwordConfirm) {
            $message = 'Salasanat eivät täsmää';
            $messageType = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Salanan tulee olla vähintään 6 merkkiä';
            $messageType = 'error';
        } else {
            adminSetPassword($username, $password);
            $message = "Käyttäjän '$username' salasana vaihdettu";
            $messageType = 'success';
        }
    }
}

$users = adminUsers();
$currentUsername = $_SESSION['admin_username'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($message): ?>
<div class="alert alert-<?= esc($messageType) ?>"><?= esc($message) ?></div>
<?php endif; ?>

<section class="admin-card">
    <h2>Lisää käyttäjä</h2>
    <form method="post" class="form-inline">
        <input type="hidden" name="action" value="add">
        <input type="text" name="username" placeholder="Käyttäjätunnus" required>
        <input type="password" name="password" placeholder="Salasana" required>
        <input type="password" name="password_confirm" placeholder="Toista salasana" required>
        <button type="submit" class="btn btn-primary">Lisää</button>
    </form>
</section>

<section class="admin-card">
    <h2>Käyttäjät (<?= count($users) ?>)</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Käyttäjätunnus</th>
                <th>Luotu</th>
                <th>Toiminnot</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td>
                    <?= esc($user['username']) ?>
                    <?php if ($user['username'] === $currentUsername): ?>
                    <span class="badge">(sinä)</span>
                    <?php endif; ?>
                </td>
                <td><?= esc($user['created_at'] ?? '—') ?></td>
                <td class="actions">
                    <button class="btn btn-sm btn-secondary" onclick="togglePasswordForm('<?= esc($user['username']) ?>')">Vaihda salasana</button>
                    <?php if ($user['username'] !== $currentUsername): ?>
                    <form method="post" class="inline-form" onsubmit="return confirm('Poista käyttäjä <?= esc($user['username']) ?>?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="username" value="<?= esc($user['username']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Poista</button>
                    </form>
                    <?php endif; ?>
                    <div id="password-form-<?= esc($user['username']) ?>" class="password-form" style="display:none;">
                        <form method="post">
                            <input type="hidden" name="action" value="change_password">
                            <input type="hidden" name="username" value="<?= esc($user['username']) ?>">
                            <input type="password" name="password" placeholder="Uusi salasana" required>
                            <input type="password" name="password_confirm" placeholder="Toista salasana" required>
                            <button type="submit" class="btn btn-sm btn-primary">Vaihda</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<script>
function togglePasswordForm(username) {
    const form = document.getElementById('password-form-' + username);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
