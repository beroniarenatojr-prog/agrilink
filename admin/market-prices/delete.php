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

$priceId = (int)($_POST['price_id'] ?? 0);
if ($priceId > 0) {
    $mpModel->delete($priceId);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Reference price deleted.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
}

redirect('/admin/market-prices.php');
exit;
