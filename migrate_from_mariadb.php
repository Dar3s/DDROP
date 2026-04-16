<?php

function connectPostgres(): PDO
{
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'dropshipping_app';
    $user = getenv('DB_USER') ?: 'ddrop_user';
    $pass = getenv('DB_PASS') ?: 'ddrop_password';

    return new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbname}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function connectMaria(): PDO
{
    $host = getenv('OLD_DB_HOST');
    $port = getenv('OLD_DB_PORT') ?: '3306';
    $dbname = getenv('OLD_DB_NAME');
    $user = getenv('OLD_DB_USER');
    $pass = getenv('OLD_DB_PASS');

    if (!$host || !$dbname || !$user) {
        throw new RuntimeException('Chybí OLD_DB_* proměnné prostředí.');
    }

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function createPostgresSchema(PDO $pg): void
{
    $pg->exec("
        CREATE TABLE IF NOT EXISTS drops (
            id INTEGER PRIMARY KEY,
            title VARCHAR(255),
            brand VARCHAR(100),
            model VARCHAR(255),
            sku VARCHAR(100),
            colorway VARCHAR(255),
            release_date TIMESTAMP NULL,
            retail_price NUMERIC(10,2) NULL,
            currency VARCHAR(10) DEFAULT 'EUR',
            store_name VARCHAR(255),
            store_link TEXT,
            description TEXT,
            image_url TEXT,
            ai_summary TEXT,
            status VARCHAR(50) DEFAULT 'upcoming'
        );
    ");

    $pg->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash TEXT NOT NULL
        );
    ");

    $pg->exec("
        CREATE TABLE IF NOT EXISTS price_watchlist (
            id INTEGER PRIMARY KEY,
            discord_user_id VARCHAR(100),
            product_url TEXT,
            product_title TEXT,
            current_price NUMERIC(10,2) NULL,
            target_price NUMERIC(10,2) NULL,
            currency VARCHAR(10) DEFAULT 'CZK',
            status VARCHAR(50) DEFAULT 'active',
            last_checked_at TIMESTAMP NULL,
            last_notified_price NUMERIC(10,2) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $pg->exec("
        CREATE TABLE IF NOT EXISTS price_watch_history (
            id INTEGER PRIMARY KEY,
            watch_id INTEGER,
            checked_price NUMERIC(10,2) NULL,
            in_stock BOOLEAN DEFAULT TRUE,
            checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
}

function copyDrops(PDO $maria, PDO $pg): void
{
    $rows = $maria->query("SELECT * FROM drops ORDER BY id ASC")->fetchAll();

    $stmt = $pg->prepare("
        INSERT INTO drops (
            id, title, brand, model, sku, colorway, release_date,
            retail_price, currency, store_name, store_link,
            description, image_url, ai_summary, status
        ) VALUES (
            :id, :title, :brand, :model, :sku, :colorway, :release_date,
            :retail_price, :currency, :store_name, :store_link,
            :description, :image_url, :ai_summary, :status
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
            ai_summary = EXCLUDED.ai_summary,
            status = EXCLUDED.status
    ");

    foreach ($rows as $row) {
        $stmt->execute([
            ':id' => $row['id'] ?? null,
            ':title' => $row['title'] ?? null,
            ':brand' => $row['brand'] ?? null,
            ':model' => $row['model'] ?? null,
            ':sku' => $row['sku'] ?? null,
            ':colorway' => $row['colorway'] ?? null,
            ':release_date' => $row['release_date'] ?? null,
            ':retail_price' => $row['retail_price'] ?? null,
            ':currency' => $row['currency'] ?? 'EUR',
            ':store_name' => $row['store_name'] ?? null,
            ':store_link' => $row['store_link'] ?? null,
            ':description' => $row['description'] ?? null,
            ':image_url' => $row['image_url'] ?? null,
            ':ai_summary' => $row['ai_summary'] ?? null,
            ':status' => $row['status'] ?? 'upcoming',
        ]);
    }

    echo "Drops: " . count($rows) . " záznamů přeneseno.\n";
}

function copyUsers(PDO $maria, PDO $pg): void
{
    $rows = $maria->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

    $stmt = $pg->prepare("
        INSERT INTO users (
            id, username, email, password_hash
        ) VALUES (
            :id, :username, :email, :password_hash
        )
        ON CONFLICT (id) DO UPDATE SET
            username = EXCLUDED.username,
            email = EXCLUDED.email,
            password_hash = EXCLUDED.password_hash
    ");

    foreach ($rows as $row) {
        $stmt->execute([
            ':id' => $row['id'] ?? null,
            ':username' => $row['username'] ?? null,
            ':email' => $row['email'] ?? null,
            ':password_hash' => $row['password_hash'] ?? null,
        ]);
    }

    echo "Users: " . count($rows) . " záznamů přeneseno.\n";
}

function copyWatchlist(PDO $maria, PDO $pg): void
{
    $rows = $maria->query("SELECT * FROM price_watchlist ORDER BY id ASC")->fetchAll();

    $stmt = $pg->prepare("
        INSERT INTO price_watchlist (
            id, discord_user_id, product_url, product_title,
            current_price, target_price, currency, status,
            last_checked_at, last_notified_price, created_at
        ) VALUES (
            :id, :discord_user_id, :product_url, :product_title,
            :current_price, :target_price, :currency, :status,
            :last_checked_at, :last_notified_price, :created_at
        )
        ON CONFLICT (id) DO UPDATE SET
            discord_user_id = EXCLUDED.discord_user_id,
            product_url = EXCLUDED.product_url,
            product_title = EXCLUDED.product_title,
            current_price = EXCLUDED.current_price,
            target_price = EXCLUDED.target_price,
            currency = EXCLUDED.currency,
            status = EXCLUDED.status,
            last_checked_at = EXCLUDED.last_checked_at,
            last_notified_price = EXCLUDED.last_notified_price,
            created_at = EXCLUDED.created_at
    ");

    foreach ($rows as $row) {
        $stmt->execute([
            ':id' => $row['id'] ?? null,
            ':discord_user_id' => $row['discord_user_id'] ?? null,
            ':product_url' => $row['product_url'] ?? null,
            ':product_title' => $row['product_title'] ?? null,
            ':current_price' => $row['current_price'] ?? null,
            ':target_price' => $row['target_price'] ?? null,
            ':currency' => $row['currency'] ?? 'CZK',
            ':status' => $row['status'] ?? 'active',
            ':last_checked_at' => $row['last_checked_at'] ?? null,
            ':last_notified_price' => $row['last_notified_price'] ?? null,
            ':created_at' => $row['created_at'] ?? null,
        ]);
    }

    echo "Price watchlist: " . count($rows) . " záznamů přeneseno.\n";
}

function copyWatchHistory(PDO $maria, PDO $pg): void
{
    $rows = $maria->query("SELECT * FROM price_watch_history ORDER BY id ASC")->fetchAll();

    $stmt = $pg->prepare("
        INSERT INTO price_watch_history (
            id, watch_id, checked_price, in_stock, checked_at
        ) VALUES (
            :id, :watch_id, :checked_price, :in_stock, :checked_at
        )
        ON CONFLICT (id) DO UPDATE SET
            watch_id = EXCLUDED.watch_id,
            checked_price = EXCLUDED.checked_price,
            in_stock = EXCLUDED.in_stock,
            checked_at = EXCLUDED.checked_at
    ");

    foreach ($rows as $row) {
        $stmt->execute([
            ':id' => $row['id'] ?? null,
            ':watch_id' => $row['watch_id'] ?? null,
            ':checked_price' => $row['checked_price'] ?? null,
            ':in_stock' => !empty($row['in_stock']),
            ':checked_at' => $row['checked_at'] ?? null,
        ]);
    }

    echo "Price watch history: " . count($rows) . " záznamů přeneseno.\n";
}

try {
    $maria = connectMaria();
    $pg = connectPostgres();

    createPostgresSchema($pg);

    copyDrops($maria, $pg);
    copyUsers($maria, $pg);
    copyWatchlist($maria, $pg);
    copyWatchHistory($maria, $pg);

    echo "Migrace dokončena.\n";
} catch (Throwable $e) {
    die("Chyba migrace: " . $e->getMessage() . "\n");
}
