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
    SELECT
        w.id AS watch_id,
        w.target_price,
        w.currency AS watch_currency,
        d.id AS drop_id,
        d.title,
        d.brand,
        d.retail_price,
        d.currency AS drop_currency,
        d.release_date
    FROM watchlist w
    JOIN drops d ON d.id = w.drop_id
    WHERE w.user_id = :user_id
    ORDER BY w.id DESC
");
$stmt->execute([':user_id' => $user['id']]);
$watchlistItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Můj účet | DDrop</title>
    <link rel="icon" type="image/x-icon" href="/assets/img/logo.ico">
    <link rel="stylesheet" href="/assets/css/styla.css">
    <style>
        .watch-item-meta {
            opacity: 0.9;
            margin-top: 8px;
            line-height: 1.5;
        }

        .watch-item-link {
            display: inline-block;
            margin-top: 10px;
        }
    </style>
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
                    <h3>Price watch</h3>
                    <p class="stat-value-number"><?= $watchCount ?></p>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h2>Price watchlist z databáze</h2>

            <?php if (!$watchlistItems): ?>
                <p>
                    Watchlist je zatím prázdný.
                    Cílovou cenu nastavíš v detailu konkrétního dropu.
                </p>
            <?php else: ?>
                <ul class="account-list">
                    <?php foreach ($watchlistItems as $item): ?>
                        <li>
                            <strong><?= htmlspecialchars($item['title']) ?></strong>

                            <div class="watch-item-meta">
                                Retail:
                                <?= htmlspecialchars($item['retail_price']) ?>
                                <?= htmlspecialchars($item['drop_currency']) ?>
                                <br>

                                Cílová cena:
                                <strong>
                                    <?= htmlspecialchars($item['target_price']) ?>
                                    <?= htmlspecialchars($item['watch_currency']) ?>
                                </strong>
                            </div>

                            <a class="watch-item-link" href="drop.php?id=<?= (int)$item['drop_id'] ?>">
                                Otevřít detail
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
