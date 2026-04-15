<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user'])) {
    header('Location: account.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $passwordConfirm === '') {
        $error = 'Vyplň prosím všechna pole.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Zadej validní email.';
    } elseif (strlen($password) < 6) {
        $error = 'Heslo musí mít alespoň 6 znaků.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Hesla se neshodují.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $error = 'Uživatel s tímto jménem nebo emailem už existuje.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$username, $email, $passwordHash]);

            $success = 'Registrace proběhla úspěšně. Teď se můžeš přihlásit.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrace | DDrop</title>
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
        <a class="nav-link active" href="register.php">Registrace</a>
    </div>
</nav>

<section class="auth-page">
    <div class="auth-card">
        <h1>Registrace</h1>
        <p>Vytvoř si svůj DDrop účet.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Uživatelské jméno</label>
                <input class="form-input" type="text" name="username" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-input" type="email" name="email" required>
            </div>

            <div class="form-group">
                <label class="form-label">Heslo</label>
                <input class="form-input" type="password" name="password" required>
            </div>

            <div class="form-group">
                <label class="form-label">Potvrzení hesla</label>
                <input class="form-input" type="password" name="password_confirm" required>
            </div>

            <button class="btn" type="submit">Registrovat se</button>
        </form>

        <div class="auth-switch">
            <p>Už účet máte?</p>
            <a class="btn-outline" href="login.php">Přihlaste se</a>
        </div>
    </div>

    <div class="info-card">
        <h2>Co účet odemkne</h2>
        <ul class="account-list">
            <li>Ukládání oblíbených dropů</li>
            <li>Přehled watchlistů</li>
            <li>Marketplace funkce</li>
            <li>AI funkce a personalizaci</li>
        </ul>
    </div>
</section>

</body>
</html>
