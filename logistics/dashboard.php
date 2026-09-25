<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/FarmerLogistics.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/DeliveryTracking.php';

requireRole('logistics');

$pdo             = Database::getInstance();
$model           = new FarmerLogistics($pdo);
$orderModel      = new Order($pdo);
$trackingModel   = new DeliveryTracking($pdo);

$user            = currentUser();
$logisticsUserId = (int) $user['id'];

$assignedFarmer  = $model->getAssignedFarmerForLogisticsUser($logisticsUserId);
$statusCounts    = $orderModel->countByStatusForLogistics($logisticsUserId);
$periodSummary   = $orderModel->getLogisticsPeriodSummary($logisticsUserId);
$dailyAssignments = $orderModel->getLogisticsDailyAssignmentCount($logisticsUserId, 14);
$recentActivity  = $orderModel->getRecentAssignedForLogistics($logisticsUserId, 8);

$deliveriesToday   = $orderModel->countDeliveriesTodayForLogistics($logisticsUserId);
$activeDeliveries  = $orderModel->countActiveDeliveriesForLogistics($logisticsUserId);
$assignedOrders    = $orderModel->countAssignedToLogistics($logisticsUserId);
$successRate       = $orderModel->getDeliverySuccessRateForLogistics($logisticsUserId);
$liveTrackingCount = $trackingModel->countLiveForLogistics($logisticsUserId);

$assignedFarmerName    = $assignedFarmer['name'] ?? 'Not assigned';
$assignedFarmerEmail   = $assignedFarmer['email'] ?? '—';
$assignedFarmerPhone   = $assignedFarmer['phone'] ?? '—';
$assignedFarmerAddress = $assignedFarmer['address'] ?? '—';

$statusLabels = ['pending', 'processing', 'in_transit', 'delivered', 'cancelled'];
$statusValues = [];
foreach ($statusLabels as $s) {
    $statusValues[] = $statusCounts[$s] ?? 0;
}
$maxStatusCount = max(1, max($statusValues));

$pendingCount    = (int) ($statusCounts['pending'] ?? 0);
$processingCount = (int) ($statusCounts['processing'] ?? 0);
$inTransitCount  = (int) ($statusCounts['in_transit'] ?? 0);
$deliveredCount  = (int) ($statusCounts['delivered'] ?? 0);
$cancelledCount  = (int) ($statusCounts['cancelled'] ?? 0);
$attentionCount  = $processingCount + $inTransitCount;

$deliveredThisMonth = (int) ($periodSummary['delivered_this_month'] ?? 0);
$deliveredThisWeek  = (int) ($periodSummary['delivered_this_week'] ?? 0);
$assignedThisMonth  = (int) ($periodSummary['assigned_this_month'] ?? 0);

$today  = new DateTime();
$asnMap = [];
foreach ($dailyAssignments as $row) {
    $asnMap[$row['day']] = (int) $row['cnt'];
}
$asnDays   = [];
$asnCounts = [];
for ($i = 13; $i >= 0; $i--) {
    $d = (clone $today)->modify("-$i day")->format('Y-m-d');
    $asnDays[]   = (clone $today)->modify("-$i day")->format('M j');
    $asnCounts[] = $asnMap[$d] ?? 0;
}

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Hero -->
<div class="admin-hero mb-4">
    <div class="row align-items-center g-3">
        <div class="col-12 col-lg-8">
            <div class="admin-hero-title">Delivery overview</div>
            <p class="admin-hero-sub">
                Track assignments, active routes, and live GPS for <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> deliveries.
            </p>
            <div class="admin-chip-row">
                <span class="admin-chip"><strong><?= $attentionCount ?></strong> active workload</span>
                <span class="admin-chip"><strong><?= $deliveredThisMonth ?></strong> delivered this month</span>
                <span class="admin-chip"><strong><?= $liveTrackingCount ?></strong> live on map</span>
                <span class="admin-chip"><strong><?= htmlspecialchars($assignedFarmerName, ENT_QUOTES, 'UTF-8') ?></strong> assigned farmer</span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="<?= url('/logistics/deliveries.php') ?>" class="btn btn-agri btn-sm">Active Deliveries</a>
                <a href="<?= url('/logistics/assigned-orders.php') ?>" class="btn btn-agri-outline btn-sm">Assigned Orders</a>
                <a href="<?= url('/logistics/delivery-history.php') ?>" class="btn btn-agri-outline btn-sm">History</a>
            </div>
        </div>
    </div>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--green"><i class="ti ti-truck-delivery"></i></span>
            </div>
            <div class="stat-value"><?= $deliveriesToday ?></div>
            <div class="stat-label">Deliveries Today</div>
            <div class="stat-meta"><?= $deliveredThisWeek ?> completed this week</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--blue"><i class="ti ti-route"></i></span>
            </div>
            <div class="stat-value"><?= $activeDeliveries ?></div>
            <div class="stat-label">Active Deliveries</div>
            <div class="stat-meta"><?= $inTransitCount ?> in transit</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--purple"><i class="ti ti-clipboard-list"></i></span>
            </div>
            <div class="stat-value"><?= $assignedOrders ?></div>
            <div class="stat-label">Assigned Orders</div>
            <div class="stat-meta"><?= $assignedThisMonth ?> new this month</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon--amber"><i class="ti ti-chart-pie"></i></span>
            </div>
            <div class="stat-value"><?= htmlspecialchars($successRate ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="stat-label">Success Rate</div>
            <div class="stat-meta <?= $liveTrackingCount > 0 ? 'is-up' : '' ?>">
                <?= $liveTrackingCount ?> live GPS<?= $liveTrackingCount === 1 ? '' : ' signals' ?>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="chart-card">
            <div class="chart-card-title">Assignments — Last 14 Days</div>
            <?php if (array_sum($asnCounts) === 0): ?>
                <p class="text-muted mb-0" style="font-size:0.875rem">No assignments in the last 14 days yet.</p>
            <?php else: ?>
                <canvas id="assignmentsChart" height="100"></canvas>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="chart-card">
            <div class="chart-card-title">Orders by Status</div>
            <?php if (array_sum($statusValues) === 0): ?>
                <p class="text-muted mb-0" style="font-size:0.875rem">No assigned orders yet.</p>
            <?php else: ?>
                <canvas id="statusChart" height="180"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pipeline, alerts, farmer + quick links -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Delivery Pipeline</div>
            <div class="card-body">
                <?php if (array_sum($statusValues) === 0): ?>
                    <p class="text-muted mb-0" style="font-size:0.875rem">Assignments will appear here once your farmer assigns orders to you.</p>
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
            <div class="card-header bg-white fw-semibold">Delivery Alerts</div>
            <div class="card-body">
                <div class="admin-alert-list">
                    <div class="admin-alert-item <?= $inTransitCount > 0 ? 'is-warn' : 'is-ok' ?>">
                        <span><?= $inTransitCount ?> in transit — enable GPS on order view</span>
                        <a href="<?= url('/logistics/deliveries.php') ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                    <div class="admin-alert-item <?= $processingCount > 0 ? 'is-warn' : 'is-ok' ?>">
                        <span><?= $processingCount ?> ready to pick up</span>
                        <a href="<?= url('/logistics/assigned-orders.php?status=processing') ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </div>
                    <div class="admin-alert-item <?= $liveTrackingCount > 0 ? 'is-ok' : ($inTransitCount > 0 ? 'is-warn' : 'is-ok') ?>">
                        <span><?= $liveTrackingCount ?> live GPS<?= $liveTrackingCount === 1 ? '' : ' signals' ?> broadcasting</span>
                    </div>
                    <div class="admin-alert-item is-ok">
                        <span><?= $deliveredThisMonth ?> delivered this month</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Assigned Farmer</div>
            <div class="card-body">
                <div class="fw-700 mb-2"><?= htmlspecialchars($assignedFarmerName, ENT_QUOTES, 'UTF-8') ?></div>
                <dl class="mb-3" style="font-size:0.875rem">
                    <dt class="text-muted" style="font-weight:500;font-size:0.8rem">Email</dt>
                    <dd class="mb-2"><?= htmlspecialchars($assignedFarmerEmail, ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="text-muted" style="font-weight:500;font-size:0.8rem">Phone</dt>
                    <dd class="mb-2"><?= htmlspecialchars($assignedFarmerPhone, ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="text-muted" style="font-weight:500;font-size:0.8rem">Address</dt>
                    <dd class="mb-0"><?= htmlspecialchars($assignedFarmerAddress, ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>
                <div class="admin-quick-grid">
                    <a href="<?= url('/logistics/deliveries.php') ?>" class="admin-quick-link">
                        <strong>Active Deliveries</strong>
                        <span>Start GPS & update status</span>
                    </a>
                    <a href="<?= url('/logistics/assigned-orders.php') ?>" class="admin-quick-link">
                        <strong>Assigned Orders</strong>
                        <span>View all your assignments</span>
                    </a>
                    <a href="<?= url('/logistics/delivery-history.php') ?>" class="admin-quick-link">
                        <strong>Delivery History</strong>
                        <span>Past completed routes</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent assignments -->
<div class="row g-3">
    <div class="col-12">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                Recent Assignments
                <a href="<?= url('/logistics/delivery-history.php') ?>" style="font-size:0.8125rem">View history</a>
            </div>
            <?php if (empty($recentActivity)): ?>
                <div class="card-body">
                    <p class="text-muted mb-0" style="font-size:0.875rem">No assignments yet. Your assigned farmer will route orders to you.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-agri align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Buyer</th>
                                <th>Farmer</th>
                                <th>Assigned</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $a): ?>
                                <tr>
                                    <td class="fw-600">
                                        <a href="<?= url('/logistics/orders/view.php?id=' . (int) $a['id']) ?>">#<?= (int) $a['id'] ?></a>
                                    </td>
                                    <td><?= htmlspecialchars($a['buyer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($a['farmer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($a['assigned_at'])) ?></td>
                                    <td><?= statusBadge($a['logistics_status']) ?></td>
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
$purple     = '#7e22ce';
$purpleFill = 'rgba(126, 34, 206, 0.12)';

if (array_sum($asnCounts) > 0) {
    $chartJs .= '
    new Chart(document.getElementById("assignmentsChart"), {
        type: "bar",
        data: {
            labels: ' . json_encode($asnDays) . ',
            datasets: [{
                label: "Assignments",
                data: ' . json_encode($asnCounts) . ',
                backgroundColor: "' . $purpleFill . '",
                borderColor: "' . $purple . '",
                borderWidth: 1.5,
                borderRadius: 4,
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

if (array_sum($statusValues) > 0) {
    $chartJs .= '
    new Chart(document.getElementById("statusChart"), {
        type: "doughnut",
        data: {
            labels: ["Pending","Processing","In Transit","Delivered","Cancelled"],
            datasets: [{
                data: ' . json_encode($statusValues) . ',
                backgroundColor: ["#94a3b8","#3b82f6","#f59e0b","#10b981","#ef4444"],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            cutout: "62%",
            plugins: { legend: { position: "bottom", labels: { boxWidth: 10, font: { size: 11 } } } }
        }
    });';
}

$extraScripts = $chartJs !== ''
    ? '<script>document.addEventListener("DOMContentLoaded",function(){' . $chartJs . '});</script>'
    : '';

require_once __DIR__ . '/../includes/sidebar_end.php';
?>
