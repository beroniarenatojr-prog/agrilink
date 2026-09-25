<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/MarketPrice.php';

$pdo = Database::getInstance();
$productModel = new Product($pdo);
$mpModel      = new MarketPrice($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/marketplace/index.php');
    exit;
}

$product = $productModel->findById($id);
if (!$product || !$product['is_available'] || $product['stock_quantity'] <= 0) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="empty-state"><h2>Product not found</h2><p>This product may no longer be available.</p><a href="' . url('/marketplace/index.php') . '" class="btn btn-agri">Back to Marketplace</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Build image list
$images = [];
if ($product['image']) {
    $images[] = $product['image'];
}
foreach (($product['additional_images'] ?? []) as $img) {
    if ($img) $images[] = $img;
}

// Price comparison (farmer/admin only)
$priceComparison = null;
$benchmark       = null;
if (isLoggedIn() && in_array(currentRole(), ['farmer','admin']) && $product['category_id']) {
    $benchmark       = $mpModel->getCategoryBenchmark((int)$product['category_id']);
    $priceComparison = $mpModel->comparePrice((float)$product['price_per_unit'], (int)$product['category_id']);
}

$pageTitle = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');

$extraScripts = <<<'JS'
<script>
function switchImage(src, el) {
    document.getElementById('mainImg').src = src;
    document.querySelectorAll('.product-gallery-thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

function changeQty(delta) {
    const input = document.getElementById('qtyInput');
    const max   = parseInt(input.max);
    let val = parseInt(input.value) + delta;
    if (val < 1)   val = 1;
    if (val > max) val = max;
    input.value = val;
}
</script>
JS;

require_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb" style="font-size:0.875rem">
        <li class="breadcrumb-item"><a href="<?= url('/marketplace/index.php') ?>">Marketplace</a></li>
        <?php if ($product['category_name']): ?>
            <li class="breadcrumb-item">
                <a href="<?= url('/marketplace/index.php?category=' . $product['category_id']) ?>">
                    <?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></li>
    </ol>
</nav>

<div class="row g-4">
    <!-- Gallery -->
    <div class="col-12 col-md-5">
        <div class="product-gallery-main">
            <?php if ($images): ?>
                <img id="mainImg" src="<?= url('/media.php?path=' . urlencode($images[0])) ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
                <div class="img-placeholder w-100 h-100 d-flex align-items-center justify-content-center" style="min-height:320px">No image</div>
            <?php endif; ?>
        </div>

        <?php if (count($images) > 1): ?>
        <div class="product-gallery-thumbs mt-2">
            <?php foreach ($images as $i => $img): ?>
                <div class="product-gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                     onclick="switchImage('<?= url('/media.php?path=' . urlencode($img)) ?>', this)">
                    <img src="<?= url('/media.php?path=' . urlencode($img)) ?>" alt="Image <?= $i+1 ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Product info -->
    <div class="col-12 col-md-7">
        <h1 class="page-title mb-2"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if ($product['category_name']): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">
                <?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <div class="product-price-large mb-1">
            ₱<?= number_format($product['price_per_unit'], 2) ?> / <?= htmlspecialchars($product['unit_type'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <!-- Price comparison badge (farmer/admin only) -->
        <?php if ($priceComparison): ?>
            <?php
            $badgeClass = match($priceComparison) {
                'above_average' => 'price-badge-above',
                'below_average' => 'price-badge-below',
                default         => 'price-badge-within',
            };
            $badgeLabel = match($priceComparison) {
                'above_average' => 'Above market avg (+10%)',
                'below_average' => 'Below market avg (-10%)',
                default         => 'Within market range',
            };
            ?>
            <div class="mb-2">
                <span class="price-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                <?php if ($benchmark && $benchmark['reference']): ?>
                    <span class="text-muted ms-1" style="font-size:0.8rem">
                        Ref: ₱<?= number_format($benchmark['reference']['reference_price'], 2) ?>/<?= htmlspecialchars($benchmark['reference']['unit_type'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php elseif ($benchmark && $benchmark['platform']): ?>
                    <span class="text-muted ms-1" style="font-size:0.8rem">
                        Platform avg: ₱<?= number_format($benchmark['platform']['avg_price'], 2) ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p class="text-muted mb-3" style="font-size:0.875rem">
            <?= (int)$product['stock_quantity'] ?> <?= htmlspecialchars($product['unit_type'], ENT_QUOTES, 'UTF-8') ?>(s) available
        </p>

        <?php if ($product['description']): ?>
            <p style="font-size:0.9rem;line-height:1.65"><?= nl2br(htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8')) ?></p>
        <?php endif; ?>

        <div class="divider"></div>

        <?php if (isLoggedIn() && currentRole() === 'buyer'): ?>
            <!-- Add to Cart -->
            <form method="post" action="<?= url('/cart/add.php') ?>" class="d-flex align-items-center gap-3 flex-wrap mb-3">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <div class="qty-stepper">
                    <button type="button" onclick="changeQty(-1)">−</button>
                    <input type="number" id="qtyInput" name="quantity" value="1" min="1" max="<?= (int)$product['stock_quantity'] ?>">
                    <button type="button" onclick="changeQty(1)">+</button>
                </div>
                <button type="submit" class="btn btn-agri">Add to Cart</button>
            </form>

            <!-- Buy Now -->
            <form method="post" action="<?= url('/cart/checkout.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="buynow" value="1">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="quantity" id="buyNowQty" value="1">
                <button type="submit" class="btn btn-agri-outline"
                    onclick="document.getElementById('buyNowQty').value = document.getElementById('qtyInput').value">
                    Buy Now
                </button>
            </form>
        <?php elseif (!isLoggedIn()): ?>
            <a href="<?= url('/login.php') ?>" class="btn btn-agri">Sign in to buy</a>
        <?php endif; ?>

        <div class="divider"></div>

        <!-- Seller card -->
        <div class="seller-card">
            <div style="font-size:0.8rem;color:var(--agri-text-muted);margin-bottom:0.375rem;font-weight:500;text-transform:uppercase;letter-spacing:0.04em">Seller</div>
            <div class="fw-600 mb-1"><?= htmlspecialchars($product['farmer_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <a href="<?= url('/marketplace/seller.php?id=' . $product['farmer_id']) ?>" style="font-size:0.875rem">View all listings</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
