-- Migration: live delivery tracking (coords + rider position)
-- Run on existing agrilink databases after 001_order_logistics_assignments.sql

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `orders`
  ADD COLUMN `delivery_lat` DECIMAL(10,8) NULL AFTER `delivery_address`,
  ADD COLUMN `delivery_lng` DECIMAL(11,8) NULL AFTER `delivery_lat`;

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

SET FOREIGN_KEY_CHECKS = 1;
