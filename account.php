<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

$dropCount = (int)$pdo->query("SELECT COUNT(*) FROM drops")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM watchlist
    WHERE user_id = :user_id
");
$stmt->execute([':user_id' => $user['id']]);
$watchCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT d.*
    FROM watchlist w
    JOIN drops d ON d.id = w.drop_id
    WHERE w.user_id = :user_id
    ORDER BY w.created_at DESC
");
$stmt->execute([':user_id' => $user['id']]);
$watchlistDrops = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Můj účet | DDrop</title>
    <link rel="icon" type="image/x-icon" href="/assets/img/logo.ico">
    <link rel="stylesheet" href="/assets/css/styla.css">
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
            <a class="nav-link active" href="account.php">Můj účet</a>
            <a class="nav-link" href="logout.php">Odhlásit</a>
        </div>
    </nav>

    <section class="account-page">
        <div class="account-card">
            <h1>Vítej, <?= htmlspecialchars($user['username']) ?></h1>
            <p>Tato stránka ukazuje data načtená z PostgreSQL.</p>

            <div class="account-grid account-grid-compact">
                <div class="stat-box stat-box-wide">
                    <h3>Email</h3>
                    <p class="stat-value-email"><?= htmlspecialchars($user['email']) ?></p>
                </div>

                <div class="stat-box">
                    <h3>Dropy</h3>
                    <p class="stat-value-number"><?= $dropCount ?></p>
                </div>

                <div class="stat-box">
                    <h3>Watchlist</h3>
                    <p class="stat-value-number"><?= $watchCount ?></p>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h2>Watchlist z databáze</h2>

            <?php if (!$watchlistDrops): ?>
                <p>Watchlist je zatím prázdný. Přidání probíhá přes detail konkrétního dropu.</p>
            <?php else: ?>
                <ul class="account-list">
                    <?php foreach ($watchlistDrops as $drop): ?>
                        <li><?= htmlspecialchars($drop['title']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
