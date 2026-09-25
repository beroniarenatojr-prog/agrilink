<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/MarketPrice.php';

requireRole('admin');

$pdo     = Database::getInstance();
$mpModel = new MarketPrice($pdo);

$prices = $mpModel->getAll();

$pageTitle = 'Reference Prices';
$activeNav = 'market-prices';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Reference Prices</h1>
        <p class="page-subtitle mb-0">Manage admin-set baseline prices per category</p>
    </div>
    <a href="<?= url('/admin/market-prices/create.php') ?>" class="btn btn-agri">Add Reference Price</a>
</div>

<?php if (empty($prices)): ?>
    <div class="empty-state">
        <h2>No reference prices set</h2>
        <p>Add a reference price to help farmers benchmark their listings.</p>
        <a href="<?= url('/admin/market-prices/create.php') ?>" class="btn btn-agri">Add Reference Price</a>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-agri align-middle mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Reference Price</th>
                        <th>Effective Date</th>
                        <th>Set By</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prices as $p): ?>
                    <tr>
                        <td class="fw-600"><?= htmlspecialchars($p['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($p['unit_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= peso((float)$p['reference_price']) ?></td>
                        <td><?= date('M j, Y', strtotime($p['effective_date'])) ?></td>
                        <td><?= htmlspecialchars($p['admin_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $p['notes'] ? htmlspecialchars(mb_strimwidth($p['notes'], 0, 50, '…'), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= url('/admin/market-prices/edit.php?id=' . $p['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form method="post" action="<?= url('/admin/market-prices/delete.php') ?>"
                                      data-vex-confirm="Delete this reference price?"
                                      data-vex-confirm-variant="danger">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="price_id" value="<?= $p['id'] ?>">
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
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/sidebar_end.php'; ?>
