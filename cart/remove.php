<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Cart.php';

requireRole('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cart/index.php');
    exit;
}

csrf_verify();

$productId = (int)($_POST['product_id'] ?? 0);
$cart = new Cart();
$cart->remove($productId);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Item removed from cart.'];
redirect('/cart/index.php');
exit;
