<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/Product.php';

requireRole('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/marketplace/index.php');
    exit;
}

csrf_verify();

$productId = (int)($_POST['product_id'] ?? 0);
$quantity  = (int)($_POST['quantity']   ?? 1);

if ($productId <= 0 || $quantity < 1) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid request.'];
    redirect('/marketplace/index.php');
    exit;
}

$pdo     = Database::getInstance();
$prodModel = new Product($pdo);
$product   = $prodModel->findById($productId);

if (!$product || !$product['is_available'] || $product['stock_quantity'] <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Product is no longer available.'];
    redirect('/marketplace/index.php');
    exit;
}

$cart = new Cart();
$ok   = $cart->add(
    $productId,
    $product['name'],
    (float)$product['price_per_unit'],
    $quantity,
    $product['unit_type'],
    (int)$product['farmer_id'],
    $product['image'],
    (int)$product['stock_quantity']
);

if ($ok) {
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Added to cart.'];
} else {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Cannot add more than available stock.'];
}

$redirect = $_SERVER['HTTP_REFERER'] ?? url('/marketplace/index.php');
header('Location: ' . $redirect);
exit;
