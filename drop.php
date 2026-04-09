<?php
session_start();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die("Neplatné ID");
}


$url = "https://ddrop.net/api/drops.php?id=" . $id;

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


$response = preg_replace('/^\xEF\xBB\xBF/', '', $response);


$data = json_decode($response, true);

if (!is_array($data)) {
    die("JSON ERROR: " . json_last_error_msg());
}


$drop = isset($data[0]) ? $data[0] : $data;

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

    <style>
        .ai-box { margin-top: 22px; }
        .ai-loading { display: flex; gap: 6px; align-items: center; margin-top: 10px; }
        .ai-dot { width: 8px; height: 8px; background: white; border-radius: 50%; animation: bounce 1.2s infinite; }
        .ai-dot:nth-child(2) { animation-delay: 0.2s; }
        .ai-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); opacity: 0.3; }
            40% { transform: scale(1); opacity: 1; }
        }
        .ai-text { margin-top: 10px; white-space: pre-line; }
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

<section class="hero drop-hero">
    <h1><?= htmlspecialchars($drop['title']) ?></h1>
</section>

<div class="drops drops-single">
    <div class="card">

        <img
            src="<?= htmlspecialchars($drop['image_url'] ?: 'assets/img/placeholder.png') ?>"
            alt="<?= htmlspecialchars($drop['title']) ?>"
            onerror="this.src='assets/img/placeholder.png'"
        >

        <p><strong>Značka:</strong> <?= htmlspecialchars($drop['brand'] ?: 'Neuvedeno') ?></p>
        <p><strong>Model:</strong> <?= htmlspecialchars($drop['model'] ?: 'Neuvedeno') ?></p>
        <p><strong>SKU:</strong> <?= htmlspecialchars($drop['sku'] ?: 'Neuvedeno') ?></p>
        <p><strong>Colorway:</strong> <?= htmlspecialchars($drop['colorway'] ?: 'Neuvedeno') ?></p>
        <p><strong>Datum dropu:</strong> <?= htmlspecialchars($drop['release_date'] ?: 'Neuvedeno') ?></p>
        <p><strong>Retail cena:</strong> <?= htmlspecialchars($drop['retail_price']) ?> <?= htmlspecialchars($drop['currency'] ?: 'EUR') ?></p>
        <p><strong>Store:</strong> <?= htmlspecialchars($drop['store_name'] ?: 'Neuvedeno') ?></p>

        <?php if (!empty($drop['description'])): ?>
            <p><?= nl2br(htmlspecialchars($drop['description'])) ?></p>
        <?php endif; ?>

        <div class="info-card ai-box">
            <h2>AI shrnutí</h2>

            <button class="btn" id="ai-btn" onclick="generateSummary()">
                Vygenerovat
            </button>

            <div id="ai-loading" class="ai-loading" style="display:none;">
                <div class="ai-dot"></div>
                <div class="ai-dot"></div>
                <div class="ai-dot"></div>
            </div>

            <div id="ai-text" class="ai-text">
                Klikni na tlačítko pro vygenerování...
            </div>
        </div>

        <div class="drop-actions">
            <?php if (!empty($drop['store_link'])): ?>
                <a class="btn" href="<?= htmlspecialchars($drop['store_link']) ?>" target="_blank">
                    Přejít na store
                </a>
            <?php endif; ?>

            <a class="btn btn-outline" href="index.php">Zpět</a>
        </div>

    </div>
</div>

<script>
function generateSummary() {
    const btn = document.getElementById('ai-btn');
    const loading = document.getElementById('ai-loading');
    const text = document.getElementById('ai-text');

    btn.disabled = true;
    loading.style.display = 'flex';
    text.innerText = '';

    fetch('https://ddrop.net/api/generate_summary.php?id=<?= $drop['id'] ?>')
        .then(res => res.text()) 
        .then(raw => {
            
            raw = raw.replace(/^\uFEFF/, '');

            let data;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                throw new Error("Neplatný JSON: " + raw);
            }

            loading.style.display = 'none';
            btn.disabled = false;

            if (data.error) {
                text.innerText = 'Chyba: ' + data.error;
                return;
            }

            text.innerText = data.summary || 'Žádný výstup';
        })
        .catch(err => {
            loading.style.display = 'none';
            btn.disabled = false;
            text.innerText = 'Chyba při načítání';
            console.error(err);
        });
}
</script>

</body>
</html>
