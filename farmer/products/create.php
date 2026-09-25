<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/MarketPrice.php';

requireRole('farmer');

$pdo = Database::getInstance();

$catStmt    = $pdo->query('SELECT id, name FROM categories ORDER BY name');
$categories = $catStmt->fetchAll();

$unitTypes = ['kg','sack','bundle','crate','box','piece','dozen','liter','pack'];

$pageTitle = 'Add Product';
$activeNav = 'products';

$extraHead = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
HTML;

require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Add Product</h1>
        <p class="page-subtitle mb-0">Create a new product listing</p>
    </div>
    <a href="<?= url('/farmer/products.php') ?>" class="btn btn-agri-outline"><i class="ti ti-arrow-left me-1"></i> Back</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form method="post" action="<?= url('/farmer/products/store.php') ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <?php include __DIR__ . '/form_fields.php'; ?>
        </form>
    </div>
</div>

<?php
$mpModel     = new MarketPrice($pdo);
$priceData   = [];
foreach ($categories as $cat) {
    $bench = $mpModel->getCategoryBenchmark((int)$cat['id']);
    $priceData[$cat['id']] = [
        'ref'        => $bench['reference'] ? (float)$bench['reference']['reference_price'] : null,
        'refUnit'    => $bench['reference']['unit_type'] ?? '',
        'platformAvg'=> $bench['platform'] ? round($bench['platform']['avg_price'], 2) : null,
    ];
}

$extraScripts = '<script>
const priceData = ' . json_encode($priceData) . ';

function updatePriceHint() {
    const catId = document.getElementById("category_id").value;
    const hint  = document.getElementById("priceHint");
    if (!catId || !priceData[catId]) { hint.style.display = "none"; return; }
    const d = priceData[catId];
    let html = "";
    if (d.ref !== null) {
        html += "Reference price: ₱" + d.ref.toLocaleString("en-PH", {minimumFractionDigits:2}) + " / " + d.refUnit;
    }
    if (d.platformAvg !== null) {
        if (html) html += " &nbsp;·&nbsp; ";
        html += "Platform avg: ₱" + d.platformAvg.toLocaleString("en-PH", {minimumFractionDigits:2});
    }
    if (html) { hint.innerHTML = html; hint.style.display = "block"; }
    else hint.style.display = "none";
}

document.getElementById("category_id").addEventListener("change", updatePriceHint);
updatePriceHint();
</script>';

require_once __DIR__ . '/../../includes/sidebar_end.php';
?>
