<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Order.php';
require_once __DIR__ . '/../../classes/FarmerLogistics.php';
require_once __DIR__ . '/../../classes/DeliveryTracking.php';

requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/farmer/orders.php');
    exit;
}

csrf_verify();

$user           = currentUser();
$farmerId       = (int) $user['id'];
$pdo            = Database::getInstance();
$orderModel     = new Order($pdo);
$logisticsModel = new FarmerLogistics($pdo);
$trackingModel  = new DeliveryTracking($pdo);

$orderId         = (int) ($_POST['order_id'] ?? 0);
$newStatus       = $_POST['new_status'] ?? '';
$reason          = trim($_POST['reason'] ?? '');
$logisticsUserId = (int) ($_POST['logistics_user_id'] ?? 0);

$order    = $orderModel->findById($orderId);
$allItems = $orderModel->getItems($orderId);
$myItems  = array_filter($allItems, fn($i) => (int) $i['farmer_id'] === $farmerId);

if (!$order || empty($myItems)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Order not found.'];
    redirect('/farmer/orders.php');
    exit;
}

$current = $order['logistics_status'];

$allowed = match ($current) {
    'pending'    => ['processing', 'cancelled'],
    'processing' => ['in_transit'],
    default      => [],
};

if (!in_array($newStatus, $allowed, true)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid status transition.'];
    redirect('/farmer/orders/view.php?id=' . $orderId);
    exit;
}

$activeStaff = $logisticsModel->listActiveLogisticsUsersForFarmer($farmerId);

if ($newStatus === 'in_transit' && !empty($activeStaff)) {
    if ($logisticsUserId <= 0 || !$logisticsModel->userIsActiveForFarmer($farmerId, $logisticsUserId)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Assign an active logistics person before marking In Transit.'];
        redirect('/farmer/orders/view.php?id=' . $orderId);
        exit;
    }
    $orderModel->assignLogistics($orderId, $farmerId, $logisticsUserId);
}

if ($newStatus === 'cancelled') {
    $orderModel->restoreStockForOrder($orderId);
    $trackingModel->deactivate($orderId);
}

$orderModel->updateStatus($orderId, $newStatus, ['cancellation_reason' => $reason]);

if ($newStatus === 'in_transit') {
    if ($logisticsUserId <= 0) {
        $existingAssignment = $orderModel->getLogisticsAssignment($orderId, $farmerId);
        if ($existingAssignment) {
            $logisticsUserId = (int) $existingAssignment['logistics_user_id'];
        }
    }
    if ($logisticsUserId > 0) {
        $trackingModel->activate($orderId, $logisticsUserId);
    }
}

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Order status updated.'];
redirect('/farmer/orders/view.php?id=' . $orderId);
exit;
