<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/Order.php';

requireRole('admin');

$pdo        = Database::getInstance();
$orderModel = new Order($pdo);

$orderId = (int)($_GET['id'] ?? 0);
$order   = $orderModel->findById($orderId);

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order Not Found';
    $activeNav = 'orders';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="empty-state"><h2>Order not found</h2><a href="' . url('/admin/orders.php') . '" class="btn btn-agri">All Orders</a></div>';
    require_once __DIR__ . '/../../includes/sidebar_end.php';
    exit;
}

$items = $orderModel->getItems($orderId);

$pageTitle = 'Order #' . $orderId;
$activeNav = 'orders';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Order #<?= $orderId ?></h1>
        <p class="page-subtitle mb-0">Placed <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></p>
    </div>
    <a href="<?= url('/admin/orders.php') ?>" class="btn btn-agri-outline"><i class="ti ti-arrow-left me-1"></i> All Orders</a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Order Items</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-agri align-middle mb-0">
                        <thead>
                            <tr><th>Product</th><th>Farmer</th><th>Unit Price</th><th>Qty</th><th class="text-end">Subtotal</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($item['farmer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= peso((float)$item['price_snapshot']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td class="text-end fw-600"><?= peso((float)$item['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-600">Total</td>
                                <td class="text-end fw-600 text-agri"><?= peso((float)$order['total_amount']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Order Details</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <dl class="mb-0">
                    <dt class="text-muted">Status</dt>
                    <dd class="mb-2"><?= statusBadge($order['logistics_status']) ?></dd>

                    <dt class="text-muted">Payment</dt>
                    <dd class="mb-2"><?= paymentBadge($order['payment_status']) ?> (<?= htmlspecialchars($order['payment_method'], ENT_QUOTES, 'UTF-8') ?>)</dd>

                    <dt class="text-muted">Buyer</dt>
                    <dd class="mb-2"><?= htmlspecialchars($order['buyer_name'], ENT_QUOTES, 'UTF-8') ?></dd>

                    <?php if ($order['cancellation_reason']): ?>
                    <dt class="text-muted">Cancellation reason</dt>
                    <dd class="mb-2"><?= htmlspecialchars($order['cancellation_reason'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Delivery Info</div>
            <div class="card-body p-3" style="font-size:0.875rem">
                <div class="mb-1"><span class="text-muted">Phone:</span> <?= htmlspecialchars($order['delivery_phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div><span class="text-muted">Address:</span> <?= htmlspecialchars($order['delivery_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/sidebar_end.php'; ?>
