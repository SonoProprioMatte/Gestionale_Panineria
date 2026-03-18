CREATE DATABASE IF NOT EXISTS panineria;
USE panineria;

CREATE TABLE IF NOT EXISTS email_verifications (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(150) NOT NULL,
    name          VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    code          CHAR(6) NOT NULL,
    expires_at    DATETIME NOT NULL,
    attempts      TINYINT NOT NULL DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    description     TEXT,
    price           DECIMAL(6,2) NOT NULL,
    category        VARCHAR(80) DEFAULT 'Panini',
    image_url       VARCHAR(255) DEFAULT NULL,
    is_visible      TINYINT(1) NOT NULL DEFAULT 1,
    variant_options VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_ingredients (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name       VARCHAR(100) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_extras (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name       VARCHAR(100) NOT NULL,
    price      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    total      DECIMAL(8,2) NOT NULL,
    status     ENUM('in_attesa','in_preparazione','pronto','consegnato') NOT NULL DEFAULT 'in_attesa',
    notes      TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT NOT NULL,
    product_id   INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity     INT NOT NULL DEFAULT 1,
    unit_price   DECIMAL(6,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_item_customizations (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    type          ENUM('remove','extra','variant','note') NOT NULL,
    label         VARCHAR(255) NOT NULL,
    price         DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin: password = admin123
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@panineria.it', '$2y$10$0gViS88T3l2U.ePrMnvqEOTTRM1WEk5/WQF9dGFHFOLkyFCXPqBru', 'admin');

INSERT INTO products (name, description, price, category, is_visible, variant_options) VALUES
('Classico',          'Pane artigianale, prosciutto cotto, scamorza, pomodoro fresco',                5.50, 'Panini',     1, NULL),
('Americano',         'Burger di manzo 180g, cheddar, bacon croccante, lattuga, cipolla caramellata', 8.00, 'Burger',     1, NULL),
('Vegetariano',       'Pane integrale, hummus, zucchine grigliate, peperoni, rucola',                 6.00, 'Panini',     1, NULL),
('Club Sandwich',     'Tre strati: tacchino, bacon, uovo, lattuga, pomodoro, maionese',               7.50, 'Sandwich',   1, NULL),
('Piadina Romagnola', 'Piadina artigianale, squacquerone, prosciutto crudo, rucola',                  6.50, 'Piadine',    1, NULL),
('Fritto Misto',      'Olive ascolane, arancini, mozzarelline fritte',                                5.00, 'Sfiziosita', 1, NULL),
('Coca-Cola 33cl',    'Bevanda gassata',                                                              2.00, 'Bevande',    1, NULL),
('Acqua 50cl',        'Naturale o frizzante',                                                         1.00, 'Bevande',    1, '["Naturale","Frizzante"]');

-- Ingredienti Classico
INSERT INTO product_ingredients (product_id, name) VALUES
(1, 'Pane artigianale'), (1, 'Prosciutto cotto'), (1, 'Scamorza'), (1, 'Pomodoro fresco');

-- Ingredienti Americano
INSERT INTO product_ingredients (product_id, name) VALUES
(2, 'Burger di manzo'), (2, 'Cheddar'), (2, 'Bacon'), (2, 'Lattuga'), (2, 'Cipolla caramellata');

-- Ingredienti Vegetariano
INSERT INTO product_ingredients (product_id, name) VALUES
(3, 'Pane integrale'), (3, 'Hummus'), (3, 'Zucchine grigliate'), (3, 'Peperoni'), (3, 'Rucola');

-- Ingredienti Club Sandwich
INSERT INTO product_ingredients (product_id, name) VALUES
(4, 'Tacchino'), (4, 'Bacon'), (4, 'Uovo'), (4, 'Lattuga'), (4, 'Pomodoro'), (4, 'Maionese');

-- Ingredienti Piadina Romagnola
INSERT INTO product_ingredients (product_id, name) VALUES
(5, 'Piadina'), (5, 'Squacquerone'), (5, 'Prosciutto crudo'), (5, 'Rucola');

-- Extra Americano
INSERT INTO product_extras (product_id, name, price) VALUES
(2, 'Doppio burger', 2.00), (2, 'Doppio cheddar', 0.50), (2, 'Bacon extra', 1.00);

-- Extra Classico
INSERT INTO product_extras (product_id, name, price) VALUES
(1, 'Scamorza extra', 0.50), (1, 'Prosciutto doppio', 1.00);
