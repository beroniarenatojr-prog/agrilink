<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/MarketPrice.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/market-prices/create.php');
    exit;
}

csrf_verify();

$user       = currentUser();
$pdo        = Database::getInstance();
$mpModel    = new MarketPrice($pdo);

$categoryId    = (int)($_POST['category_id'] ?? 0);
$unitType      = trim($_POST['unit_type']    ?? 'kg');
$refPrice      = (float)($_POST['reference_price'] ?? 0);
$effectiveDate = trim($_POST['effective_date'] ?? '');
$notes         = trim($_POST['notes'] ?? '');

if (!$categoryId || $refPrice <= 0 || !$effectiveDate) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Category, price, and effective date are required.'];
    redirect('/admin/market-prices/create.php');
    exit;
}

$mpModel->create([
    'category_id'     => $categoryId,
    'unit_type'       => $unitType,
    'reference_price' => $refPrice,
    'effective_date'  => $effectiveDate,
    'notes'           => $notes ?: null,
    'admin_id'        => (int)$user['id'],
]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Reference price added.'];
redirect('/admin/market-prices.php');
exit;
