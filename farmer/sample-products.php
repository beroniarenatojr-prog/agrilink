<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Product.php';

requireRole('farmer');

$pdo       = Database::getInstance();
$prodModel = new Product($pdo);
$user      = currentUser();

// 8 predefined demo products
$sampleProducts = [
    [
        'name'          => 'Fresh Tomatoes',
        'category_slug' => 'vegetables',
        'unit_type'     => 'kg',
        'price'         => 55.00,
        'stock'         => 100,
        'description'   => 'Fresh, ripe tomatoes straight from the farm. Great for cooking and salads.',
        'unsplash_id'   => 'photo-1546094096-0df4bcabd337',
    ],
    [
        'name'          => 'Organic Kangkong',
        'category_slug' => 'vegetables',
        'unit_type'     => 'bundle',
        'price'         => 25.00,
        'stock'         => 80,
        'description'   => 'Freshly harvested water spinach grown without pesticides.',
        'unsplash_id'   => 'photo-1597362925123-77861d3fbac7',
    ],
    [
        'name'          => 'Cavendish Bananas',
        'category_slug' => 'fruits',
        'unit_type'     => 'dozen',
        'price'         => 120.00,
        'stock'         => 60,
        'description'   => 'Sweet and nutritious Cavendish bananas, freshly harvested.',
        'unsplash_id'   => 'photo-1571771894821-ce9b6c11b08e',
    ],
    [
        'name'          => 'Sweet Mangoes',
        'category_slug' => 'fruits',
        'unit_type'     => 'kg',
        'price'         => 90.00,
        'stock'         => 50,
        'description'   => 'Philippine Carabao mangoes — the sweetest in the world.',
        'unsplash_id'   => 'photo-1601493700631-2b16ec4b4716',
    ],
    [
        'name'          => 'Jasmine Rice',
        'category_slug' => 'grains-cereals',
        'unit_type'     => 'sack',
        'price'         => 2200.00,
        'stock'         => 20,
        'description'   => '50kg sack of premium jasmine rice, locally milled.',
        'unsplash_id'   => 'photo-1586201375761-83865001e31c',
    ],
    [
        'name'          => 'Garlic Bulbs',
        'category_slug' => 'herbs-spices',
        'unit_type'     => 'kg',
        'price'         => 180.00,
        'stock'         => 40,
        'description'   => 'Locally grown Philippine garlic, full and pungent.',
        'unsplash_id'   => 'photo-1591351765952-e2e503e3b2e0',
    ],
    [
        'name'          => 'Kamote (Sweet Potato)',
        'category_slug' => 'root-crops',
        'unit_type'     => 'kg',
        'price'         => 40.00,
        'stock'         => 120,
        'description'   => 'Orange-flesh sweet potatoes, great for snacks and cooking.',
        'unsplash_id'   => 'photo-1574226516831-e1dff420e562',
    ],
    [
        'name'          => 'Free-Range Eggs',
        'category_slug' => 'dairy-eggs',
        'unit_type'     => 'dozen',
        'price'         => 95.00,
        'stock'         => 70,
        'description'   => 'Farm-fresh free-range eggs from happy chickens.',
        'unsplash_id'   => 'photo-1582722872445-44dc5f7e3c8f',
    ],
];

// Resolve category IDs
$catMap = [];
$stmt   = $pdo->query('SELECT id, slug FROM categories');
foreach ($stmt->fetchAll() as $row) {
    $catMap[$row['slug']] = (int)$row['id'];
}

// Check which already exist
foreach ($sampleProducts as &$sp) {
    $sp['exists']      = $prodModel->existsByNameAndFarmer($sp['name'], (int)$user['id']);
    $sp['category_id'] = $catMap[$sp['category_slug']] ?? null;
}
unset($sp);

$pageTitle = 'Sample Products';
$activeNav = 'sample-products';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Sample Products</h1>
        <p class="page-subtitle mb-0">Seed your store with demo product listings</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <p style="font-size:0.9rem;color:var(--agri-text-muted)">
            Click "Seed All" to add all sample products to your store at once.
            Products with names you already have will be skipped.
            Images are downloaded from Unsplash.
        </p>
        <form method="post" action="<?= url('/farmer/sample-products/seed.php') ?>"
              data-vex-confirm="Add all sample products to your store? Existing names will be skipped.">
            <?= csrf_field() ?>
            <input type="hidden" name="seed_all" value="1">
            <button type="submit" class="btn btn-agri">Seed All</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-agri align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sampleProducts as $sp): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(str_replace('-', ' ', $sp['category_slug']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>₱<?= number_format($sp['price'], 2) ?> / <?= $sp['unit_type'] ?></td>
                    <td>
                        <?php if ($sp['exists']): ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Already added</span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Available</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$sp['exists']): ?>
                            <form method="post" action="<?= url('/farmer/sample-products/seed.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_name" value="<?= htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Add</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/sidebar_end.php'; ?>
