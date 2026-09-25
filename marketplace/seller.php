<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Product.php';

$pdo = Database::getInstance();

$farmerId = (int)($_GET['id'] ?? 0);
if ($farmerId <= 0) {
    redirect('/marketplace/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, address FROM users WHERE id = ? AND role = 'farmer' LIMIT 1");
$stmt->execute([$farmerId]);
$farmer = $stmt->fetch();

if (!$farmer) {
    http_response_code(404);
    $pageTitle = 'Seller Not Found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="empty-state"><h2>Seller not found</h2><a href="' . url('/marketplace/index.php') . '" class="btn btn-agri">Back to Marketplace</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$productModel = new Product($pdo);
$products     = $productModel->getByFarmerPublic($farmerId);

$pageTitle = htmlspecialchars($farmer['name'], ENT_QUOTES, 'UTF-8') . ' — Seller';
require_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb" style="font-size:0.875rem">
        <li class="breadcrumb-item"><a href="<?= url('/marketplace/index.php') ?>">Marketplace</a></li>
        <li class="breadcrumb-item active">Seller: <?= htmlspecialchars($farmer['name'], ENT_QUOTES, 'UTF-8') ?></li>
    </ol>
</nav>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0"><?= htmlspecialchars($farmer['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($farmer['address']): ?>
            <p class="page-subtitle mb-0"><?= htmlspecialchars($farmer['address'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <h2>No active listings</h2>
        <p>This farmer has no products available right now.</p>
        <a href="<?= url('/marketplace/index.php') ?>" class="btn btn-agri">Browse Marketplace</a>
    </div>
<?php else: ?>
    <p class="text-muted mb-3" style="font-size:0.875rem"><?= count($products) ?> active listing<?= count($products) !== 1 ? 's' : '' ?></p>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
        <?php foreach ($products as $p): ?>
            <div class="col">
                <a href="<?= url('/marketplace/product.php?id=' . $p['id']) ?>" class="text-decoration-none">
                    <div class="product-card">
                        <div class="product-card-img-wrap">
                            <?php if ($p['image']): ?>
                                <img src="<?= url('/media.php?path=' . urlencode($p['image'])) ?>" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                            <?php else: ?>
                                <div class="img-placeholder w-100 h-100" style="min-height:160px">No image</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-card-body">
                            <div class="product-card-name"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="product-card-price">
                                ₱<?= number_format($p['price_per_unit'], 2) ?> / <?= htmlspecialchars($p['unit_type'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
