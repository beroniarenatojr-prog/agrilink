-- Migration: order_logistics_assignments
-- Run on existing agrilink databases that were created before this table existed.

SET FOREIGN_KEY_CHECKS = 0;

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

SET FOREIGN_KEY_CHECKS = 1;
