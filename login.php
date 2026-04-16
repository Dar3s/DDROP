<?php
session_start();
require_once __DIR__ . '/bootstrap.php';

if (isset($_SESSION['user'])) {
    header('Location: account.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Vyplň prosím email i heslo.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Uživatel neexistuje.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = 'Špatné heslo.';
        } else {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ];

            header('Location: account.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení | DDrop</title>
    <link rel="icon" type="image/x-icon" href="assets/img/logo.ico">
    <link rel="stylesheet" href="assets/css/styla.css">
</head>
<body>
    <div class="glass-shards">
        <div class="shard shard-1"></div>
        <div class="shard shard-2"></div>
        <div class="shard shard-3"></div>
        <div class="shard shard-4"></div>
        <div class="shard shard-5"></div>
        <div class="shard shard-6"></div>
        <div class="shard shard-7"></div>
        <div class="shard shard-8"></div>
    </div>

    <div class="glass-cracks"></div>
    <div class="bg-lightning"></div>

    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php">DDrop</a>
        </div>

        <div class="navbar-menu">
            <a class="nav-link" href="index.php">Domů</a>
            <a class="nav-link" href="index.php#drops-section">Drops</a>
            <a class="nav-link active" href="login.php">Přihlášení</a>
        </div>
    </nav>

    <section class="auth-page">
        <div class="auth-card">
            <h1>Přihlášení</h1>
            <p>Přihlas se do svého DDrop účtu.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input class="form-input" type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Heslo</label>
                    <input class="form-input" type="password" name="password" required>
                </div>

                <button class="btn" type="submit">Přihlásit se</button>
            </form>

            <div class="auth-switch">
                <p>Nemáte účet?</p>
                <a class="btn-outline" href="register.php">Zaregistrujte se</a>
            </div>
        </div>

        <div class="info-card">
            <h2>Co účet odemkne</h2>
            <ul class="account-list">
                <li>Ukládání oblíbených dropů</li>
                <li>Personalizované řazení podle preferencí</li>
                <li>Marketplace profil a správa nabídek</li>
                <li>Přehled watchlistů a notifikací</li>
            </ul>
        </div>
    </section>
</body>
</html>
