<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Product.php';

requireRole('farmer');

$pdo       = Database::getInstance();
$prodModel = new Product($pdo);
$user       = currentUser();
$farmerId   = (int) $user['id'];

$productTotal = $prodModel->countForFarmer($farmerId);
$pagination   = paginationMeta($productTotal);
$products     = $prodModel->getByFarmer($farmerId, $pagination['per_page'], $pagination['offset']);

$pageTitle = 'My Products';
$activeNav = 'products';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">My Products</h1>
        <p class="page-subtitle mb-0">Manage your product listings</p>
    </div>
    <a href="<?= url('/farmer/products/create.php') ?>" class="btn btn-agri">Add Product</a>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <h2>No products listed yet</h2>
        <p>Start selling by adding your first product.</p>
        <a href="<?= url('/farmer/products/create.php') ?>" class="btn btn-agri">Add Product</a>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-agri align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Sold</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($p['image']): ?>
                                    <img src="<?= url('/media.php?path=' . urlencode($p['image'])) ?>"
                                         style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid var(--agri-border)"
                                         alt="">
                                <?php endif; ?>
                                <span class="fw-600"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($p['category_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= peso((float)$p['price_per_unit']) ?>/<?= htmlspecialchars($p['unit_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($p['stock_quantity'] == 0): ?>
                                <span class="text-danger fw-600">Out of stock</span>
                            <?php elseif ($p['stock_quantity'] <= 5): ?>
                                <span class="text-warning fw-600"><?= (int)$p['stock_quantity'] ?> (low)</span>
                            <?php else: ?>
                                <?= (int)$p['stock_quantity'] ?>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$p['sold_count'] ?></td>
                        <td>
                            <?php if ($p['is_available'] && $p['stock_quantity'] > 0): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= url('/farmer/products/edit.php?id=' . $p['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('/farmer/products/delete.php') ?>"
                                      data-vex-confirm="Delete this product? This cannot be undone."
                                      data-vex-confirm-variant="danger">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= renderListFooter($pagination, 'product') ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/sidebar_end.php'; ?>
