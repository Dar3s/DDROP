CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(120) NOT NULL,
    password_hash TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS drops (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    sku VARCHAR(100),
    colorway VARCHAR(255),
    release_date TIMESTAMP,
    retail_price NUMERIC(10,2),
    store VARCHAR(255),
    image_url TEXT,
    description TEXT
);

CREATE TABLE IF NOT EXISTS watchlist (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    drop_id INTEGER REFERENCES drops(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, email, password_hash)
VALUES
('demo_user', 'demo@example.com', 'demo_hash');

INSERT INTO drops (
    name,
    brand,
    model,
    sku,
    colorway,
    release_date,
    retail_price,
    store,
    image_url,
    description
)
VALUES
(
    'Nike Dunk Low Panda',
    'Nike',
    'Dunk Low',
    'DD1391-100',
    'Black / White',
    '2026-04-10 09:00:00',
    120.00,
    'Nike SNKRS',
    'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/dd1391-100/nike-dunk-low-retro-shoes-69hX7z.png',
    'Populární model s vysokou poptávkou.'
),
(
    'Air Jordan 1 Retro High OG',
    'Jordan',
    'AJ1 High',
    'DZ5485-042',
    'Royal Blue',
    '2026-04-14 09:00:00',
    190.00,
    'Nike SNKRS',
    'https://static.nike.com/a/images/t_PDP_1728_v1/f_auto,q_auto:eco/air-jordan-1-retro-high-og-shoes.png',
    'Legendární Jordan release.'
);

INSERT INTO watchlist (user_id, drop_id)
VALUES
(1, 1),
(1, 2);
