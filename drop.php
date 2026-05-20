<?php
session_start();
require_once __DIR__ . '/db.php';

function ddrop_build_ai_prompt(array $drop): string
{
    return "Napiš krátké české shrnutí sneaker dropu maximálně ve 3 větách. "
        . "Nepiš jistý zisk, jen opatrně zhodnoť, jestli je drop zajímavý pro sledování nebo osobní koupi.\n\n"
        . "Název: " . ($drop['title'] ?? 'Neuvedeno') . "\n"
        . "Značka: " . ($drop['brand'] ?? 'Neuvedeno') . "\n"
        . "Model: " . ($drop['model'] ?? 'Neuvedeno') . "\n"
        . "SKU: " . ($drop['sku'] ?? 'Neuvedeno') . "\n"
        . "Colorway: " . ($drop['colorway'] ?? 'Neuvedeno') . "\n"
        . "Retail cena: " . ($drop['retail_price'] ?? 'Neuvedeno') . " " . ($drop['currency'] ?? '') . "\n"
        . "Store: " . ($drop['store_name'] ?? 'Neuvedeno') . "\n"
        . "Popis: " . ($drop['description'] ?? 'Bez popisu');
}

function ddrop_generate_ai_summary(array $drop): array
{
    $baseUrl = rtrim((string)getenv('OPENAI_BASE_URL'), '/');
    $apiKey = (string)getenv('OPENAI_API_KEY');
    $model = (string)(getenv('OPENAI_MODEL') ?: 'gemma3:27b');

    if ($baseUrl === '' || $apiKey === '') {
        return [
            'success' => false,
            'summary' => null,
            'error' => 'AI není nakonfigurovaná. Chybí OPENAI_BASE_URL nebo OPENAI_API_KEY.'
        ];
    }

    $prompt = ddrop_build_ai_prompt($drop);

    $payload = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Jsi AI pomocník pro sneaker dropy. Odpovídej česky, stručně a realisticky.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.5,
        'max_tokens' => 220
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 60,
            'ignore_errors' => true,
            'header' => implode("\r\n", [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ]),
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]
    ]);

    $response = @file_get_contents($baseUrl . '/chat/completions', false, $context);

    if ($response === false) {
        return [
            'success' => false,
            'summary' => null,
            'error' => 'Nepodařilo se kontaktovat AI endpoint.'
        ];
    }

    $data = json_decode(trim($response), true);

    if (!is_array($data)) {
        return [
            'success' => false,
            'summary' => null,
            'error' => 'AI endpoint nevrátil validní JSON.'
        ];
    }

    $summary = $data['choices'][0]['message']['content'] ?? null;

    if (!$summary) {
        return [
            'success' => false,
            'summary' => null,
            'error' => 'AI endpoint nevrátil text odpovědi.'
        ];
    }

    return [
        'success' => true,
        'summary' => trim($summary),
        'error' => null,
        'prompt' => $prompt,
        'model' => $model
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate_ai') {
    header('Content-Type: application/json; charset=utf-8');

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Neplatné ID dropu.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM drops WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $drop = $stmt->fetch();

    if (!$drop) {
        echo json_encode(['success' => false, 'error' => 'Drop nebyl nalezen.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = ddrop_generate_ai_summary($drop);

    if (!$result['success']) {
        echo json_encode(['success' => false, 'error' => $result['error']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $update = $pdo->prepare("
        UPDATE drops
        SET ai_summary = :summary,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");

    $update->execute([
        ':summary' => $result['summary'],
        ':id' => $id,
    ]);

    $insertGeneration = $pdo->prepare("
        INSERT INTO ai_generations (drop_id, prompt, response, model)
        VALUES (:drop_id, :prompt, :response, :model)
    ");

    $insertGeneration->execute([
        ':drop_id' => $id,
        ':prompt' => $result['prompt'],
        ':response' => $result['summary'],
        ':model' => $result['model'],
    ]);

    echo json_encode([
        'success' => true,
        'summary' => $result['summary']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Neplatné ID');
}

$stmt = $pdo->prepare("SELECT * FROM drops WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
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
    <title><?= htmlspecialchars($drop['title']) ?> | DDrop</title>
    <link rel="stylesheet" href="/assets/css/styla.css">
    <link rel="shortcut icon" href="/assets/img/logo.ico">
    <style>
        .ai-loading {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 24px;
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

            <p><strong>Značka:</strong> <?= htmlspecialchars($drop['brand']) ?></p>
            <p><strong>Model:</strong> <?= htmlspecialchars($drop['model'] ?? 'Neuvedeno') ?></p>
            <p><strong>SKU:</strong> <?= htmlspecialchars($drop['sku'] ?? 'Neuvedeno') ?></p>
            <p><strong>Colorway:</strong> <?= htmlspecialchars($drop['colorway'] ?? 'Neuvedeno') ?></p>
            <p><strong>Datum dropu:</strong> <?= htmlspecialchars($drop['release_date']) ?></p>
            <p><strong>Retail cena:</strong> <?= htmlspecialchars($drop['retail_price']) ?> <?= htmlspecialchars($drop['currency']) ?></p>
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
                        <span>Generuji AI shrnutí</span>
                        <span class="ai-loading-dots">
                            <span></span><span></span><span></span>
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

                    const data = await response.json();

                    if (!data.success) {
                        aiSummaryText.textContent = data.error || 'Nepodařilo se vygenerovat AI shrnutí.';
                    } else {
                        aiSummaryText.textContent = data.summary;
                    }
                } catch (error) {
                    aiSummaryText.textContent = 'Došlo k chybě při generování AI shrnutí.';
                } finally {
                    aiButton.disabled = false;
                    aiButton.textContent = 'Vygenerovat AI shrnutí';
                }
            });
        }
    </script>
</body>
</html>
