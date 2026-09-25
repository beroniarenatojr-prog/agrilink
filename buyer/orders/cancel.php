<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Order.php';

requireRole('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/buyer/orders.php');
    exit;
}

csrf_verify();

$user       = currentUser();
$pdo        = Database::getInstance();
$orderModel = new Order($pdo);
$orderId    = (int)($_POST['order_id'] ?? 0);
$order      = $orderModel->findById($orderId);

if (!$order || (int)$order['buyer_id'] !== (int)$user['id'] || $order['logistics_status'] !== 'pending') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot cancel this order.'];
    redirect('/buyer/orders.php');
    exit;
}

$reason = trim($_POST['reason'] ?? '');
$orderModel->restoreStockForOrder($orderId);
$orderModel->updateStatus($orderId, 'cancelled', ['cancellation_reason' => $reason]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Order cancelled.'];
redirect('/buyer/orders/view.php?id=' . $orderId);
exit;
