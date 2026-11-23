-- ============================================================
--  A la Carta · Sistema de gestión de restaurante
--  Tarea 6 · Programación Web Avanzada
--  Base de datos: pwatarea6 (MariaDB 10.4 / MySQL 8)
--
--  Estructura tomada del enunciado (roles, users, dishes, orders)
--  y ampliada con order_items para que un pedido pueda llevar
--  varios platos, más los campos de mesa, notas y fechas que
--  necesita la operación diaria del salón.
-- ============================================================

DROP DATABASE IF EXISTS pwatarea6;
CREATE DATABASE pwatarea6 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pwatarea6;

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Roles del sistema
-- ------------------------------------------------------------
CREATE TABLE roles (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL
);

INSERT INTO roles (name) VALUES
    ('Administrator'),
    ('Chef'),
    ('Waiter'),
    ('Client');

-- ------------------------------------------------------------
-- Usuarios
-- ------------------------------------------------------------
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(80) NOT NULL,
    username   VARCHAR(50) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role_id    INT NOT NULL,
    active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- La clave de todas las cuentas de prueba es: ALaCarta2026
INSERT INTO users (id, full_name, username, email, password, role_id, active) VALUES
    (1, 'Marta Cedeño',   'admin',   'admin@alacarta.ec',   '$2y$10$/21Lo5Q8csTGtoMU8Xoa8eKBMC5B8omehIV3A9GamxA7eazwDi9Um', 1, 1),
    (2, 'Iván Zambrano',  'cocina',  'cocina@alacarta.ec',  '$2y$10$/21Lo5Q8csTGtoMU8Xoa8eKBMC5B8omehIV3A9GamxA7eazwDi9Um', 2, 1),
    (3, 'Ruth Quimí',     'mesero',  'mesero@alacarta.ec',  '$2y$10$/21Lo5Q8csTGtoMU8Xoa8eKBMC5B8omehIV3A9GamxA7eazwDi9Um', 3, 1),
    (4, 'Andrés Vera',    'andres',  'andres@correo.ec',     '$2y$10$/21Lo5Q8csTGtoMU8Xoa8eKBMC5B8omehIV3A9GamxA7eazwDi9Um', 4, 1),
    (5, 'Diana Moreira',  'diana',   'diana@correo.ec',      '$2y$10$/21Lo5Q8csTGtoMU8Xoa8eKBMC5B8omehIV3A9GamxA7eazwDi9Um', 4, 1);

-- ------------------------------------------------------------
-- Platos de la carta
-- ------------------------------------------------------------
CREATE TABLE dishes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    price       DECIMAL(10, 2) NOT NULL,
    category    VARCHAR(30) NOT NULL DEFAULT 'Platos fuertes',
    image       VARCHAR(120) DEFAULT NULL,
    available   TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO dishes (id, name, description, price, category, image, available) VALUES
    (1, 'Ceviche mixto',        'Camarón y pescado en limón sutil, ají, cebolla encurtida y chifles.', 9.50, 'Entradas', 'plato-ceviche.jpg', 1),
    (2, 'Empanadas de harina',  'Cuatro unidades de masa de harina rellenas de queso, con ají de la casa.', 4.00, 'Entradas', 'plato-empanadas.jpg', 1),
    (3, 'Pollo guisado',        'Pollo de campo guisado con papas y arvejas, tomate y cebolla.', 7.80, 'Platos fuertes', 'plato-pollo.jpg', 1),
    (4, 'Camarones al ajillo',  '250 g de camarón en mantequilla y ajo, con arroz y ensalada.', 11.00, 'Platos fuertes', 'plato-camarones.jpg', 1),
    (5, 'Pescado a la brasa',   'Corvina entera a la brasa, patacón, ensalada criolla y limón.', 12.50, 'Platos fuertes', 'plato-pescado.jpg', 1),
    (6, 'Jugo de naranja',      '400 ml recién prensado, sin azúcar añadida.', 2.50, 'Bebidas', 'plato-jugo.jpg', 1);

-- ------------------------------------------------------------
-- Pedidos
-- ------------------------------------------------------------
CREATE TABLE orders (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    waiter_id    INT DEFAULT NULL,
    table_number INT DEFAULT NULL,
    status       ENUM('Pending', 'Preparing', 'Ready to serve', 'Served') NOT NULL DEFAULT 'Pending',
    notes        VARCHAR(255) DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user   FOREIGN KEY (user_id)   REFERENCES users(id),
    CONSTRAINT fk_orders_waiter FOREIGN KEY (waiter_id) REFERENCES users(id)
);

CREATE INDEX idx_orders_status  ON orders (status);
CREATE INDEX idx_orders_created ON orders (created_at);

CREATE TABLE order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    dish_id    INT NOT NULL,
    quantity   INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_dish  FOREIGN KEY (dish_id)  REFERENCES dishes(id)
);

-- Pedidos de ejemplo: los últimos con fecha de hoy para que el panel
-- muestre la operación en curso desde el primer arranque.
INSERT INTO orders (id, user_id, waiter_id, table_number, status, notes, created_at) VALUES
    (1, 4, 3, 4,  'Pending',        'Sin cebolla en el ceviche.', DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
    (2, 5, 3, 2,  'Preparing',      'Dos jugos sin hielo.',      DATE_SUB(NOW(), INTERVAL 26 MINUTE)),
    (3, 4, 3, NULL, 'Ready to serve', 'Para llevar, cubiertos.',  DATE_SUB(NOW(), INTERVAL 41 MINUTE)),
    (4, 5, 3, 6,  'Served',         NULL,                        DATE_SUB(NOW(), INTERVAL 1 HOUR)),
    (5, 4, 3, 1,  'Served',         'Cumpleaños: llevar una vela.', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
    (6, 5, 3, 5,  'Served',         NULL,                        DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (7, 4, 3, 3,  'Served',         NULL,                        DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO order_items (order_id, dish_id, quantity, unit_price) VALUES
    (1, 1, 1, 9.50), (1, 6, 2, 2.50),
    (2, 3, 1, 7.80), (2, 2, 1, 4.00), (2, 6, 2, 2.50),
    (3, 4, 1, 11.00), (3, 6, 1, 2.50),
    (4, 5, 2, 12.50), (4, 1, 1, 9.50),
    (5, 3, 2, 7.80), (5, 2, 2, 4.00), (5, 6, 3, 2.50),
    (6, 1, 2, 9.50), (6, 4, 1, 11.00),
    (7, 5, 1, 12.50), (7, 2, 1, 4.00), (7, 6, 1, 2.50);

-- Los pedidos de ejemplo se insertaron con la fecha del día; su última
-- actualización debe coincidir con esa fecha y no con la hora de carga.
UPDATE orders SET updated_at = created_at;
