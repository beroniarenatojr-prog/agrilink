<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Order.php';

requireRole('buyer');

$pdo        = Database::getInstance();
$orderModel = new Order($pdo);
$user       = currentUser();

$statusFilter = $_GET['status'] ?? '';
$buyerId      = (int) $user['id'];

$orderTotal   = $orderModel->countByBuyer($buyerId, $statusFilter);
$pagination   = paginationMeta($orderTotal);
$orders       = $orderModel->getByBuyer($buyerId, $statusFilter, $pagination['per_page'], $pagination['offset']);

$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">My Orders</h1>
        <p class="page-subtitle mb-0">Track and manage your purchases</p>
    </div>
    <a href="<?= url('/marketplace/index.php') ?>" class="btn btn-agri">Shop Now</a>
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
        <p>Place your first order from the marketplace.</p>
        <a href="<?= url('/marketplace/index.php') ?>" class="btn btn-agri">Browse Products</a>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-agri align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="fw-600">#<?= $o['id'] ?></td>
                        <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                        <td><?= (int)$o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                        <td><?= peso((float)$o['total_amount']) ?></td>
                        <td><?= statusBadge($o['logistics_status']) ?></td>
                        <td>
                            <a href="<?= url('/buyer/orders/view.php?id=' . $o['id']) ?>"
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
