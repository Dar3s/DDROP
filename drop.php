<?php
session_start();

if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/db.php';

function ddrop_fetch_ai_summary_from_remote(int $id): array
{
    $url = 'https://ddrop.net/api/generate_summary.php?id=' . $id . '&t=' . time();

    $context = stream_context_create([
        'http' => [
            'timeout' => 45,
            'ignore_errors' => true,
            'header' => "User-Agent: DDrop-App\r\nCache-Control: no-cache\r\nPragma: no-cache\r\n"
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return [
            'success' => false,
            'summary' => null,
            'error' => 'Nepodařilo se kontaktovat AI endpoint.'
        ];
    }

    $response = preg_replace('/^\xEF\xBB\xBF/', '', (string)$response);
    $response = trim($response);

    if ($response === '') {
        return [
            'success' => false,
            'summary' => null,
            'error' => 'AI endpoint vrátil prázdnou odpověď.'
        ];
    }

    $json = json_decode($response, true);

    if (is_array($json)) {
        if (!empty($json['summary']) && is_string($json['summary'])) {
            return [
                'success' => true,
                'summary' => trim($json['summary']),
                'error' => null
            ];
        }

        if (!empty($json['response']) && is_string($json['response'])) {
            return [
                'success' => true,
                'summary' => trim($json['response']),
                'error' => null
            ];
        }

        if (!empty($json['text']) && is_string($json['text'])) {
            return [
                'success' => true,
                'summary' => trim($json['text']),
                'error' => null
            ];
        }

        if (isset($json['success']) && $json['success'] === false) {
            return [
                'success' => false,
                'summary' => null,
                'error' => !empty($json['error']) ? (string)$json['error'] : 'AI endpoint vrátil chybu.'
            ];
        }
    }

    return [
        'success' => true,
        'summary' => $response,
        'error' => null
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_ai') {
    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Neplatné ID dropu.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM drops WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $drop = $stmt->fetch();

    if (!$drop) {
        echo json_encode([
            'success' => false,
            'error' => 'Drop nebyl nalezen.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = ddrop_fetch_ai_summary_from_remote($id);

    if (!$result['success'] || empty($result['summary'])) {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Nepodařilo se vygenerovat AI shrnutí.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $summary = trim($result['summary']);

    $update = $pdo->prepare("
        UPDATE drops
        SET ai_summary = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $update->execute([$summary, $id]);

    echo json_encode([
        'success' => true,
        'summary' => $summary
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die('Neplatné ID');
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
    <style>
        .ai-loading {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 24px;
        }

        .ai-loading-label {
            opacity: 0.9;
        }

        .ai-loading-dots {
            display: inline-flex;
            gap: 6px;
            align-items: center;
        }

        .ai-loading-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.9);
            animation: aiDots 1.2s infinite ease-in-out;
        }

        .ai-loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .ai-loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes aiDots {
            0%, 80%, 100% {
                transform: scale(0.65);
                opacity: 0.45;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .btn[disabled] {
            opacity: 0.7;
            pointer-events: none;
        }

        .ai-error {
            color: #ffd7d7;
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

                <p id="ai-summary-text">
                    <?= !empty($drop['ai_summary'])
                        ? nl2br(htmlspecialchars($drop['ai_summary']))
                        : 'Zatím nebylo vygenerováno.' ?>
                </p>

                <div style="margin-top: 18px;">
                    <button class="btn" id="generate-ai-btn" data-drop-id="<?= (int)$drop['id'] ?>" type="button">
                        Vygenerovat AI shrnutí
                    </button>
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

    <script>
        const aiButton = document.getElementById('generate-ai-btn');
        const aiSummaryText = document.getElementById('ai-summary-text');

        if (aiButton && aiSummaryText) {
            aiButton.addEventListener('click', async () => {
                const dropId = aiButton.getAttribute('data-drop-id');

                aiButton.disabled = true;
                aiButton.textContent = 'Generuji...';

                aiSummaryText.innerHTML = `
                    <span class="ai-loading">
                        <span class="ai-loading-label">Generuji AI shrnutí</span>
                        <span class="ai-loading-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </span>
                `;

                try {
                    const response = await fetch('drop.php?id=' + encodeURIComponent(dropId), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: new URLSearchParams({
                            action: 'generate_ai',
                            id: dropId
                        }).toString()
                    });

                    let rawText = await response.text();
                    rawText = rawText.replace(/^\uFEFF/, '').trim();

                    let data;

                    try {
                        data = JSON.parse(rawText);
                    } catch (e) {
                        data = {
                            success: true,
                            summary: rawText
                        };
                    }

                    if (!data.success) {
                        aiSummaryText.innerHTML = '<span class="ai-error">' + (data.error || 'Nepodařilo se vygenerovat AI shrnutí.') + '</span>';
                    } else {
                        aiSummaryText.textContent = data.summary || 'AI endpoint vrátil prázdnou odpověď.';
                    }
                } catch (error) {
                    aiSummaryText.innerHTML = '<span class="ai-error">Došlo k chybě při generování AI shrnutí.</span>';
                } finally {
                    aiButton.disabled = false;
                    aiButton.textContent = 'Vygenerovat AI shrnutí';
                }
            });
        }
    </script>
</body>
</html>
