<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Order.php';

requireRole('admin');

$pdo        = Database::getInstance();
$orderModel = new Order($pdo);

$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');

$orderTotal = $orderModel->countAll($statusFilter, $search);
$pagination = paginationMeta($orderTotal);
$orders     = $orderModel->getAll($statusFilter, $search, $pagination['per_page'], $pagination['offset']);

$pageTitle = 'Orders';
$activeNav = 'orders';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Orders</h1>
        <p class="page-subtitle mb-0">View and monitor all platform orders</p>
    </div>
</div>

<!-- Filters -->
<form method="get" class="row g-2 mb-4">
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach (['pending','processing','in_transit','delivered','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col">
        <input type="search" name="search" class="form-control form-control-sm" placeholder="Search by buyer name, email or order ID..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-agri">Filter</button>
    </div>
</form>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <h2>No orders found</h2>
        <p>Try adjusting your filters.</p>
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
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="fw-600">#<?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['buyer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                        <td><?= peso((float)$o['total_amount']) ?></td>
                        <td><?= paymentBadge($o['payment_status']) ?></td>
                        <td><?= statusBadge($o['logistics_status']) ?></td>
                        <td>
                            <a href="<?= url('/admin/orders/view.php?id=' . $o['id']) ?>"
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
