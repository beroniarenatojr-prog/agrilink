-- Structured Philippine address fields

ALTER TABLE `users`
    ADD COLUMN `address_province` VARCHAR(100) NULL AFTER `address`,
    ADD COLUMN `address_city` VARCHAR(100) NULL AFTER `address_province`,
    ADD COLUMN `address_barangay` VARCHAR(100) NULL AFTER `address_city`,
    ADD COLUMN `address_street` VARCHAR(255) NULL AFTER `address_barangay`;

ALTER TABLE `orders`
    ADD COLUMN `delivery_province` VARCHAR(100) NULL AFTER `delivery_address`,
    ADD COLUMN `delivery_city` VARCHAR(100) NULL AFTER `delivery_province`,
    ADD COLUMN `delivery_barangay` VARCHAR(100) NULL AFTER `delivery_city`,
    ADD COLUMN `delivery_street` VARCHAR(255) NULL AFTER `delivery_barangay`;
