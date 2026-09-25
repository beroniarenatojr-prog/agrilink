<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole('admin', 'farmer', 'buyer', 'logistics');

$user = currentUser();
$pdo  = Database::getInstance();

$stmt = $pdo->prepare(
    'SELECT id, name, email, phone, address, address_province, address_city, address_barangay, address_street, role
     FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
$addressValues = addressFieldsFromRow($profile ?: [], 'address');

$pageTitle = 'My Profile';
$activeNav = 'profile';
$useSidebar = in_array(currentRole(), ['farmer', 'admin', 'logistics'], true);

if ($useSidebar) {
    require_once __DIR__ . '/includes/sidebar.php';
} else {
    require_once __DIR__ . '/includes/header.php';
}
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">My Profile</h1>
        <p class="page-subtitle mb-0">Manage your account details</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-md-7 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Account Information</div>
            <div class="card-body p-4">
                <form method="post" action="<?= url('/profile/update.php') ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Full name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email address</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                            <div class="form-text">Email cannot be changed.</div>
                        </div>

                        <?php
                        $prefix = '';
                        $values = $addressValues;
                        $errors = [];
                        $showPhone = true;
                        $phoneValue = $profile['phone'] ?? '';
                        require __DIR__ . '/includes/address_fields.php';
                        ?>

                        <div class="col-12 mt-1">
                            <button type="submit" class="btn btn-agri">Save changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Account Details</div>
            <div class="card-body p-4">
                <dl class="mb-0" style="font-size:0.9rem">
                    <dt class="text-muted" style="font-weight:500;font-size:0.8rem">Role</dt>
                    <dd class="mb-3" style="text-transform:capitalize"><?= htmlspecialchars($profile['role'], ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>

                <?php if ($profile['role'] === 'farmer'): ?>
                    <a href="<?= url('/farmer/dashboard.php') ?>" class="btn btn-agri-outline w-100 mb-2" style="font-size:0.875rem">Go to Dashboard</a>
                <?php elseif ($profile['role'] === 'admin'): ?>
                    <a href="<?= url('/admin/dashboard.php') ?>" class="btn btn-agri-outline w-100 mb-2" style="font-size:0.875rem">Go to Dashboard</a>
                <?php elseif ($profile['role'] === 'logistics'): ?>
                    <a href="<?= url('/logistics/dashboard.php') ?>" class="btn btn-agri-outline w-100 mb-2" style="font-size:0.875rem">Go to Dashboard</a>
                <?php else: ?>
                    <a href="<?= url('/buyer/orders.php') ?>" class="btn btn-agri-outline w-100 mb-2" style="font-size:0.875rem">My Orders</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
if ($useSidebar) {
    require_once __DIR__ . '/includes/sidebar_end.php';
} else {
    require_once __DIR__ . '/includes/footer.php';
}
?>
