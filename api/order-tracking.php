<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/DeliveryTracking.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$orderId = (int) ($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order']);
    exit;
}

$pdo = Database::getInstance();
$orderModel = new Order($pdo);
$trackingModel = new DeliveryTracking($pdo);
$user = currentUser();
$userId = (int) $user['id'];
$role = $user['role'] ?? '';

$order = $orderModel->findById($orderId);
if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$allowed = false;

if ($role === 'buyer' && (int) $order['buyer_id'] === $userId) {
    $allowed = true;
} elseif ($role === 'logistics' && $orderModel->isAssignedToLogisticsUser($orderId, $userId)) {
    $allowed = true;
} elseif ($role === 'farmer') {
    $items = $orderModel->getItems($orderId);
    foreach ($items as $item) {
        if ((int) $item['farmer_id'] === $userId) {
            $allowed = true;
            break;
        }
    }
}

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$snapshot = $trackingModel->getSnapshot($orderId);
if (!$snapshot) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

echo json_encode($snapshot, JSON_UNESCAPED_UNICODE);
exit;
