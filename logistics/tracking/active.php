<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/DeliveryTracking.php';

header('Content-Type: application/json; charset=utf-8');

requireRole('logistics');

$user = currentUser();
$pdo = Database::getInstance();
$trackingModel = new DeliveryTracking($pdo);

$rows = $trackingModel->getActiveInTransitOrdersForLogistics((int) $user['id']);
$orders = [];

foreach ($rows as $row) {
    $orders[] = ['id' => (int) $row['id']];
}

echo json_encode(['orders' => $orders], JSON_UNESCAPED_UNICODE);
exit;
