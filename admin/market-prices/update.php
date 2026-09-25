<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/MarketPrice.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/market-prices.php');
    exit;
}

csrf_verify();

$pdo     = Database::getInstance();
$mpModel = new MarketPrice($pdo);

$priceId       = (int)($_POST['price_id'] ?? 0);
$categoryId    = (int)($_POST['category_id'] ?? 0);
$unitType      = trim($_POST['unit_type']    ?? 'kg');
$refPrice      = (float)($_POST['reference_price'] ?? 0);
$effectiveDate = trim($_POST['effective_date'] ?? '');
$notes         = trim($_POST['notes'] ?? '');

if (!$priceId || !$categoryId || $refPrice <= 0 || !$effectiveDate) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'All required fields must be filled.'];
    redirect('/admin/market-prices/edit.php?id=' . $priceId);
    exit;
}

$mpModel->update($priceId, [
    'category_id'     => $categoryId,
    'unit_type'       => $unitType,
    'reference_price' => $refPrice,
    'effective_date'  => $effectiveDate,
    'notes'           => $notes ?: null,
]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Reference price updated.'];
redirect('/admin/market-prices.php');
exit;
