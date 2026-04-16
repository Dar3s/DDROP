<?php
session_start();
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die("Neplatné ID");
}

if (isset($_GET['generate_ai']) && $_GET['generate_ai'] === '1') {
    $summary = ddrop_fetch_ai_summary($id);

    if ($summary !== null) {
        $update = $pdo->prepare("UPDATE drops SET ai_summary = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$summary, $id]);
    }

    header('Location: drop.php?id=' . $id);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM drops WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$drop = $stmt->fetch();

if (!$drop) {
    die('Drop nebyl nalezen.');
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail dropu | DDrop</title>
    <link rel="stylesheet" href="/assets/css/styla.css">
    <link rel="shortcut icon" href="/assets/img/logo.ico">
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

    <section class="hero drop-hero">
        <h1><?= htmlspecialchars($drop['title'] ?? 'Bez názvu') ?></h1>
    </section>

    <div class="drops drops-single">
        <div class="card">
            <img
                src="<?= htmlspecialchars(!empty($drop['image_url']) ? $drop['image_url'] : 'assets/img/placeholder.png') ?>"
                alt="<?= htmlspecialchars($drop['title'] ?? 'Produkt') ?>"
                onerror="this.src='assets/img/placeholder.png'"
            >

            <p><strong>Značka:</strong> <?= htmlspecialchars($drop['brand'] ?? 'Neuvedeno') ?></p>
            <p><strong>Model:</strong> <?= htmlspecialchars($drop['model'] ?? 'Neuvedeno') ?></p>
            <p><strong>SKU:</strong> <?= htmlspecialchars($drop['sku'] ?? 'Neuvedeno') ?></p>
            <p><strong>Colorway:</strong> <?= htmlspecialchars($drop['colorway'] ?? 'Neuvedeno') ?></p>
            <p><strong>Datum dropu:</strong> <?= htmlspecialchars($drop['release_date'] ?? 'Neuvedeno') ?></p>
            <p><strong>Retail cena:</strong> <?= htmlspecialchars($drop['retail_price'] ?? 'Neuvedeno') ?> <?= htmlspecialchars($drop['currency'] ?? 'EUR') ?></p>
            <p><strong>Store:</strong> <?= htmlspecialchars($drop['store_name'] ?? 'Neuvedeno') ?></p>

            <?php if (!empty($drop['description'])): ?>
                <p><?= nl2br(htmlspecialchars($drop['description'])) ?></p>
            <?php endif; ?>

            <div class="info-card" style="margin-top: 22px;">
                <h2>AI shrnutí</h2>
                <p><?= !empty($drop['ai_summary']) ? nl2br(htmlspecialchars($drop['ai_summary'])) : 'Zatím nebylo vygenerováno.' ?></p>

                <div style="margin-top: 18px;">
                    <a class="btn" href="drop.php?id=<?= (int)$drop['id'] ?>&generate_ai=1">
                        Vygenerovat AI shrnutí
                    </a>
                </div>
            </div>

            <div class="drop-actions">
                <?php if (!empty($drop['store_link'])): ?>
                    <a class="btn" href="<?= htmlspecialchars($drop['store_link']) ?>" target="_blank" rel="noopener noreferrer">
                        Přejít na store
                    </a>
                <?php endif; ?>

                <a class="btn btn-outline" href="index.php">Zpět</a>
            </div>
        </div>
    </div>
</body>
</html>
