-- AgriLink Database Schema
-- MySQL 5.7+ / MariaDB 10.3+
-- Run this file first, then seed.sql

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -------------------------------------------------------
-- users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(191) NOT NULL,
    `email`      VARCHAR(191) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `phone`      VARCHAR(30)  DEFAULT NULL,
    `address`    TEXT         DEFAULT NULL,
    `address_province` VARCHAR(100) DEFAULT NULL,
    `address_city`     VARCHAR(100) DEFAULT NULL,
    `address_barangay` VARCHAR(100) DEFAULT NULL,
    `address_street`   VARCHAR(255) DEFAULT NULL,
    `role`       ENUM('admin','farmer','buyer', 'logistics') NOT NULL DEFAULT 'buyer',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- categories
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `slug`       VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- products
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `farmer_id`          INT UNSIGNED NOT NULL,
    `category_id`        INT UNSIGNED DEFAULT NULL,
    `name`               VARCHAR(191) NOT NULL,
    `description`        TEXT         DEFAULT NULL,
    `unit_type`          VARCHAR(30)  NOT NULL DEFAULT 'kg',
    `price_per_unit`     DECIMAL(10,2) NOT NULL,
    `stock_quantity`     INT UNSIGNED NOT NULL DEFAULT 0,
    `image`              VARCHAR(255) DEFAULT NULL,
    `additional_images`  JSON         DEFAULT NULL,
    `is_available`       TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_products_farmer`   (`farmer_id`),
    KEY `idx_products_category` (`category_id`),
    KEY `idx_products_available`(`is_available`, `stock_quantity`),
    CONSTRAINT `fk_products_farmer`   FOREIGN KEY (`farmer_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- orders
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `buyer_id`            INT UNSIGNED NOT NULL,
    `delivery_phone`      VARCHAR(30)  DEFAULT NULL,
    `delivery_address`    TEXT         DEFAULT NULL,
    `delivery_province`   VARCHAR(100) DEFAULT NULL,
    `delivery_city`       VARCHAR(100) DEFAULT NULL,
    `delivery_barangay`   VARCHAR(100) DEFAULT NULL,
    `delivery_street`     VARCHAR(255) DEFAULT NULL,
    `delivery_lat`        DECIMAL(10,8) DEFAULT NULL,
    `delivery_lng`        DECIMAL(11,8) DEFAULT NULL,
    `payment_method`      ENUM('cod','meetup') NOT NULL DEFAULT 'cod',
    `payment_status`      ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    `logistics_status`    ENUM('pending','processing','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `in_transit_at`       TIMESTAMP NULL DEFAULT NULL,
    `total_amount`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `cancellation_reason` TEXT         DEFAULT NULL,
    `completed_at`        TIMESTAMP NULL DEFAULT NULL,
    `cancelled_at`        TIMESTAMP NULL DEFAULT NULL,
    `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_orders_buyer`  (`buyer_id`),
    KEY `idx_orders_status` (`logistics_status`),
    CONSTRAINT `fk_orders_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- order_delivery_tracking (latest rider position)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_delivery_tracking` (
    `order_id`            INT UNSIGNED NOT NULL,
    `logistics_user_id`   INT UNSIGNED NOT NULL,
    `latitude`            DECIMAL(10,8) NOT NULL DEFAULT 0,
    `longitude`           DECIMAL(11,8) NOT NULL DEFAULT 0,
    `accuracy`            DECIMAL(8,2)  NULL,
    `heading`             DECIMAL(6,2)  NULL,
    `speed`               DECIMAL(8,2)  NULL,
    `is_active`           TINYINT(1)    NOT NULL DEFAULT 1,
    `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`order_id`),
    KEY `idx_odt_logistics` (`logistics_user_id`),
    CONSTRAINT `fk_odt_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_odt_logistics` FOREIGN KEY (`logistics_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- order_tracking_points (route breadcrumb)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_tracking_points` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`    INT UNSIGNED NOT NULL,
    `latitude`    DECIMAL(10,8) NOT NULL,
    `longitude`   DECIMAL(11,8) NOT NULL,
    `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_otp_order_time` (`order_id`, `recorded_at`),
    CONSTRAINT `fk_otp_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- order_items
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`              INT UNSIGNED NOT NULL,
    `product_id`            INT UNSIGNED DEFAULT NULL,
    `farmer_id`             INT UNSIGNED NOT NULL,
    `product_name_snapshot` VARCHAR(191) NOT NULL,
    `price_snapshot`        DECIMAL(10,2) NOT NULL,
    `quantity`              INT UNSIGNED NOT NULL,
    `subtotal`              DECIMAL(12,2) NOT NULL,
    `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order`   (`order_id`),
    KEY `idx_order_items_product` (`product_id`),
    KEY `idx_order_items_farmer`  (`farmer_id`),
    CONSTRAINT `fk_order_items_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_order_items_farmer`  FOREIGN KEY (`farmer_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- farmer_logistics (ownership)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `farmer_logistics` (
    `farmer_id`          INT UNSIGNED NOT NULL,
    `logistics_user_id` INT UNSIGNED NOT NULL,
    `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`farmer_id`, `logistics_user_id`),
    KEY `idx_farmer_logistics_farmer` (`farmer_id`),
    KEY `idx_farmer_logistics_user` (`logistics_user_id`),
    CONSTRAINT `fk_farmer_logistics_farmer`
        FOREIGN KEY (`farmer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_farmer_logistics_user`
        FOREIGN KEY (`logistics_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- order_logistics_assignments (per-farmer delivery assignee)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_logistics_assignments` (
    `order_id`            INT UNSIGNED NOT NULL,
    `farmer_id`           INT UNSIGNED NOT NULL,
    `logistics_user_id`   INT UNSIGNED NOT NULL,
    `assigned_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`order_id`, `farmer_id`),
    KEY `idx_ola_logistics` (`logistics_user_id`),
    CONSTRAINT `fk_ola_order`     FOREIGN KEY (`order_id`)          REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ola_farmer`    FOREIGN KEY (`farmer_id`)         REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ola_logistics` FOREIGN KEY (`logistics_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- market_prices
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `market_prices` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id`     INT UNSIGNED NOT NULL,
    `unit_type`       VARCHAR(30)  NOT NULL DEFAULT 'kg',
    `reference_price` DECIMAL(10,2) NOT NULL,
    `effective_date`  DATE NOT NULL,
    `notes`           TEXT DEFAULT NULL,
    `admin_id`        INT UNSIGNED NOT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_market_prices_cat_date` (`category_id`, `effective_date`),
    KEY `idx_market_prices_admin`    (`admin_id`),
    CONSTRAINT `fk_market_prices_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_market_prices_admin`    FOREIGN KEY (`admin_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
