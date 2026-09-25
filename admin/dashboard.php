<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/User.php';

requireRole('admin');

$pdo        = Database::getInstance();
$orderModel = new Order($pdo);
$prodModel  = new Product($pdo);
$userModel  = new User($pdo);
$user       = currentUser();

$userCounts      = $userModel->countByRole();
$inventoryStats  = $prodModel->getPlatformInventoryStats();
$totalProducts   = (int) ($inventoryStats['total'] ?? 0);
$activeListings  = (int) ($inventoryStats['active'] ?? 0);
$totalOrders     = $orderModel->getTotalCount();
$totalRevenue    = $orderModel->getTotalRevenue();
$statusCounts    = $orderModel->countByStatus();
$periodSummary   = $orderModel->getAdminPeriodSummary();
$recentOrders    = $orderModel->getRecentForAdmin(8);
$recentSignups   = $userModel->getRecentSignups(6);
$topProducts     = $orderModel->getAdminTopProducts(5);
$dailyRevenue    = $orderModel->getAdminDailyRevenue(30);
$dailyOrders     = $orderModel->getAdminDailyOrderCount(14);

$statusLabels = ['pending', 'processing', 'in_transit', 'delivered', 'cancelled'];
$statusValues = [];
foreach ($statusLabels as $s) {
    $statusValues[] = $statusCounts[$s] ?? 0;
}

$maxStatusCount = max(1, max($statusValues));

$ordersThisMonth    = (int) ($periodSummary['orders_this_month'] ?? 0);
$ordersLastMonth    = (int) ($periodSummary['orders_last_month'] ?? 0);
$revenueThisMonth   = (float) ($periodSummary['revenue_this_month'] ?? 0);
$revenueLastMonth   = (float) ($periodSummary['revenue_last_month'] ?? 0);
$deliveredThisMonth = (int) ($periodSummary['delivered_this_month'] ?? 0);

$orderMoM = $ordersLastMonth > 0
    ? round((($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth) * 100, 1)
    : ($ordersThisMonth > 0 ? 100.0 : null);

$revenueMoM = $revenueLastMonth > 0
    ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
    : ($revenueThisMonth > 0 ? 100.0 : null);

$pendingCount    = (int) ($statusCounts['pending'] ?? 0);
$processingCount = (int) ($statusCounts['processing'] ?? 0);
$inTransitCount  = (int) ($statusCounts['in_transit'] ?? 0);
$deliveredCount  = (int) ($statusCounts['delivered'] ?? 0);
$cancelledCount  = (int) ($statusCounts['cancelled'] ?? 0);
$attentionCount  = $pendingCount + $processingCount + $inTransitCount;

$platformUsers = ($userCounts['farmer'] ?? 0) + ($userCounts['buyer'] ?? 0) + ($userCounts['logistics'] ?? 0);
$stockAlerts   = (int) ($inventoryStats['out_of_stock'] ?? 0) + (int) ($inventoryStats['low_stock'] ?? 0);

$today  = new DateTime();
$revMap = [];
foreach ($dailyRevenue as $row) {
    $revMap[$row['day']] = (float) $row['revenue'];
}
$revDays    = [];
$revAmounts = [];
for ($i = 29; $i >= 0; $i--) {
    $d = (clone $today)->modify("-$i day")->format('Y-m-d');
    $revDays[]    = (clone $today)->modify("-$i day")->format('M j');
    $revAmounts[] = $revMap[$d] ?? 0;
}

$ordMap = [];
foreach ($dailyOrders as $row) {
    $ordMap[$row['day']] = (int) $row['cnt'];
}
$ordDays  = [];
$ordCounts = [];
for ($i = 13; $i >= 0; $i--) {
    $d = (clone $today)->modify("-$i day")->format('Y-m-d');
    $ordDays[]   = (clone $today)->modify("-$i day")->format('M j');
    $ordCounts[] = $ordMap[$d] ?? 0;
}

$topProdLabels = array_column($topProducts, 'name');
$topProdValues = array_map('intval', array_column($topProducts, 'total_qty'));

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Hero -->
<div class="admin-hero mb-4">
    <div class="row align-items-center g-3">
        <div class="col-12 col-lg-8">
            <div class="admin-hero-title">Platform overview</div>
            <p class="admin-hero-sub">
                Monitor marketplace activity, orders, and user growth across <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>.
            </p>
            <div class="admin-chip-row">
                <span class="admin-chip"><strong><?= $attentionCount ?></strong> orders need attention</span>
                <span class="admin-chip"><strong><?= $deliveredThisMonth ?></strong> delivered this month</span>
                <span class="admin-chip"><strong><?= $activeListings ?></strong> active listings</span>
                <span class="admin-chip"><strong><?= $platformUsers ?></strong> platform users</span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="<?= url('/admin/orders.php') ?>" class="btn btn-agri btn-sm">Manage Orders</a>
                <a href="<?= url('/admin/users.php') ?>" class="btn btn-agri-outline btn-sm">Users</a>
                <a href="<?= url('/admin/market-prices.php') ?>" class="btn btn-agri-outline btn-sm">Market Prices</a>
            </div>
        </div>
    </div>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--green"><i class="ti ti-coin"></i></span>
            </div>
            <div class="stat-value" style="font-size:1.5rem"><?= peso($totalRevenue) ?></div>
            <div class="stat-label">Total Revenue</div>
            <?php if ($revenueMoM !== null): ?>
                <div class="stat-meta <?= $revenueMoM >= 0 ? 'is-up' : 'is-down' ?>">
                    <?= $revenueMoM >= 0 ? '+' : '' ?><?= $revenueMoM ?>% vs last month
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--blue"><i class="ti ti-shopping-cart"></i></span>
            </div>
            <div class="stat-value"><?= $totalOrders ?></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-meta"><?= $ordersThisMonth ?> placed this month</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--purple"><i class="ti ti-users"></i></span>
            </div>
            <div class="stat-value"><?= $platformUsers ?></div>
            <div class="stat-label">Platform Users</div>
            <div class="stat-meta">
                <?= (int) ($userCounts['farmer'] ?? 0) ?> farmers · <?= (int) ($userCounts['buyer'] ?? 0) ?> buyers
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--amber"><i class="ti ti-package"></i></span>
            </div>
            <div class="stat-value"><?= $totalProducts ?></div>
            <div class="stat-label">Products Listed</div>
            <div class="stat-meta <?= $stockAlerts > 0 ? 'is-down' : '' ?>">
                <?= $activeListings ?> active<?= $stockAlerts > 0 ? ' · ' . $stockAlerts . ' stock alerts' : '' ?>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="chart-card">
            <div class="chart-card-title">Revenue — Last 30 Days</div>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="chart-card">
            <div class="chart-card-title">Orders by Status</div>
            <canvas id="statusChart" height="180"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-5">
        <div class="chart-card">
            <div class="chart-card-title">New Orders — Last 14 Days</div>
            <canvas id="ordersChart" height="120"></canvas>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="chart-card">
            <div class="chart-card-title">Top Products by Units Sold</div>
            <?php if (empty($topProducts)): ?>
                <p class="text-muted mb-0" style="font-size:0.875rem">No completed sales yet.</p>
            <?php else: ?>
                <canvas id="topProductsChart" height="120"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pipeline, alerts, quick links -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Order Pipeline</div>
            <div class="card-body">
                <div class="admin-pipeline">
                    <?php
                    $pipeline = [
                        'pending'    => $pendingCount,
                        'processing' => $processingCount,
                        'in_transit' => $inTransitCount,
                        'delivered'  => $deliveredCount,
                        'cancelled'  => $cancelledCount,
                    ];
                    foreach ($pipeline as $key => $count):
                        $pct = round(($count / $maxStatusCount) * 100);
                    ?>
                    <div class="admin-pipeline-row">
                        <span class="admin-pipeline-label"><?= str_replace('_', ' ', $key) ?></span>
                        <div class="admin-pipeline-bar">
                            <div class="admin-pipeline-fill admin-pipeline-fill--<?= $key ?>"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="admin-pipeline-count"><?= $count ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Platform Alerts</div>
            <div class="card-body">
                <div class="admin-alert-list">
                    <div class="admin-alert-item <?= $pendingCount > 0 ? 'is-warn' : 'is-ok' ?>">
                        <span><?= $pendingCount ?> pending order<?= $pendingCount === 1 ? '' : 's' ?></span>
                        <a href="<?= url('/admin/orders.php?status=pending') ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                    <div class="admin-alert-item <?= $inTransitCount > 0 ? 'is-ok' : '' ?>">
                        <span><?= $inTransitCount ?> in transit</span>
                        <a href="<?= url('/admin/orders.php?status=in_transit') ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                    <div class="admin-alert-item <?= $stockAlerts > 0 ? 'is-warn' : 'is-ok' ?>">
                        <span><?= (int) ($inventoryStats['low_stock'] ?? 0) ?> low stock · <?= (int) ($inventoryStats['out_of_stock'] ?? 0) ?> out of stock</span>
                    </div>
                    <div class="admin-alert-item is-ok">
                        <span><?= peso($revenueThisMonth) ?> revenue this month</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Quick Actions</div>
            <div class="card-body">
                <div class="admin-quick-grid">
                    <a href="<?= url('/admin/orders.php') ?>" class="admin-quick-link">
                        <strong>Orders</strong>
                        <span>Review all transactions</span>
                    </a>
                    <a href="<?= url('/admin/users.php') ?>" class="admin-quick-link">
                        <strong>Users</strong>
                        <span>Manage accounts</span>
                    </a>
                    <a href="<?= url('/admin/market-prices.php') ?>" class="admin-quick-link">
                        <strong>Market Prices</strong>
                        <span>Update DA reference</span>
                    </a>
                    <a href="<?= url('/marketplace/index.php') ?>" class="admin-quick-link">
                        <strong>Marketplace</strong>
                        <span>View public storefront</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demo accounts -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                Demo Accounts
                <span class="text-muted fw-normal" style="font-size:0.8125rem">For testing — share with reviewers</span>
            </div>
            <div class="table-responsive">
                <table class="table table-agri align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="role-pill role-pill--admin">Admin</span></td>
                            <td class="font-monospace" style="font-size:0.875rem">admin@agrilink.com</td>
                            <td class="font-monospace" style="font-size:0.875rem">admin1234</td>
                        </tr>
                        <tr>
                            <td><span class="role-pill role-pill--farmer">Farmer</span></td>
                            <td class="font-monospace" style="font-size:0.875rem">farmer@agrilink.com</td>
                            <td class="font-monospace" style="font-size:0.875rem">farmer1234</td>
                        </tr>
                        <tr>
                            <td><span class="role-pill role-pill--buyer">Buyer</span></td>
                            <td class="font-monospace" style="font-size:0.875rem">buyer@agrilink.com</td>
                            <td class="font-monospace" style="font-size:0.875rem">buyer1234</td>
                        </tr>
                        <tr>
                            <td><span class="role-pill role-pill--logistics">Logistics</span></td>
                            <td class="font-monospace" style="font-size:0.875rem">maria@agrilink.com</td>
                            <td class="font-monospace" style="font-size:0.875rem">logistics1234</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recent orders + signups -->
<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                Recent Orders
                <a href="<?= url('/admin/orders.php') ?>" style="font-size:0.8125rem">View all</a>
            </div>
            <?php if (empty($recentOrders)): ?>
                <div class="card-body">
                    <p class="text-muted mb-0" style="font-size:0.875rem">No orders yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-agri align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Buyer</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td class="fw-600">
                                    <a href="<?= url('/admin/orders/view.php?id=' . $o['id']) ?>">#<?= $o['id'] ?></a>
                                </td>
                                <td><?= htmlspecialchars($o['buyer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= peso((float) $o['total_amount']) ?></td>
                                <td><?= date('M j, g:i A', strtotime($o['created_at'])) ?></td>
                                <td><?= statusBadge($o['logistics_status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                Recent Signups
                <a href="<?= url('/admin/users.php') ?>" style="font-size:0.8125rem">View all</a>
            </div>
            <?php if (empty($recentSignups)): ?>
                <div class="card-body">
                    <p class="text-muted mb-0" style="font-size:0.875rem">No users yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-agri align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSignups as $u): ?>
                            <tr>
                                <td>
                                    <div class="fw-600"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted" style="font-size:0.775rem"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <span class="role-pill role-pill--<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size:0.85rem"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
(function() {
    const green     = "#10b981";
    const greenFill = "rgba(16,185,129,0.12)";
    const slate     = "#64748b";

    new Chart(document.getElementById("revenueChart"), {
        type: "line",
        data: {
            labels: ' . json_encode($revDays) . ',
            datasets: [{
                label: "Revenue",
                data: ' . json_encode($revAmounts) . ',
                borderColor: green,
                backgroundColor: greenFill,
                borderWidth: 2,
                fill: true,
                tension: 0.35,
                pointRadius: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => "₱" + Number(v).toLocaleString() }
                },
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }
            }
        }
    });

    new Chart(document.getElementById("statusChart"), {
        type: "doughnut",
        data: {
            labels: ["Pending","Processing","In Transit","Delivered","Cancelled"],
            datasets: [{
                data: ' . json_encode($statusValues) . ',
                backgroundColor: ["#f4c457","#3b82f6","#8b5cf6","#10b981","#ef4444"],
                borderWidth: 0,
                hoverOffset: 4,
            }]
        },
        options: {
            plugins: {
                legend: { position: "bottom", labels: { padding: 12, font: { size: 11 } } }
            },
            cutout: "62%",
        }
    });

    new Chart(document.getElementById("ordersChart"), {
        type: "bar",
        data: {
            labels: ' . json_encode($ordDays) . ',
            datasets: [{
                label: "Orders",
                data: ' . json_encode($ordCounts) . ',
                backgroundColor: "#3b82f6",
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });
' . (empty($topProducts) ? '' : '
    new Chart(document.getElementById("topProductsChart"), {
        type: "bar",
        data: {
            labels: ' . json_encode($topProdLabels) . ',
            datasets: [{
                label: "Units Sold",
                data: ' . json_encode($topProdValues) . ',
                backgroundColor: green,
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: "y",
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true },
                y: { grid: { display: false } }
            }
        }
    });') . '
})();
</script>';
require_once __DIR__ . '/../includes/sidebar_end.php';
?>
