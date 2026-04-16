<?php
session_start();
require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'Přihlášení je zde zatím jen jako vizuální prototyp.';
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
            <p>Tato stránka je zatím pouze vizuální prototyp.</p>

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
        </div>

        <div class="info-card">
            <h2>Prototyp</h2>
            <ul class="account-list">
                <li>Přihlášení je zde jen pro vizuální ukázku</li>
                <li>Hlavní důraz je na drops a AI shrnutí</li>
                <li>Databáze běží na PostgreSQL</li>
            </ul>
        </div>
    </section>
</body>
</html>
