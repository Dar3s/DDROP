<?php
session_start();
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query("SELECT * FROM drops ORDER BY release_date ASC");
$drops = $stmt->fetchAll();

$isLoggedIn = isset($_SESSION['user']);
$username = $isLoggedIn ? $_SESSION['user']['username'] : null;
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Drops | DDrop</title>
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
            <a class="nav-link active" href="index.php">Domů</a>
            <a class="nav-link" href="index.php#drops-section">Drops</a>

            <?php if ($isLoggedIn): ?>
                <a class="nav-link" href="account.php">Můj účet</a>
                <a class="nav-link" href="#">Watchlist</a>
                <a class="nav-link" href="#">Marketplace</a>
                <a class="nav-link" href="logout.php">Odhlásit</a>
            <?php else: ?>
                <a class="nav-link" href="login.php">Přihlášení</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero">
        <h1>Upcoming Drops</h1>
        <p>Sleduj releasy, porovnávej retail a buduj si vlastní DDrop účet pro další funkce.</p>
    </section>

    <div class="drops" id="drops-section">
        <?php foreach ($drops as $drop): ?>
            <div class="card">
                <img
                    src="<?= htmlspecialchars($drop['image_url'] ?: 'assets/img/placeholder.png') ?>"
                    alt="<?= htmlspecialchars($drop['title']) ?>"
                    onerror="this.src='assets/img/placeholder.png'"
                >

                <h2><?= htmlspecialchars($drop['title']) ?></h2>

                <p><strong>Značka:</strong> <?= htmlspecialchars($drop['brand'] ?? 'Neuvedeno') ?></p>
                <p><strong>Datum:</strong> <?= htmlspecialchars($drop['release_date'] ?? 'Neuvedeno') ?></p>
                <p><strong>Retail:</strong> <?= htmlspecialchars($drop['retail_price']) ?> <?= htmlspecialchars($drop['currency'] ?? 'EUR') ?></p>

                <a class="btn" href="drop.php?id=<?= (int)$drop['id'] ?>">Detail</a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
