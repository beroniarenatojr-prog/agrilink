<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/Order.php';
require_once __DIR__ . '/../../classes/FarmerLogistics.php';

requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/farmer/orders.php');
    exit;
}

csrf_verify();

$user       = currentUser();
$farmerId   = (int) $user['id'];
$pdo        = Database::getInstance();
$orderModel = new Order($pdo);
$logisticsModel = new FarmerLogistics($pdo);

$orderId         = (int) ($_POST['order_id'] ?? 0);
$logisticsUserId = (int) ($_POST['logistics_user_id'] ?? 0);

$order    = $orderModel->findById($orderId);
$allItems = $orderModel->getItems($orderId);
$myItems  = array_filter($allItems, fn($i) => (int) $i['farmer_id'] === $farmerId);

if (!$order || empty($myItems)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Order not found.'];
    redirect('/farmer/orders.php');
    exit;
}

if (!in_array($order['logistics_status'], ['pending', 'processing'], true)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'This order can no longer be assigned.'];
    redirect('/farmer/orders/view.php?id=' . $orderId);
    exit;
}

if ($logisticsUserId <= 0 || !$logisticsModel->userIsActiveForFarmer($farmerId, $logisticsUserId)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please select an active logistics person.'];
    redirect('/farmer/orders/view.php?id=' . $orderId);
    exit;
}

$orderModel->assignLogistics($orderId, $farmerId, $logisticsUserId);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Delivery assigned to logistics personnel.'];
redirect('/farmer/orders/view.php?id=' . $orderId);
exit;
