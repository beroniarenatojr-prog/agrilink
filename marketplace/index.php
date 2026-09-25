<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Product.php';

$pdo     = Database::getInstance();
$product = new Product($pdo);

$search     = trim($_GET['search']   ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$sort       = $_GET['sort'] ?? 'newest';

$productTotal = $product->countMarketplace($search, $categoryId);
$pagination   = paginationMeta($productTotal);
$products     = $product->getMarketplace($search, $categoryId, $sort, $pagination['per_page'], $pagination['offset']);

// Categories for filter chips
$catStmt = $pdo->query('SELECT id, name FROM categories ORDER BY name');
$categories = $catStmt->fetchAll();

$pageTitle = 'Marketplace';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Banner -->
<div class="mb-4" style="background: linear-gradient(135deg, var(--agri-green-muted) 0%, rgba(16, 185, 129, 0.2) 100%); border-radius: var(--agri-radius-lg); padding: 3rem 2rem; position: relative; overflow: hidden; border: 1px solid rgba(16, 185, 129, 0.3);">
    <div style="position: relative; z-index: 1;">
        <h1 class="page-title mb-2" style="font-size: 2.5rem;">Fresh & Local Marketplace</h1>
        <p class="page-subtitle mb-0" style="font-size: 1.1rem; color: var(--agri-green-dark); font-weight: 500;">Support Filipino farmers by buying directly from the source.</p>
    </div>
    <div style="position: absolute; right: -5%; top: -20%; opacity: 0.1; font-size: 15rem; color: var(--agri-green); pointer-events: none;">
        <i class="ti ti-flower"></i>
    </div>
</div>

<!-- Search + Sort -->
<form method="get" action="<?= url('/marketplace/index.php') ?>" class="mb-4">
    <input type="hidden" name="category" value="<?= $categoryId ?>">
    <div class="row g-2">
        <div class="col">
            <input
                type="search"
                class="form-control"
                name="search"
                placeholder="Search products..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>
        <div class="col-auto">
            <select name="sort" class="form-select" onchange="this.form.submit()">
                <option value="newest"    <?= $sort === 'newest'    ? 'selected' : '' ?>>Newest</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc"<?= $sort === 'price_desc'? 'selected' : '' ?>>Price: High to Low</option>
                <option value="name_asc"  <?= $sort === 'name_asc'  ? 'selected' : '' ?>>Name A–Z</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-agri">Search</button>
        </div>
    </div>
</form>

<!-- Category filter chips -->
<div class="category-chips">
    <a
        href="<?= url('/marketplace/index.php?search=' . urlencode($search) . '&sort=' . urlencode($sort)) ?>"
        class="chip <?= $categoryId === 0 ? 'active' : '' ?>"
    >All</a>
    <?php foreach ($categories as $cat): ?>
        <a
            href="<?= url('/marketplace/index.php?search=' . urlencode($search) . '&category=' . $cat['id'] . '&sort=' . urlencode($sort)) ?>"
            class="chip <?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>"
        ><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <h2>No products found</h2>
        <p>Try a different search term or browse all categories.</p>
        <?php if ($search || $categoryId): ?>
            <a href="<?= url('/marketplace/index.php') ?>" class="btn btn-agri">Clear filters</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
        <?php foreach ($products as $p): ?>
            <?php $productUrl = url('/marketplace/product.php?id=' . $p['id']); ?>
            <div class="col">
                <div class="product-card">
                    <div class="product-card-img-wrap">
                        <a href="<?= $productUrl ?>" class="product-card-img-link">
                            <?php if ($p['image']): ?>
                                <img src="<?= url('/media.php?path=' . urlencode($p['image'])) ?>" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                            <?php else: ?>
                                <div class="img-placeholder w-100 h-100" style="min-height:160px">No image</div>
                            <?php endif; ?>
                        </a>
                    </div>
                    <a href="<?= $productUrl ?>" class="product-card-body text-decoration-none">
                        <div class="text-muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                            <?= htmlspecialchars($p['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="product-card-name" style="font-size: 1.15rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        
                        <div class="product-card-meta mb-3 d-flex flex-column gap-1" style="font-size: 0.85rem;">
                            <div><i class="bi bi-person text-muted"></i> <?= htmlspecialchars($p['farmer_name'] ?? 'Unknown Farmer', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-box text-muted"></i> <?= (int)$p['stock_quantity'] ?> in stock</span>
                                <?php if ($p['sold_count'] > 0): ?>
                                    <span class="text-success" style="font-weight: 600;"><i class="bi bi-cart-check"></i> <?= (int)$p['sold_count'] ?> sold</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="product-card-price">
                            ₱<?= number_format($p['price_per_unit'], 2) ?> / <?= htmlspecialchars($p['unit_type'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </a>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?= renderListPaginationBar($pagination, 'product') ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
