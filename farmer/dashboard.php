<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/FarmerLogistics.php';

requireRole('farmer');

$pdo             = Database::getInstance();
$orderModel      = new Order($pdo);
$prodModel       = new Product($pdo);
$logisticsModel  = new FarmerLogistics($pdo);
$user            = currentUser();
$farmerId        = (int) $user['id'];

$revenue         = $orderModel->getFarmerRevenue($farmerId);
$completedCount  = $orderModel->getFarmerCompletedCount($farmerId);
$prodStats       = $prodModel->countByFarmer($farmerId);
$statusCounts    = $orderModel->countByStatusForFarmer($farmerId);
$periodSummary   = $orderModel->getFarmerPeriodSummary($farmerId);
$dailyRevenue    = $orderModel->getFarmerDailyRevenue($farmerId, 30);
$dailyOrders     = $orderModel->getFarmerDailyOrderCount($farmerId, 14);
$topProducts     = $orderModel->getFarmerTopProducts($farmerId, 5);
$forecast        = $orderModel->getFarmerDemandForecast($farmerId, 3);
$recentOrders    = $orderModel->getRecentForFarmer($farmerId, 6);
$recentSales     = $orderModel->getRecentSalesForFarmer($farmerId, 6);
$activeLogistics = count($logisticsModel->listActiveLogisticsUsersForFarmer($farmerId));

$statusLabels = ['pending', 'processing', 'in_transit', 'delivered', 'cancelled'];
$statusValues = [];
foreach ($statusLabels as $s) {
    $statusValues[] = $statusCounts[$s] ?? 0;
}
$maxStatusCount = max(1, max($statusValues));

$activeListings = (int) ($prodStats['active'] ?? 0);
$totalProducts  = (int) ($prodStats['total'] ?? 0);
$stockAlerts    = (int) ($prodStats['out_of_stock'] ?? 0) + (int) ($prodStats['low_stock'] ?? 0);

$pendingCount    = (int) ($statusCounts['pending'] ?? 0);
$processingCount = (int) ($statusCounts['processing'] ?? 0);
$inTransitCount  = (int) ($statusCounts['in_transit'] ?? 0);
$deliveredCount  = (int) ($statusCounts['delivered'] ?? 0);
$cancelledCount  = (int) ($statusCounts['cancelled'] ?? 0);
$attentionCount  = $pendingCount + $processingCount + $inTransitCount;

$revenueThisMonth   = (float) ($periodSummary['revenue_this_month'] ?? 0);
$revenueLastMonth   = (float) ($periodSummary['revenue_last_month'] ?? 0);
$ordersThisMonth    = (int) ($periodSummary['orders_this_month'] ?? 0);
$deliveredThisMonth = (int) ($periodSummary['delivered_this_month'] ?? 0);

$revenueMoM = $revenueLastMonth > 0
    ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
    : ($revenueThisMonth > 0 ? 100.0 : null);

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
$ordDays   = [];
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
            <div class="admin-hero-title">Farm overview</div>
            <p class="admin-hero-sub">
                Track your sales, inventory, and orders across <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>.
            </p>
            <div class="admin-chip-row">
                <span class="admin-chip"><strong><?= $attentionCount ?></strong> orders need action</span>
                <span class="admin-chip"><strong><?= $deliveredThisMonth ?></strong> delivered this month</span>
                <span class="admin-chip"><strong><?= $activeListings ?></strong> active listings</span>
                <span class="admin-chip"><strong><?= $activeLogistics ?></strong> logistics staff</span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="<?= url('/farmer/orders.php') ?>" class="btn btn-agri btn-sm">Manage Orders</a>
                <a href="<?= url('/farmer/products/create.php') ?>" class="btn btn-agri-outline btn-sm">Add Product</a>
                <a href="<?= url('/market-prices.php') ?>" class="btn btn-agri-outline btn-sm">Market Prices</a>
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
            <div class="stat-value" style="font-size:1.5rem"><?= peso($revenue) ?></div>
            <div class="stat-label">Total Revenue</div>
            <?php if ($revenueMoM !== null): ?>
                <div class="stat-meta <?= $revenueMoM >= 0 ? 'is-up' : 'is-down' ?>">
                    <?= $revenueMoM >= 0 ? '+' : '' ?><?= $revenueMoM ?>% vs last month
                </div>
            <?php else: ?>
                <div class="stat-meta"><?= peso($revenueThisMonth) ?> this month</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--blue"><i class="ti ti-shopping-cart"></i></span>
            </div>
            <div class="stat-value"><?= $completedCount ?></div>
            <div class="stat-label">Completed Orders</div>
            <div class="stat-meta"><?= $ordersThisMonth ?> new this month</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--purple"><i class="ti ti-package"></i></span>
            </div>
            <div class="stat-value"><?= $activeListings ?></div>
            <div class="stat-label">Active Listings</div>
            <div class="stat-meta"><?= $totalProducts ?> total products</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--amber"><i class="ti ti-alert-triangle"></i></span>
            </div>
            <div class="stat-value" style="color:<?= $stockAlerts > 0 ? '#dc2626' : 'inherit' ?>"><?= $stockAlerts ?></div>
            <div class="stat-label">Stock Alerts</div>
            <?php if ($stockAlerts > 0): ?>
                <div class="stat-meta is-down">
                    <?= (int) ($prodStats['out_of_stock'] ?? 0) ?> out · <?= (int) ($prodStats['low_stock'] ?? 0) ?> low
                </div>
            <?php else: ?>
                <div class="stat-meta">All stock levels OK</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="chart-card">
            <div class="chart-card-title">Revenue — Last 30 Days</div>
            <?php if (array_sum($revAmounts) <= 0): ?>
                <p class="text-muted mb-0" style="font-size:0.875rem">No delivered sales in the last 30 days yet.</p>
            <?php else: ?>
                <canvas id="revenueChart" height="100"></canvas>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="chart-card">
            <div class="chart-card-title">Orders by Status</div>
            <?php if (array_sum($statusValues) === 0): ?>
                <p class="text-muted mb-0" style="font-size:0.875rem">No orders yet.</p>
            <?php else: ?>
                <canvas id="statusChart" height="180"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-5">
        <div class="chart-card">
            <div class="chart-card-title">New Orders — Last 14 Days</div>
            <?php if (array_sum($ordCounts) === 0): ?>
                <p class="text-muted mb-0" style="font-size:0.875rem">No orders in the last 14 days.</p>
            <?php else: ?>
                <canvas id="ordersChart" height="120"></canvas>
            <?php endif; ?>
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
                <?php if (array_sum($statusValues) === 0): ?>
                    <p class="text-muted mb-0" style="font-size:0.875rem">Orders will appear here once buyers purchase your products.</p>
                <?php else: ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Farm Alerts</div>
            <div class="card-body">
                <div class="admin-alert-list">
                    <div class="admin-alert-item <?= $pendingCount > 0 ? 'is-warn' : 'is-ok' ?>">
                        <span><?= $pendingCount ?> pending order<?= $pendingCount === 1 ? '' : 's' ?></span>
                        <a href="<?= url('/farmer/orders.php?status=pending') ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                    <div class="admin-alert-item <?= $stockAlerts > 0 ? 'is-warn' : 'is-ok' ?>">
                        <span><?= (int) ($prodStats['low_stock'] ?? 0) ?> low stock · <?= (int) ($prodStats['out_of_stock'] ?? 0) ?> out of stock</span>
                        <a href="<?= url('/farmer/products.php') ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                    <div class="admin-alert-item <?= $inTransitCount > 0 ? 'is-ok' : '' ?>">
                        <span><?= $inTransitCount ?> in transit</span>
                        <a href="<?= url('/farmer/orders.php?status=in_transit') ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                    <div class="admin-alert-item is-ok">
                        <span><?= peso($revenueThisMonth) ?> earned this month</span>
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
                    <a href="<?= url('/farmer/products/create.php') ?>" class="admin-quick-link">
                        <strong>Add Product</strong>
                        <span>List a new crop or item</span>
                    </a>
                    <a href="<?= url('/farmer/orders.php') ?>" class="admin-quick-link">
                        <strong>Orders</strong>
                        <span>Update status & assign logistics</span>
                    </a>
                    <a href="<?= url('/farmer/logistics.php') ?>" class="admin-quick-link">
                        <strong>Logistics</strong>
                        <span>Manage delivery staff</span>
                    </a>
                    <a href="<?= url('/market-prices.php') ?>" class="admin-quick-link">
                        <strong>Market Prices</strong>
                        <span>Compare DA reference prices</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demand Forecast -->
<?php if (!empty($forecast)): ?>
<div class="mb-4">
    <div class="forecast-section-title">Demand Forecast <span>(90-day history)</span></div>
    <div class="row g-3">
        <?php foreach ($forecast as $f): ?>
        <div class="col-12 col-md-4">
            <div class="forecast-card">
                <div class="forecast-product-name"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="forecast-metric">
                    <span class="forecast-metric-label">Weekly avg demand</span>
                    <span class="forecast-metric-value"><?= number_format($f['weekly_avg'], 1) ?> units</span>
                </div>
                <div class="forecast-metric">
                    <span class="forecast-metric-label">Month-over-month</span>
                    <span class="forecast-metric-value">
                        <?php if ($f['mom_percent'] !== null): ?>
                            <span class="<?= $f['mom_percent'] >= 0 ? 'forecast-trend--up' : 'forecast-trend--down' ?>">
                                <?= $f['mom_percent'] >= 0 ? '+' : '' ?><?= number_format($f['mom_percent'], 1) ?>%
                            </span>
                        <?php else: ?>
                            <span class="forecast-trend--na">N/A</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="forecast-metric forecast-metric--highlight">
                    <span class="forecast-metric-label">Suggested stock</span>
                    <span class="forecast-metric-value"><?= (int) $f['suggested_stock'] ?> units</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent orders + recent sales -->
<div class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                Recent Orders
                <a href="<?= url('/farmer/orders.php') ?>" style="font-size:0.8125rem">View all</a>
            </div>
            <?php if (empty($recentOrders)): ?>
                <div class="card-body">
                    <p class="text-muted mb-0" style="font-size:0.875rem">No orders yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-agri align-middle mb-0">
                        <thead>
                            <tr><th>#</th><th>Buyer</th><th>Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td class="fw-600"><a href="<?= url('/farmer/orders/view.php?id=' . $o['id']) ?>">#<?= $o['id'] ?></a></td>
                                <td><?= htmlspecialchars($o['buyer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= peso((float) $o['total_amount']) ?></td>
                                <td><?= statusBadge($o['logistics_status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Recent Sales</div>
            <?php if (empty($recentSales)): ?>
                <div class="card-body">
                    <p class="text-muted mb-0" style="font-size:0.875rem">No completed sales yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-agri align-middle mb-0">
                        <thead>
                            <tr><th>Product</th><th>Qty</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentSales as $s): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $s['quantity'] ?></td>
                                <td><?= peso((float) $s['subtotal']) ?></td>
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
$chartJs = '';
$green     = '#10b981';
$greenFill = 'rgba(16,185,129,0.12)';

if (array_sum($revAmounts) > 0) {
    $chartJs .= '
    new Chart(document.getElementById("revenueChart"), {
        type: "line",
        data: {
            labels: ' . json_encode($revDays) . ',
            datasets: [{
                label: "Revenue",
                data: ' . json_encode($revAmounts) . ',
                borderColor: "' . $green . '",
                backgroundColor: "' . $greenFill . '",
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
    });';
}

if (array_sum($statusValues) > 0) {
    $chartJs .= '
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
    });';
}

if (array_sum($ordCounts) > 0) {
    $chartJs .= '
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
    });';
}

if (!empty($topProducts)) {
    $chartJs .= '
    new Chart(document.getElementById("topProductsChart"), {
        type: "bar",
        data: {
            labels: ' . json_encode($topProdLabels) . ',
            datasets: [{
                label: "Units Sold",
                data: ' . json_encode($topProdValues) . ',
                backgroundColor: "' . $green . '",
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
    });';
}

$extraScripts = $chartJs !== ''
    ? '<script>(function(){' . $chartJs . '})();</script>'
    : '';

require_once __DIR__ . '/../includes/sidebar_end.php';
?>
