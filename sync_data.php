<?php

require_once __DIR__ . '/config/db.php';

$apiUrl = 'https://ddrop.net/api/drops.php';

$json = @file_get_contents($apiUrl);

if ($json === false) {
    die("Nepodařilo se stáhnout data z API.\n");
}

$data = json_decode($json, true);

if (!is_array($data)) {
    die("API nevrátilo validní JSON.\n");
}

$stmt = $pdo->prepare("
    INSERT INTO drops (
        id,
        title,
        brand,
        model,
        sku,
        colorway,
        release_date,
        retail_price,
        currency,
        store_name,
        store_link,
        description,
        image_url,
        ai_summary
    ) VALUES (
        :id,
        :title,
        :brand,
        :model,
        :sku,
        :colorway,
        :release_date,
        :retail_price,
        :currency,
        :store_name,
        :store_link,
        :description,
        :image_url,
        :ai_summary
    )
    ON CONFLICT (id) DO UPDATE SET
        title = EXCLUDED.title,
        brand = EXCLUDED.brand,
        model = EXCLUDED.model,
        sku = EXCLUDED.sku,
        colorway = EXCLUDED.colorway,
        release_date = EXCLUDED.release_date,
        retail_price = EXCLUDED.retail_price,
        currency = EXCLUDED.currency,
        store_name = EXCLUDED.store_name,
        store_link = EXCLUDED.store_link,
        description = EXCLUDED.description,
        image_url = EXCLUDED.image_url,
        ai_summary = EXCLUDED.ai_summary
");

foreach ($data as $drop) {
    $stmt->execute([
        ':id' => $drop['id'] ?? null,
        ':title' => $drop['title'] ?? null,
        ':brand' => $drop['brand'] ?? null,
        ':model' => $drop['model'] ?? null,
        ':sku' => $drop['sku'] ?? null,
        ':colorway' => $drop['colorway'] ?? null,
        ':release_date' => $drop['release_date'] ?? null,
        ':retail_price' => $drop['retail_price'] ?? null,
        ':currency' => $drop['currency'] ?? 'EUR',
        ':store_name' => $drop['store_name'] ?? null,
        ':store_link' => $drop['store_link'] ?? null,
        ':description' => $drop['description'] ?? null,
        ':image_url' => $drop['image_url'] ?? null,
        ':ai_summary' => $drop['ai_summary'] ?? null,
    ]);
}

echo "Synchronizace dropů dokončena.\n";
