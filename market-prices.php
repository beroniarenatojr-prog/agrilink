<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/classes/MarketPrice.php';

// Only farmer and admin can view market prices
if (!isLoggedIn() || !in_array(currentRole(), ['farmer','admin'])) {
    redirect('/marketplace/index.php');
    exit;
}

$pdo     = Database::getInstance();
$mpModel = new MarketPrice($pdo);

$overview  = $mpModel->categoryPriceOverview();

// Category selector for chart
$catStmt    = $pdo->query('SELECT id, name FROM categories ORDER BY name');
$categories = $catStmt->fetchAll();

$selectedCatId = (int)($_GET['cat'] ?? ($categories[0]['id'] ?? 0));
$historyRows   = $selectedCatId ? $mpModel->referencePriceHistory($selectedCatId) : [];

$chartLabels = array_map(fn($r) => date('M j, Y', strtotime($r['effective_date'])), $historyRows);
$chartData   = array_map(fn($r) => (float)$r['reference_price'], $historyRows);

$pageTitle = 'Market Prices';
$activeNav = currentRole() === 'admin' ? 'price-overview' : 'market-prices';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Market Prices</h1>
        <p class="page-subtitle mb-0">Reference prices and platform benchmarks per category</p>
    </div>
</div>

<!-- Disclaimer -->
<div class="alert alert-light border mb-4" style="font-size:0.875rem">
    Reference prices are set by the <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> admin team based on DA bulletins. Platform averages reflect active listings on <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>.
</div>

<!-- Overview table -->
<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-agri align-middle mb-0">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Reference Price</th>
                    <th>Platform Avg</th>
                    <th>Range (Min – Max)</th>
                    <th>Listings</th>
                    <th>As of</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overview as $row): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($row['reference']): ?>
                            <?= peso((float)$row['reference']['reference_price']) ?>
                            <span class="text-muted" style="font-size:0.8rem">/ <?= htmlspecialchars($row['reference']['unit_type'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['platform']): ?>
                            <?= peso($row['platform']['avg_price']) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['platform']): ?>
                            <?= peso($row['platform']['min_price']) ?> – <?= peso($row['platform']['max_price']) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $row['platform'] ? (int)$row['platform']['listing_count'] : '—' ?>
                    </td>
                    <td>
                        <?php if ($row['reference']): ?>
                            <?= date('M j, Y', strtotime($row['reference']['effective_date'])) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Price history chart -->
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between flex-wrap gap-2">
        Reference Price History
        <form method="get" class="d-flex align-items-center gap-2">
            <select name="cat" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $selectedCatId === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body p-4">
        <?php if (empty($historyRows)): ?>
            <p class="text-muted mb-0" style="font-size:0.875rem">No reference price history for this category.</p>
        <?php else: ?>
            <canvas id="historyChart" height="80"></canvas>
        <?php endif; ?>
    </div>
</div>

<?php
$extraScripts = '';
if (!empty($historyRows)) {
    $extraScripts = '<script>
new Chart(document.getElementById("historyChart"), {
    type: "line",
    data: {
        labels: ' . json_encode($chartLabels) . ',
        datasets: [{
            label: "Reference Price (₱)",
            data: ' . json_encode($chartData) . ',
            borderColor: "#2d6a4f",
            backgroundColor: "rgba(45,106,79,0.1)",
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: false,
                ticks: { callback: v => "₱" + v.toLocaleString() }
            }
        }
    }
});
</script>';
}

require_once __DIR__ . '/includes/sidebar_end.php';
?>
