<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Order.php';

requireRole('farmer');

$pdo        = Database::getInstance();
$orderModel = new Order($pdo);
$user       = currentUser();

$statusFilter = $_GET['status'] ?? '';
$farmerId     = (int) $user['id'];

$orderTotal   = $orderModel->countByFarmer($farmerId, $statusFilter);
$pagination   = paginationMeta($orderTotal);
$orders       = $orderModel->getByFarmer($farmerId, $statusFilter, $pagination['per_page'], $pagination['offset']);

$pageTitle = 'Orders';
$activeNav = 'orders';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Orders</h1>
        <p class="page-subtitle mb-0">Manage orders containing your products</p>
    </div>
</div>

<!-- Status filter -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php
    $statuses = ['' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'in_transit' => 'In Transit', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
    foreach ($statuses as $val => $label):
    ?>
        <a href="?status=<?= urlencode($val) ?>"
           class="chip <?= $statusFilter === $val ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <h2>No orders yet</h2>
        <p>Orders for your products will appear here.</p>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-agri align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Buyer</th>
                        <th>Date</th>
                        <th>Your Subtotal</th>
                        <th>Status</th>
                        <th>Assignee</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="fw-600">#<?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['buyer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                        <td><?= peso((float)$o['farmer_subtotal']) ?></td>
                        <td><?= statusBadge($o['logistics_status']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($o['assignee_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="<?= url('/farmer/orders/view.php?id=' . $o['id']) ?>"
                               class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= renderListFooter($pagination, 'order') ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/sidebar_end.php'; ?>
