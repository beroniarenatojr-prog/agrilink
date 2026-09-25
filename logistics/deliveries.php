<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/DeliveryTracking.php';

requireRole('logistics');

$pdo = Database::getInstance();
$orderModel = new Order($pdo);
$trackingModel = new DeliveryTracking($pdo);
$user = currentUser();
$logisticsUserId = (int) $user['id'];

$orderTotal = $orderModel->countByLogisticsUser($logisticsUserId, '', ['processing', 'in_transit']);
$pagination = paginationMeta($orderTotal);
$orders     = $orderModel->getByLogisticsUser($logisticsUserId, '', ['processing', 'in_transit'], $pagination['per_page'], $pagination['offset']);

$pageTitle = 'Deliveries';
$activeNav = 'deliveries';

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Deliveries</h1>
        <p class="page-subtitle mb-0">Active delivery tasks assigned to you</p>
    </div>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <h2>No active deliveries</h2>
        <p>Processing and in-transit orders assigned to you will show here.</p>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-agri align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Buyer</th>
                        <th>Farmer</th>
                        <th>Delivery address</th>
                        <th>Status</th>
                        <th>Live</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="fw-600">#<?= (int) $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['buyer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($o['farmer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-muted" style="max-width:220px">
                            <?= htmlspecialchars($o['delivery_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= statusBadge($o['logistics_status']) ?></td>
                        <td>
                            <?php if ($o['logistics_status'] === 'in_transit' && $trackingModel->isLive((int) $o['id'])): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Live</span>
                            <?php elseif ($o['logistics_status'] === 'in_transit'): ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Offline</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= url('/logistics/orders/view.php?id=' . (int) $o['id']) ?>"
                               class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= renderListFooter($pagination, 'delivery') ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/sidebar_end.php'; ?>
