<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/Order.php';

requireRole('buyer');

$pdo        = Database::getInstance();
$orderModel = new Order($pdo);
$user       = currentUser();

$orderId = (int)($_GET['id'] ?? 0);
$order   = $orderModel->findById($orderId);

if (!$order || (int)$order['buyer_id'] !== (int)$user['id']) {
    http_response_code(404);
    $pageTitle = 'Order Not Found';
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="empty-state"><h2>Order not found</h2><a href="' . url('/buyer/orders.php') . '" class="btn btn-agri">My Orders</a></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$items     = $orderModel->getItems($orderId);
$status    = $order['logistics_status'];

$pageTitle = 'Order #' . $orderId;
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
        . 'AgriTrackingMap.init("buyerTrackingMap",{lat:' . $mapLat . ',lng:' . $mapLng . '});'
        . 'AgriBuyerTracking.start(' . $orderId . ',"' . url('/api/order-tracking.php') . '",' . TRACKING_POLL_MS . ');'
        . '});</script>';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Order #<?= $orderId ?></h1>
        <p class="page-subtitle mb-0">Placed <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></p>
    </div>
    <a href="<?= url('/buyer/orders.php') ?>" class="btn btn-agri-outline"><i class="ti ti-arrow-left me-1"></i> My Orders</a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <!-- Items -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Items</div>
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
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= peso((float)$item['price_snapshot']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td class="text-end fw-600"><?= peso((float)$item['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-600">Total</td>
                                <td class="text-end fw-600 text-agri"><?= peso((float)$order['total_amount']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($status === 'pending'): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Cancel Order</div>
            <div class="card-body p-4">
                <form method="post" action="<?= url('/buyer/orders/cancel.php') ?>"
                      data-vex-confirm="Cancel this order? The seller will be notified."
                      data-vex-confirm-variant="danger">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label">Reason for cancellation</label>
                        <textarea class="form-control" id="cancelReason" name="reason" rows="3" placeholder="Optional — let the seller know why."></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-danger">Cancel Order</button>
                </form>
            </div>
        </div>
        <?php elseif ($status === 'in_transit'): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Live Delivery Map</div>
            <div class="card-body p-3">
                <div id="trackingStatusBar" class="tracking-status-bar">
                    <span class="tracking-live-dot"></span>
                    <span class="tracking-status-msg text-muted">Loading tracking…</span>
                    <span class="tracking-status-time text-muted" style="font-size:0.8rem"></span>
                </div>
                <div id="buyerTrackingMap" class="tracking-map"></div>
                <?php if ($order['delivery_lat'] === null || $order['delivery_lng'] === null): ?>
                    <p class="text-muted mt-2 mb-0" style="font-size:0.85rem">Delivery pin unavailable — address could not be mapped.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Confirm Receipt</div>
            <div class="card-body p-4">
                <p class="text-muted mb-3" style="font-size:0.9rem">Confirm that you have received your order. This will mark the order as delivered and release payment.</p>
                <form method="post" action="<?= url('/buyer/orders/confirm.php') ?>"
                      data-vex-confirm="Confirm you received this order? This marks the order as delivered.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                    <button type="submit" class="btn btn-agri">Confirm Received</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-4">
        <!-- Order summary -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Order Details</div>
            <div class="card-body p-3">
                <dl class="mb-0" style="font-size:0.875rem">
                    <dt class="text-muted">Status</dt>
                    <dd class="mb-2"><?= statusBadge($status) ?></dd>

                    <dt class="text-muted">Payment</dt>
                    <dd class="mb-2">
                        <?= paymentBadge($order['payment_status']) ?>
                        <span class="text-muted ms-1" style="text-transform:capitalize">(<?= htmlspecialchars($order['payment_method'], ENT_QUOTES, 'UTF-8') ?>)</span>
                    </dd>

                    <?php if ($order['cancellation_reason']): ?>
                    <dt class="text-muted">Cancellation reason</dt>
                    <dd class="mb-2"><?= htmlspecialchars($order['cancellation_reason'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Delivery info -->
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Delivery Info</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <div class="mb-1"><span class="text-muted">Phone:</span> <?= htmlspecialchars($order['delivery_phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div><span class="text-muted">Address:</span> <?= htmlspecialchars($order['delivery_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
