CREATE DATABASE IF NOT EXISTS chance_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chance_store;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'seller',
    telephone VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS customer_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    message TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES customer_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contacts (
    id INT PRIMARY KEY DEFAULT 1,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(190) NOT NULL,
    address VARCHAR(255) NOT NULL
);

INSERT INTO contacts (id, phone, email, address)
VALUES (1, '+0000000000', 'info@chancestore.com', 'Chance Store Ltd Address')
ON DUPLICATE KEY UPDATE id = id;

-- Sample users for login:
-- 1) Admin:  admin@chancestore.com / admin123
-- 2) Seller: seller@chancestore.com / seller123
INSERT INTO users (name, email, password, role, telephone)
VALUES
    (
        'Chance Store Admin',
        'admin@chancestore.com',
        '$2y$10$GPr.W/bPnziwdWGj4LVIiOBipUWBLT4wa4JggqGkN0qxogKbVuyHG',
        'admin',
        '+250700000001'
    ),
    (
        'Chance Store Seller',
        'seller@chancestore.com',
        '$2y$10$Nq.MmHCJAI9lXgxjqBRZs.ZNk.73v4/YvC5MQ1ChxvkA97qXX92jG',
        'seller',
        '+250700000002'
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    role = VALUES(role),
    telephone = VALUES(telephone);
