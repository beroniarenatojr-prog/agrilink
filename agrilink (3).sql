-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2026 at 06:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agrilink`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Vegetables', 'vegetables', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(2, 'Fruits', 'fruits', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(3, 'Grains & Cereals', 'grains-cereals', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(4, 'Livestock & Poultry', 'livestock-poultry', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(5, 'Herbs & Spices', 'herbs-spices', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(6, 'Root Crops', 'root-crops', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(7, 'Seafood', 'seafood', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(8, 'Dairy & Eggs', 'dairy-eggs', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(9, 'Others', 'others', '2026-06-23 19:35:58', '2026-06-23 19:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_logistics`
--

CREATE TABLE `farmer_logistics` (
  `farmer_id` int(10) UNSIGNED NOT NULL,
  `logistics_user_id` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farmer_logistics`
--

INSERT INTO `farmer_logistics` (`farmer_id`, `logistics_user_id`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 5, 1, '2026-06-24 16:31:54', '2026-06-24 16:54:33');

-- --------------------------------------------------------

--
-- Table structure for table `market_prices`
--

CREATE TABLE `market_prices` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `unit_type` varchar(30) NOT NULL DEFAULT 'kg',
  `reference_price` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `delivery_phone` varchar(30) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_province` varchar(100) DEFAULT NULL,
  `delivery_city` varchar(100) DEFAULT NULL,
  `delivery_barangay` varchar(100) DEFAULT NULL,
  `delivery_street` varchar(255) DEFAULT NULL,
  `delivery_lat` decimal(10,8) DEFAULT NULL,
  `delivery_lng` decimal(11,8) DEFAULT NULL,
  `payment_method` enum('cod','meetup') NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `logistics_status` enum('pending','processing','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `in_transit_at` timestamp NULL DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cancellation_reason` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_delivery_tracking`
--

CREATE TABLE `order_delivery_tracking` (
  `order_id` int(10) UNSIGNED NOT NULL,
  `logistics_user_id` int(10) UNSIGNED NOT NULL,
  `latitude` decimal(10,8) NOT NULL DEFAULT 0.00000000,
  `longitude` decimal(11,8) NOT NULL DEFAULT 0.00000000,
  `accuracy` decimal(8,2) DEFAULT NULL,
  `heading` decimal(6,2) DEFAULT NULL,
  `speed` decimal(8,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `farmer_id` int(10) UNSIGNED NOT NULL,
  `product_name_snapshot` varchar(191) NOT NULL,
  `price_snapshot` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_logistics_assignments`
--

CREATE TABLE `order_logistics_assignments` (
  `order_id` int(10) UNSIGNED NOT NULL,
  `farmer_id` int(10) UNSIGNED NOT NULL,
  `logistics_user_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking_points`
--

CREATE TABLE `order_tracking_points` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `farmer_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `unit_type` varchar(30) NOT NULL DEFAULT 'kg',
  `price_per_unit` decimal(10,2) NOT NULL,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `additional_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_images`)),
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `address_province` varchar(100) DEFAULT NULL,
  `address_city` varchar(100) DEFAULT NULL,
  `address_barangay` varchar(100) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `role` enum('admin','farmer','buyer','logistics') NOT NULL DEFAULT 'buyer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `address_province`, `address_city`, `address_barangay`, `address_street`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@agrilink.com', '$2y$10$41noixB58LculNwwZZOut.ZBAxOms0KYxVTG1bCLb/5GjC/5lJeR.', '09171234567', 'Manila, Philippines', NULL, NULL, NULL, NULL, 'admin', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(2, 'Juan dela Cruz', 'farmer@agrilink.com', '$2y$10$enBYmm/B0hNbRRZ533wUZefkKqO0.3MnX7h.ves/qjCU7I2xmFuyO', '09181234567', 'Benguet, Philippines', NULL, NULL, NULL, NULL, 'farmer', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(3, 'Maria Santos', 'buyer@agrilink.com', '$2y$10$lRIPF6d6Kp4WCogD21cm7e/EpvP5eWivZ2DQkptqk5zCWg42La3UC', '09191234567', 'Quezon City, Philippines', NULL, NULL, NULL, NULL, 'buyer', '2026-06-23 19:35:58', '2026-06-23 19:35:58'),
(5, 'Maria L. Santos', 'maria@agrilink.com', '$2y$10$pZlOplP2Yvi6zSIl.0/Oke1iliIgFwenXFKYAnG3VsgQ3W7UF87qS', '09134657377', '123 Main St., Brgy. San Roque, Quezon City, Metro Manila', NULL, NULL, NULL, NULL, 'logistics', '2026-06-24 16:31:54', '2026-06-24 16:43:00'),
(6, 'John Doe', 'john@agrilink.com', '$2y$10$ARMUhxMSiTUn5P82PWasI.rU2aU0rpjO4HQj5CEm1tiQRgpL.3azm', '09159876533', 'narra st., Alibagu, Ilagan, Isabela, Philippines', 'Isabela', 'Ilagan', 'Alibagu', 'narra st.', 'buyer', '2026-06-26 16:03:17', '2026-06-26 16:03:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_slug` (`slug`);

--
-- Indexes for table `farmer_logistics`
--
ALTER TABLE `farmer_logistics`
  ADD PRIMARY KEY (`farmer_id`,`logistics_user_id`),
  ADD KEY `idx_farmer_logistics_farmer` (`farmer_id`),
  ADD KEY `idx_farmer_logistics_user` (`logistics_user_id`);

--
-- Indexes for table `market_prices`
--
ALTER TABLE `market_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_market_prices_cat_date` (`category_id`,`effective_date`),
  ADD KEY `idx_market_prices_admin` (`admin_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_buyer` (`buyer_id`),
  ADD KEY `idx_orders_status` (`logistics_status`);

--
-- Indexes for table `order_delivery_tracking`
--
ALTER TABLE `order_delivery_tracking`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_odt_logistics` (`logistics_user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`),
  ADD KEY `idx_order_items_farmer` (`farmer_id`);

--
-- Indexes for table `order_logistics_assignments`
--
ALTER TABLE `order_logistics_assignments`
  ADD PRIMARY KEY (`order_id`,`farmer_id`),
  ADD KEY `idx_ola_logistics` (`logistics_user_id`),
  ADD KEY `fk_ola_farmer` (`farmer_id`);

--
-- Indexes for table `order_tracking_points`
--
ALTER TABLE `order_tracking_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_otp_order_time` (`order_id`,`recorded_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_farmer` (`farmer_id`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_available` (`is_available`,`stock_quantity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `market_prices`
--
ALTER TABLE `market_prices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_tracking_points`
--
ALTER TABLE `order_tracking_points`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `farmer_logistics`
--
ALTER TABLE `farmer_logistics`
  ADD CONSTRAINT `fk_farmer_logistics_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_farmer_logistics_user` FOREIGN KEY (`logistics_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `market_prices`
--
ALTER TABLE `market_prices`
  ADD CONSTRAINT `fk_market_prices_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_market_prices_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_delivery_tracking`
--
ALTER TABLE `order_delivery_tracking`
  ADD CONSTRAINT `fk_odt_logistics` FOREIGN KEY (`logistics_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_odt_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_logistics_assignments`
--
ALTER TABLE `order_logistics_assignments`
  ADD CONSTRAINT `fk_ola_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ola_logistics` FOREIGN KEY (`logistics_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ola_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_tracking_points`
--
ALTER TABLE `order_tracking_points`
  ADD CONSTRAINT `fk_otp_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
