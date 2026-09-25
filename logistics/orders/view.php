<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/Order.php';
require_once __DIR__ . '/../../classes/FarmerLogistics.php';

requireRole('logistics');

$pdo = Database::getInstance();
$orderModel = new Order($pdo);
$logisticsModel = new FarmerLogistics($pdo);
$user = currentUser();
$logisticsUserId = (int) $user['id'];

$orderId = (int) ($_GET['id'] ?? 0);
$order   = $orderModel->findById($orderId);

if (!$order || !$orderModel->isAssignedToLogisticsUser($orderId, $logisticsUserId)) {
    http_response_code(404);
    $pageTitle = 'Order Not Found';
    $activeNav = 'assigned-orders';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="empty-state"><h2>Order not found</h2><a href="' . url('/logistics/assigned-orders.php') . '" class="btn btn-agri">Assigned Orders</a></div>';
    require_once __DIR__ . '/../../includes/sidebar_end.php';
    exit;
}

$allItems = $orderModel->getItems($orderId);

$stmt = $pdo->prepare(
    "SELECT ola.farmer_id, f.name AS farmer_name, ola.assigned_at
     FROM order_logistics_assignments ola
     JOIN users f ON f.id = ola.farmer_id
     WHERE ola.order_id = ? AND ola.logistics_user_id = ?"
);
$stmt->execute([$orderId, $logisticsUserId]);
$assignments = $stmt->fetchAll();
$farmerIds = array_map('intval', array_column($assignments, 'farmer_id'));

$myItems = array_filter($allItems, fn($i) => in_array((int) $i['farmer_id'], $farmerIds, true));

$assignedFarmer = $logisticsModel->getAssignedFarmerForLogisticsUser($logisticsUserId);

$pageTitle = 'Order #' . $orderId;
$activeNav = 'assigned-orders';
$extraHead = '';
$extraScripts = '';

if ($order['logistics_status'] === 'in_transit') {
    $coords = $orderModel->ensureDeliveryCoordinates($orderId);
    if ($coords !== null) {
        $order['delivery_lat'] = $coords['lat'];
        $order['delivery_lng'] = $coords['lng'];
    }
    require_once __DIR__ . '/../../includes/tracking_assets.php';
    $extraHead = tracking_map_head();
    $mapLat = $order['delivery_lat'] !== null ? (float) $order['delivery_lat'] : 14.5995;
    $mapLng = $order['delivery_lng'] !== null ? (float) $order['delivery_lng'] : 120.9842;
    $extraScripts = '<script src="' . url('/assets/js/buyer-tracking.js') . '"></script>'
        . '<script>document.addEventListener("DOMContentLoaded",function(){'
        . 'AgriTrackingMap.init("logisticsTrackingMap",{lat:' . $mapLat . ',lng:' . $mapLng . ',viewerRole:"logistics"});'
        . 'AgriBuyerTracking.start(' . $orderId . ',"' . url('/api/order-tracking.php') . '",' . TRACKING_POLL_MS . ');'
        . '});</script>';
}

require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Order #<?= $orderId ?></h1>
        <p class="page-subtitle mb-0">Assigned <?= date('F j, Y g:i A', strtotime($assignments[0]['assigned_at'] ?? $order['created_at'])) ?></p>
    </div>
    <a href="<?= url('/logistics/assigned-orders.php') ?>" class="btn btn-agri-outline"><i class="ti ti-arrow-left me-1"></i> Assigned Orders</a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Items to Deliver</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-agri align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Farmer</th>
                                <th>Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myItems as $item): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['farmer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td class="text-end fw-600"><?= peso((float) $item['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($order['logistics_status'] === 'in_transit'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Delivery Route</div>
            <div class="card-body p-3">
                <p class="text-muted mb-2" style="font-size:0.85rem">Your location is shared automatically while this order is in transit. Keep this tab open.</p>
                <div id="trackingStatusBar" class="tracking-status-bar">
                    <span class="tracking-live-dot is-live"></span>
                    <span class="tracking-status-msg">Sharing your location</span>
                    <span class="tracking-status-time text-muted"></span>
                </div>
                <div id="logisticsTrackingMap" class="tracking-map tracking-map--compact"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Order Status</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <div class="mb-2"><?= statusBadge($order['logistics_status']) ?></div>
                <p class="text-muted mb-0">Status updates are managed by the farmer.</p>
            </div>
        </div>

        <?php if ($assignedFarmer): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Assigned Farmer</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <div class="fw-600 mb-1"><?= htmlspecialchars($assignedFarmer['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted"><?= htmlspecialchars($assignedFarmer['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Buyer Delivery Info</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <div class="fw-600 mb-1"><?= htmlspecialchars($order['buyer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="mb-1"><span class="text-muted">Phone:</span> <?= htmlspecialchars($order['delivery_phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="mb-2"><span class="text-muted">Address:</span> <?= htmlspecialchars($order['delivery_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div><span class="text-muted">Payment:</span> <?= strtoupper(htmlspecialchars($order['payment_method'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/sidebar_end.php'; ?>
