-- AgriLink Seed Data
-- Run after schema.sql
-- Passwords: admin1234, farmer1234, buyer1234, logistics1234 (bcrypt)

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------
-- Demo users
-- -------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `role`) VALUES
(
    'Admin User',
    'admin@agrilink.com',
    '$2y$10$41noixB58LculNwwZZOut.ZBAxOms0KYxVTG1bCLb/5GjC/5lJeR.',
    '09171234567',
    'Manila, Philippines',
    'admin'
),
(
    'Juan dela Cruz',
    'farmer@agrilink.com',
    '$2y$10$enBYmm/B0hNbRRZ533wUZefkKqO0.3MnX7h.ves/qjCU7I2xmFuyO',
    '09181234567',
    'Benguet, Philippines',
    'farmer'
),
(
    'Maria Santos',
    'buyer@agrilink.com',
    '$2y$10$lRIPF6d6Kp4WCogD21cm7e/EpvP5eWivZ2DQkptqk5zCWg42La3UC',
    '09191234567',
    'Quezon City, Philippines',
    'buyer'
),
(
    'Maria L. Santos',
    'maria@agrilink.com',
    '$2y$10$9ToTjaOxjcJgNdeUfuFRyeVOx//t/QexiaHl6FrP5vctKkOvAxZ6O',
    '09134657377',
    '123 Main St., Brgy. San Roque, Quezon City, Metro Manila',
    'logistics'
);

-- -------------------------------------------------------
-- Categories
-- -------------------------------------------------------
INSERT INTO `categories` (`name`, `slug`) VALUES
('Vegetables',        'vegetables'),
('Fruits',            'fruits'),
('Grains & Cereals',  'grains-cereals'),
('Livestock & Poultry','livestock-poultry'),
('Herbs & Spices',    'herbs-spices'),
('Root Crops',        'root-crops'),
('Seafood',           'seafood'),
('Dairy & Eggs',      'dairy-eggs'),
('Others',            'others');

-- -------------------------------------------------------
-- Reference prices (5 rows, admin_id = 1)
-- -------------------------------------------------------
INSERT INTO `market_prices` (`category_id`, `unit_type`, `reference_price`, `effective_date`, `notes`, `admin_id`) VALUES
(1, 'kg',   45.00, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'DA regional baseline - vegetables',  1),
(1, 'kg',   50.00, DATE_SUB(CURDATE(), INTERVAL 7  DAY), 'Updated DA price bulletin',          1),
(2, 'kg',   60.00, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'DA reference - mixed fruits',        1),
(3, 'sack', 1800.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'NFA palay/rice reference',         1),
(6, 'kg',   35.00, DATE_SUB(CURDATE(), INTERVAL 5  DAY), 'Root crops benchmark - camote/gabi', 1);

SET FOREIGN_KEY_CHECKS = 1;
