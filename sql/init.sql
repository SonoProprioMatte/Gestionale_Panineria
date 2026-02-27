-- Panineria Database Schema
CREATE DATABASE IF NOT EXISTS panineria;
USE panineria;

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

-- Seed: default admin user (password: Admin1234!)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@panineria.it', '$2y$12$YourHashedPasswordHere', 'admin');

-- We generate the hash at runtime via a seed script, so let's use a known bcrypt hash for "Admin1234!"
UPDATE users SET password = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@panineria.it';
-- Note: that hash = "password" for demo. Change immediately in production!

-- Seed: sample products
INSERT INTO products (name, description, price, category, is_visible) VALUES
('Classico', 'Pane artigianale, prosciutto cotto, scamorza, pomodoro fresco', 5.50, 'Panini', 1),
('Americano', 'Burger di manzo 180g, cheddar, bacon croccante, lattuga, cipolla caramellata', 8.00, 'Burger', 1),
('Vegetariano', 'Pane integrale, hummus, zucchine grigliate, peperoni, rucola', 6.00, 'Panini', 1),
('Club Sandwich', 'Tre strati: tacchino, bacon, uovo, lattuga, pomodoro, maionese', 7.50, 'Sandwich', 1),
('Piadina Romagnola', 'Piadina artigianale, squacquerone, prosciutto crudo, rucola', 6.50, 'Piadine', 1),
('Fritto Misto', 'Olive ascolane, arancini, mozzarelline fritte', 5.00, 'Sfiziosità', 1),
('Coca-Cola 33cl', 'Bevanda gassata', 2.00, 'Bevande', 1),
('Acqua 50cl', 'Naturale o frizzante', 1.00, 'Bevande', 1);
