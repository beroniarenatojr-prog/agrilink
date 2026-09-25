<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Order.php';

requireRole('logistics');

$pdo = Database::getInstance();
$orderModel = new Order($pdo);
$user = currentUser();
$logisticsUserId = (int) $user['id'];

$orderTotal = $orderModel->countByLogisticsUser($logisticsUserId);
$pagination = paginationMeta($orderTotal);
$orders     = $orderModel->getByLogisticsUser($logisticsUserId, 'delivered', [], $pagination['per_page'], $pagination['offset']);

$pageTitle = 'Delivery History';
$activeNav = 'delivery-history';

require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Delivery History</h1>
        <p class="page-subtitle mb-0">Completed deliveries assigned to you</p>
    </div>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <h2>No delivery history yet</h2>
        <p>Delivered orders assigned to you will appear here.</p>
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
                        <th>Assigned</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="fw-600">#<?= (int) $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['buyer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($o['farmer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= date('M j, Y', strtotime($o['assigned_at'])) ?></td>
                            <td><?= statusBadge($o['logistics_status']) ?></td>
                            <td>
                                <a href="<?= url('/logistics/orders/view.php?id=' . (int) $o['id']) ?>"
                                    class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= renderListFooter($pagination, 'result') ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/sidebar_end.php'; ?>