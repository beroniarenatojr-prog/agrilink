<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Order.php';
require_once __DIR__ . '/../../classes/DeliveryTracking.php';

header('Content-Type: application/json; charset=utf-8');

requireRole('logistics');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

csrf_verify();

$user = currentUser();
$logisticsUserId = (int) $user['id'];
$orderId = (int) ($_POST['order_id'] ?? 0);
$latitude = isset($_POST['latitude']) ? (float) $_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? (float) $_POST['longitude'] : null;

if ($orderId <= 0 || $latitude === null || $longitude === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

$pdo = Database::getInstance();
$orderModel = new Order($pdo);
$trackingModel = new DeliveryTracking($pdo);

if (!$orderModel->isAssignedToLogisticsUser($orderId, $logisticsUserId)) {
    http_response_code(403);
    echo json_encode(['error' => 'Not assigned to this order']);
    exit;
}

$order = $orderModel->findById($orderId);
if (!$order || $order['logistics_status'] !== 'in_transit') {
    http_response_code(400);
    echo json_encode(['error' => 'Order is not in transit']);
    exit;
}

$accuracy = isset($_POST['accuracy']) && $_POST['accuracy'] !== '' ? (float) $_POST['accuracy'] : null;
$heading  = isset($_POST['heading']) && $_POST['heading'] !== '' ? (float) $_POST['heading'] : null;
$speed    = isset($_POST['speed']) && $_POST['speed'] !== '' ? (float) $_POST['speed'] : null;

$ok = $trackingModel->updatePosition(
    $orderId,
    $logisticsUserId,
    $latitude,
    $longitude,
    $accuracy,
    $heading,
    $speed
);

if (!$ok) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Update skipped or not active']);
    exit;
}

echo json_encode(['ok' => true]);
exit;
