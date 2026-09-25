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

$cart = new Cart();
$cart->clear();

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Cart cleared.'];
redirect('/cart/index.php');
exit;
