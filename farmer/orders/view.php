<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/Order.php';
require_once __DIR__ . '/../../classes/FarmerLogistics.php';

requireRole('farmer');

$pdo            = Database::getInstance();
$orderModel     = new Order($pdo);
$logisticsModel = new FarmerLogistics($pdo);
$user           = currentUser();
$farmerId       = (int) $user['id'];

$orderId = (int) ($_GET['id'] ?? 0);
$order   = $orderModel->findById($orderId);

$allItems = $orderModel->getItems($orderId);
$myItems  = array_filter($allItems, fn($i) => (int) $i['farmer_id'] === $farmerId);

if (!$order || empty($myItems)) {
    http_response_code(404);
    $pageTitle = 'Order Not Found';
    $activeNav = 'orders';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="empty-state"><h2>Order not found</h2><a href="' . url('/farmer/orders.php') . '" class="btn btn-agri">All Orders</a></div>';
    require_once __DIR__ . '/../../includes/sidebar_end.php';
    exit;
}

$status          = $order['logistics_status'];
$assignment      = $orderModel->getLogisticsAssignment($orderId, $farmerId);
$activeStaff     = $logisticsModel->listActiveLogisticsUsersForFarmer($farmerId);
$hasActiveStaff  = !empty($activeStaff);
$canAssign       = in_array($status, ['pending', 'processing'], true);

$pageTitle = 'Order #' . $orderId;
$activeNav = 'orders';
$extraHead = '';
$extraScripts = '';

if ($status === 'in_transit') {
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
        . 'AgriTrackingMap.init("farmerTrackingMap",{lat:' . $mapLat . ',lng:' . $mapLng . '});'
        . 'AgriBuyerTracking.start(' . $orderId . ',"' . url('/api/order-tracking.php') . '",' . TRACKING_POLL_MS . ');'
        . '});</script>';
}

require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Order #<?= $orderId ?></h1>
        <p class="page-subtitle mb-0">Placed <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></p>
    </div>
    <a href="<?= url('/farmer/orders.php') ?>" class="btn btn-agri-outline"><i class="ti ti-arrow-left me-1"></i> All Orders</a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Your Items in This Order</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-agri align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myItems as $item): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= peso((float) $item['price_snapshot']) ?></td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td class="text-end fw-600"><?= peso((float) $item['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($status === 'in_transit'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Live Delivery Map</div>
            <div class="card-body p-3">
                <div id="trackingStatusBar" class="tracking-status-bar">
                    <span class="tracking-live-dot"></span>
                    <span class="tracking-status-msg text-muted">Loading tracking…</span>
                    <span class="tracking-status-time text-muted" style="font-size:0.8rem"></span>
                </div>
                <div id="farmerTrackingMap" class="tracking-map tracking-map--compact"></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($status, ['pending', 'processing'], true)): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Update Status</div>
            <div class="card-body p-4">
                <form method="post" action="<?= url('/farmer/orders/status.php') ?>" class="row g-3" id="statusForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">

                    <div class="col-12 col-md-6">
                        <label for="new_status" class="form-label">New status</label>
                        <select name="new_status" id="new_status" class="form-select" required>
                            <?php if ($status === 'pending'): ?>
                                <option value="processing">Processing</option>
                                <option value="cancelled">Cancelled</option>
                            <?php elseif ($status === 'processing'): ?>
                                <option value="in_transit">In Transit</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <?php if ($status === 'processing' && $hasActiveStaff): ?>
                    <div class="col-12 col-md-6" id="logisticsSelectWrap">
                        <label for="status_logistics_user_id" class="form-label">Logistics assignee</label>
                        <select name="logistics_user_id" id="status_logistics_user_id" class="form-select" required>
                            <option value="">Select personnel…</option>
                            <?php foreach ($activeStaff as $person): ?>
                                <option value="<?= (int) $person['id'] ?>"
                                    <?= $assignment && (int) $assignment['logistics_user_id'] === (int) $person['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-12" id="cancelReasonWrap" style="display:none">
                        <label for="cancel_reason" class="form-label">Cancellation reason</label>
                        <textarea class="form-control" id="cancel_reason" name="reason" rows="3"></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-agri">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        (function() {
            const statusSelect = document.getElementById('new_status');
            const cancelWrap = document.getElementById('cancelReasonWrap');
            const logisticsWrap = document.getElementById('logisticsSelectWrap');
            const logisticsSelect = document.getElementById('status_logistics_user_id');

            function syncFields() {
                if (cancelWrap) {
                    cancelWrap.style.display = statusSelect.value === 'cancelled' ? 'block' : 'none';
                }
                if (logisticsWrap && logisticsSelect) {
                    const show = statusSelect.value === 'in_transit';
                    logisticsWrap.style.display = show ? 'block' : 'none';
                    logisticsSelect.required = show;
                }
            }

            statusSelect.addEventListener('change', syncFields);
            syncFields();
        })();
        </script>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Order Status</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <div class="mb-2"><?= statusBadge($status) ?></div>
                <?php if ($order['cancellation_reason']): ?>
                    <div class="text-muted">Reason: <?= htmlspecialchars($order['cancellation_reason'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Delivery Assignment</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <?php if ($assignment): ?>
                    <div class="fw-600 mb-1"><?= htmlspecialchars($assignment['logistics_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted mb-1"><?= htmlspecialchars($assignment['logistics_email'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($assignment['logistics_phone']): ?>
                        <div class="text-muted mb-1"><?= htmlspecialchars($assignment['logistics_phone'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="text-muted">Assigned <?= date('M j, Y g:i A', strtotime($assignment['assigned_at'])) ?></div>
                <?php else: ?>
                    <p class="text-muted mb-2">Unassigned</p>
                    <?php if (!$hasActiveStaff): ?>
                        <a href="<?= url('/farmer/logistics.php') ?>" class="btn btn-sm btn-agri-outline">Add logistics staff</a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($canAssign && $hasActiveStaff): ?>
                <form method="post" action="<?= url('/farmer/orders/assign.php') ?>" class="mt-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                    <label for="assign_logistics_user_id" class="form-label">Assign to</label>
                    <select name="logistics_user_id" id="assign_logistics_user_id" class="form-select mb-2" required>
                        <option value="">Select personnel…</option>
                        <?php foreach ($activeStaff as $person): ?>
                            <option value="<?= (int) $person['id'] ?>"
                                <?= $assignment && (int) $assignment['logistics_user_id'] === (int) $person['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($person['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-agri w-100">Assign Delivery</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Buyer Delivery Info</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <div class="fw-600 mb-1"><?= htmlspecialchars($order['buyer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="mb-1"><span class="text-muted">Phone:</span> <?= htmlspecialchars($order['delivery_phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div><span class="text-muted">Address:</span> <?= htmlspecialchars($order['delivery_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/sidebar_end.php'; ?>
