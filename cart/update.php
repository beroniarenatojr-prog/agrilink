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
$quantity  = (int)($_POST['quantity']   ?? 0);

$cart = new Cart();
$ok   = $cart->update($productId, $quantity);

if (!$ok) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Could not update quantity — exceeds available stock.'];
}

redirect('/cart/index.php');
exit;
