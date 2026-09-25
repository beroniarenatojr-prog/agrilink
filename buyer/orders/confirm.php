<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Order.php';
require_once __DIR__ . '/../../classes/DeliveryTracking.php';

requireRole('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/buyer/orders.php');
    exit;
}

csrf_verify();

$user       = currentUser();
$pdo        = Database::getInstance();
$orderModel = new Order($pdo);
$trackingModel = new DeliveryTracking($pdo);
$orderId    = (int)($_POST['order_id'] ?? 0);
$order      = $orderModel->findById($orderId);

if (!$order || (int)$order['buyer_id'] !== (int)$user['id'] || $order['logistics_status'] !== 'in_transit') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot confirm this order.'];
    redirect('/buyer/orders.php');
    exit;
}

$orderModel->updateStatus($orderId, 'delivered');
$trackingModel->deactivate($orderId);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Order confirmed as received. Thank you!'];
redirect('/buyer/orders/view.php?id=' . $orderId);
exit;
