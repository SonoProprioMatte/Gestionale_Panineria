-- Panineria Database Schema
CREATE DATABASE IF NOT EXISTS panineria;
USE panineria;

-- Email verification codes
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    code CHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(6,2) NOT NULL,
    category VARCHAR(80) DEFAULT 'Panini',
    image_url VARCHAR(255) DEFAULT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(8,2) NOT NULL,
    status ENUM('in_attesa', 'in_preparazione', 'pronto', 'consegnato') NOT NULL DEFAULT 'in_attesa',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(6,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: default admin user
-- Hash bcrypt cost=12 della password "password" — CAMBIA IN PRODUZIONE
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@panineria.it', '$2y$10$0gViS88T3l2U.ePrMnvqEOTTRM1WEk5/WQF9dGFHFOLkyFCXPqBru', 'admin');

-- Seed: sample products
INSERT INTO products (name, description, price, category, is_visible) VALUES
('Classico', 'Pane artigianale, prosciutto cotto, scamorza, pomodoro fresco', 5.50, 'Panini', 1),
('Americano', 'Burger di manzo 180g, cheddar, bacon croccante, lattuga, cipolla caramellata', 8.00, 'Burger', 1),
('Vegetariano', 'Pane integrale, hummus, zucchine grigliate, peperoni, rucola', 6.00, 'Panini', 1),
('Club Sandwich', 'Tre strati: tacchino, bacon, uovo, lattuga, pomodoro, maionese', 7.50, 'Sandwich', 1),
('Piadina Romagnola', 'Piadina artigianale, squacquerone, prosciutto crudo, rucola', 6.50, 'Piadine', 1),
('Fritto Misto', 'Olive ascolane, arancini, mozzarelline fritte', 5.00, 'Sfiziosita', 1),
('Coca-Cola 33cl', 'Bevanda gassata', 2.00, 'Bevande', 1),
('Acqua 50cl', 'Naturale o frizzante', 1.00, 'Bevande', 1);
