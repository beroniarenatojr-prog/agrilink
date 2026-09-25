<?php
/**
 * Auto-delivery cron job.
 * Marks 'in_transit' orders as 'delivered' after 3 days.
 *
 * Run daily via cron:
 *   0 2 * * * php /path/to/agrishieldv2/cron/auto_deliver.php >> /var/log/agrilink_cron.log 2>&1
 *
 * On Windows (Task Scheduler), run:
 *   php C:\xampp\htdocs\agrishieldv2\cron\auto_deliver.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/DeliveryTracking.php';

$pdo = Database::getInstance();
$trackingModel = new DeliveryTracking($pdo);

// Find eligible orders: in_transit for >= 3 days
$stmt = $pdo->prepare(
    "SELECT id FROM orders
     WHERE logistics_status = 'in_transit'
       AND in_transit_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)"
);
$stmt->execute();
$orders = $stmt->fetchAll();

if (empty($orders)) {
    echo date('[Y-m-d H:i:s]') . " No orders to auto-deliver.\n";
    exit;
}

$updateStmt = $pdo->prepare(
    "UPDATE orders
     SET logistics_status = 'delivered',
         payment_status   = 'paid',
         completed_at     = NOW(),
         updated_at       = NOW()
     WHERE id = ? AND logistics_status = 'in_transit'"
);

$count = 0;
foreach ($orders as $order) {
    $updateStmt->execute([$order['id']]);
    if ($updateStmt->rowCount() > 0) {
        $trackingModel->deactivate((int) $order['id']);
        $count++;
        echo date('[Y-m-d H:i:s]') . " Auto-delivered order #{$order['id']}\n";
    }
}

echo date('[Y-m-d H:i:s]') . " Done. $count order(s) auto-delivered.\n";
