<?php
session_start();
require_once __DIR__ . '/db.php';

function ddrop_build_ai_prompt(array $drop): string
{
    return "Napiš české shrnutí sneaker dropu. "
        . "Napiš 4 až 6 vět. "
        . "Piš pouze čistý text bez nadpisu, bez markdownu, bez odrážek a bez začátku typu 'Shrnutí dropu'. "
        . "Zhodnoť design, cenu, značku, zajímavost pro běžného uživatele a opatrně i možný resale zájem. "
        . "Neslibuj jistý zisk a nepiš přehnané marketingové fráze. "
        . "Každé nové vygenerování napiš trochu jinak, jinou formulací a jiným pořadím myšlenek.\n\n"
        . "Název: " . ($drop['title'] ?? 'Neuvedeno') . "\n"
        . "Značka: " . ($drop['brand'] ?? 'Neuvedeno') . "\n"
        . "Model: " . ($drop['model'] ?? 'Neuvedeno') . "\n"
        . "SKU: " . ($drop['sku'] ?? 'Neuvedeno') . "\n"
        . "Colorway: " . ($drop['colorway'] ?? 'Neuvedeno') . "\n"
        . "Retail cena: " . ($drop['retail_price'] ?? 'Neuvedeno') . " " . ($drop['currency'] ?? '') . "\n"
        . "Store: " . ($drop['store_name'] ?? 'Neuvedeno') . "\n"
        . "Popis: " . ($drop['description'] ?? 'Bez popisu');
}

function ddrop_clean_ai_summary(string $summary): string
{
    $summary = trim($summary);

    $summary = preg_replace('/^#+\s*/u', '', $summary);
    $summary = preg_replace('/^Shrnutí\s+dropu\s*:\s*/iu', '', $summary);
    $summary = preg_replace('/^AI\s+shrnutí\s*:\s*/iu', '', $summary);
    $summary = preg_replace('/^Shrnutí\s*:\s*/iu', '', $summary);

    $summary = trim($summary);
    $summary = preg_replace('/\s+/', ' ', $summary);

    return $summary;
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
                'content' => 'Jsi AI pomocník pro sneaker dropy. Odpovídej česky, přirozeně a realisticky. Nikdy nepoužívej markdown nadpisy, odrážky ani prefixy typu Shrnutí dropu.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.95,
        'max_tokens' => 450
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

    $summary = ddrop_clean_ai_summary($summary);

    return [
        'success' => true,
        'summary' => $summary,
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_watchlist') {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $targetPrice = isset($_POST['target_price']) ? (float)$_POST['target_price'] : 0;
    $userId = (int)$_SESSION['user']['id'];

    if ($id <= 0 || $targetPrice <= 0) {
        header('Location: drop.php?id=' . $id);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT currency
        FROM drops
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $id,
    ]);

    $dropCurrency = $stmt->fetchColumn() ?: 'EUR';

    $stmt = $pdo->prepare("
        INSERT INTO watchlist (
            user_id,
            drop_id,
            target_price,
            currency,
            updated_at
        ) VALUES (
            :user_id,
            :drop_id,
            :target_price,
            :currency,
            CURRENT_TIMESTAMP
        )
        ON CONFLICT (user_id, drop_id)
        DO UPDATE SET
            target_price = EXCLUDED.target_price,
            currency = EXCLUDED.currency,
            updated_at = CURRENT_TIMESTAMP
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':drop_id' => $id,
        ':target_price' => $targetPrice,
        ':currency' => $dropCurrency,
    ]);

    header('Location: drop.php?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_watchlist') {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $userId = (int)$_SESSION['user']['id'];

    if ($id > 0) {
        $stmt = $pdo->prepare("
            DELETE FROM watchlist
            WHERE user_id = :user_id
              AND drop_id = :drop_id
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':drop_id' => $id,
        ]);
    }

    header('Location: drop.php?id=' . $id);
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

$isLoggedIn = isset($_SESSION['user']);
$watchlistItem = null;

if ($isLoggedIn) {
    $userId = (int)$_SESSION['user']['id'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM watchlist
        WHERE user_id = :user_id
          AND drop_id = :drop_id
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':drop_id' => $drop['id'],
    ]);

    $watchlistItem = $stmt->fetch();
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

        .ai-response-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .ai-response-item {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.13);
            line-height: 1.55;
        }

        .watch-form {
            margin-top: 22px;
            padding: 18px;
            border-radius: 18px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.13);
        }

        .watch-form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-top: 12px;
        }

        .watch-input {
            min-width: 180px;
            padding: 13px 15px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(0,0,0,0.18);
            color: #fff;
            outline: none;
        }

        .watch-input::placeholder {
            color: rgba(255,255,255,0.65);
        }

        .watch-current {
            margin-top: 12px;
            opacity: 0.9;
        }

        .login-hint {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
            opacity: 0.95;
        }

        .inline-login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            padding: 12px 18px;
            border-radius: 14px;
            color: #ffffff;
            font-weight: 800;
            text-decoration: none;
            background: linear-gradient(135deg, rgba(126, 79, 255, 0.78), rgba(82, 48, 166, 0.72));
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow:
                0 12px 28px rgba(45, 20, 120, 0.34),
                inset 0 1px 0 rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .inline-login-btn:hover {
            transform: translateY(-2px);
            color: #ffffff;
            text-decoration: none;
            background: linear-gradient(135deg, rgba(145, 98, 255, 0.9), rgba(95, 55, 190, 0.82));
            box-shadow:
                0 16px 36px rgba(80, 40, 180, 0.44),
                inset 0 1px 0 rgba(255, 255, 255, 0.22);
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

            <?php if ($isLoggedIn): ?>
                <div class="watch-form">
                    <h2>Sledování cílové ceny</h2>
                    <p>
                        Nastav si cenu, při které by tě v reálném rozšíření mohl upozornit price tracking bot.
                    </p>

                    <?php if ($watchlistItem): ?>
                        <p class="watch-current">
                            Aktuálně sleduješ cílovou cenu:
                            <strong>
                                <?= htmlspecialchars($watchlistItem['target_price']) ?>
                                <?= htmlspecialchars($watchlistItem['currency']) ?>
                            </strong>
                        </p>
                    <?php endif; ?>

                    <form method="POST" class="watch-form-row">
                        <input type="hidden" name="action" value="save_watchlist">
                        <input type="hidden" name="id" value="<?= (int)$drop['id'] ?>">

                        <input
                            class="watch-input"
                            type="number"
                            step="0.01"
                            min="1"
                            name="target_price"
                            placeholder="Cílová cena"
                            value="<?= $watchlistItem ? htmlspecialchars($watchlistItem['target_price']) : '' ?>"
                            required
                        >

                        <button class="btn" type="submit">
                            Uložit do watchlistu
                        </button>
                    </form>

                    <?php if ($watchlistItem): ?>
                        <form method="POST" style="margin-top: 12px;">
                            <input type="hidden" name="action" value="remove_watchlist">
                            <input type="hidden" name="id" value="<?= (int)$drop['id'] ?>">

                            <button class="btn btn-outline" type="submit">
                                Odebrat z watchlistu
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="login-hint">
                    <span>Pro nastavení cílové ceny se nejdřív přihlas.</span>
                    <a class="inline-login-btn" href="login.php">Přihlášení</a>
                </p>
            <?php endif; ?>

            <div class="info-card" style="margin-top: 22px;">
                <h2>AI shrnutí</h2>

                <div id="ai-summary-list" class="ai-response-list">
                    <p id="ai-empty-text">Zatím nebylo vygenerováno.</p>
                </div>

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
        const aiSummaryList = document.getElementById('ai-summary-list');

        function setAiResponse(text) {
            aiSummaryList.innerHTML = '';

            const item = document.createElement('div');
            item.className = 'ai-response-item';
            item.textContent = text;

            aiSummaryList.appendChild(item);
        }

        function setAiLoading() {
            aiSummaryList.innerHTML = '';

            const loadingItem = document.createElement('div');
            loadingItem.className = 'ai-response-item';
            loadingItem.innerHTML = `
                <span class="ai-loading">
                    <span>Generuji AI shrnutí</span>
                    <span class="ai-loading-dots">
                        <span></span><span></span><span></span>
                    </span>
                </span>
            `;

            aiSummaryList.appendChild(loadingItem);
        }

        if (aiButton && aiSummaryList) {
            aiButton.addEventListener('click', async () => {
                const dropId = aiButton.getAttribute('data-drop-id');

                aiButton.disabled = true;
                aiButton.textContent = 'Generuji...';

                setAiLoading();

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
                        setAiResponse(data.error || 'Nepodařilo se vygenerovat AI shrnutí.');
                    } else {
                        setAiResponse(data.summary);
                    }
                } catch (error) {
                    setAiResponse('Došlo k chybě při generování AI shrnutí.');
                } finally {
                    aiButton.disabled = false;
                    aiButton.textContent = 'Vygenerovat AI shrnutí';
                }
            });
        }
    </script>
</body>
</html>
