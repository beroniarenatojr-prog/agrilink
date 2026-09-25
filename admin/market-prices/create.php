<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole('admin');

$pdo = Database::getInstance();

$catStmt    = $pdo->query('SELECT id, name FROM categories ORDER BY name');
$categories = $catStmt->fetchAll();

$unitTypes = ['kg','sack','bundle','crate','box','piece','dozen','liter','pack'];

$pageTitle = 'Add Reference Price';
$activeNav = 'market-prices';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Add Reference Price</h1>
        <p class="page-subtitle mb-0">Set an admin baseline price for a category</p>
    </div>
    <a href="<?= url('/admin/market-prices.php') ?>" class="btn btn-agri-outline"><i class="ti ti-arrow-left me-1"></i> Back</a>
</div>

<div class="card shadow-sm" style="max-width:600px">
    <div class="card-body p-4">
        <form method="post" action="<?= url('/admin/market-prices/store.php') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">— Select —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="unit_type" class="form-label">Unit type</label>
                    <select class="form-select" id="unit_type" name="unit_type">
                        <?php foreach ($unitTypes as $u): ?>
                            <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="reference_price" class="form-label">Reference price (₱)</label>
                    <input type="number" class="form-control" id="reference_price" name="reference_price" min="0.01" step="0.01" required>
                </div>
                <div class="col-12 col-md-6">
                    <label for="effective_date" class="form-label">Effective date</label>
                    <input type="date" class="form-control" id="effective_date" name="effective_date"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-agri">Save Reference Price</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/sidebar_end.php'; ?>
