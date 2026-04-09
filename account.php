<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

$url = "https://ddrop.net/api/account_stats.php?user_id=" . $user['id'];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0'
]);

$response = curl_exec($ch);

if ($response === false) {
    die("CURL ERROR: " . curl_error($ch));
}

curl_close($ch);


$response = trim($response);
$response = preg_replace('/^\xEF\xBB\xBF/', '', $response);
$response = ltrim($response, "\xEF\xBB\xBF");

$data = json_decode($response, true);

if (!is_array($data)) {
    die("JSON ERROR: " . json_last_error_msg());
}

// fallback když API něco nedá
$dropCount = $data['dropCount'] ?? 0;
$watchCount = $data['watchCount'] ?? 0;
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Můj účet | DDrop</title>
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
            <a class="nav-link active" href="account.php">Můj účet</a>
            <a class="nav-link" href="#">Watchlist</a>
            <a class="nav-link" href="#">Marketplace</a>
            <a class="nav-link" href="logout.php">Odhlásit</a>
        </div>
    </nav>

    <section class="account-page">
        <div class="account-card">
            <h1>Vítej, <?= htmlspecialchars($user['username']) ?></h1>
            <p>Tohle je základ tvého DDrop profilu.</p>

            <div class="account-grid account-grid-compact">
                <div class="stat-box stat-box-wide">
                    <h3>Email</h3>
                    <p class="stat-value-email"><?= htmlspecialchars($user['email']) ?></p>
                </div>

                <div class="stat-box">
                    <h3>Watch záznamy</h3>
                    <p class="stat-value-number"><?= $watchCount ?></p>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h2>Další logické funkce</h2>
            <ul class="account-list">
                <li>Oblíbené dropy navázané na účet</li>
                <li>Historie price alertů</li>
                <li>Marketplace dashboard</li>
                <li>Souhrn dropů: <strong><?= $dropCount ?></strong></li>
            </ul>
        </div>
    </section>
</body>
</html>
