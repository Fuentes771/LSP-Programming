-- Database Sistem Pemesanan Restoran
-- Jalankan: mysql -u root -p < database.sql  (atau impor lewat phpMyAdmin)

CREATE DATABASE IF NOT EXISTS restoran_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE restoran_db;

-- Hapus tabel lama (urutan anak -> induk) agar skrip dapat dijalankan ulang
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS restaurant_orders;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS restaurant_tables;
DROP TABLE IF EXISTS customers;

CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE restaurant_tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(10) NOT NULL UNIQUE,
    capacity INT UNSIGNED NOT NULL,
    status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif'
) ENGINE=InnoDB;

CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    table_id INT UNSIGNED NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    guest_count INT UNSIGNED NOT NULL,
    status ENUM('Menunggu', 'Dikonfirmasi', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reservation_slot (table_id, reservation_date, reservation_time),
    CONSTRAINT fk_reservations_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_reservations_table FOREIGN KEY (table_id)
        REFERENCES restaurant_tables (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE restaurant_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    reservation_id INT UNSIGNED NULL,
    order_date DATE NOT NULL,
    status ENUM('Diproses', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Diproses',
    total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_orders_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NOT NULL,
    menu_name VARCHAR(100) NOT NULL,
    price DECIMAL(12, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
        REFERENCES restaurant_orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id)
        REFERENCES menu_items (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Data contoh untuk demonstrasi
INSERT INTO customers (name, phone, email) VALUES
    ('Budi Santoso', '081234567890', 'budi@example.com'),
    ('Siti Aminah', '082345678901', NULL),
    ('Andi Wijaya', '083456789012', 'andi@example.com');

INSERT INTO restaurant_tables (table_number, capacity, status) VALUES
    ('M01', 2, 'Aktif'),
    ('M02', 4, 'Aktif'),
    ('M03', 6, 'Aktif'),
    ('M04', 8, 'Nonaktif');

INSERT INTO menu_items (name, price, is_available) VALUES
    ('Nasi Goreng Spesial', 25000, 1),
    ('Mie Ayam Bakso', 20000, 1),
    ('Ayam Bakar', 30000, 1),
    ('Es Teh Manis', 5000, 1),
    ('Jus Alpukat', 15000, 0);
