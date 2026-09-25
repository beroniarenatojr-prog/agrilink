<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../classes/User.php';

requireRole('admin');

$pdo = Database::getInstance();
$userModel = new User($pdo);

$addErrors = [];
$old = [];
$showAddModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    csrf_verify();

    $addressFields = addressFieldsFromPost();
    $old = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? 'buyer',
    ];
    $old = array_merge($old, $addressFields);

    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if ($old['name'] === '') {
        $addErrors['name'] = 'Full name is required.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $addErrors['email'] = 'Enter a valid email address.';
    }
    $addErrors = array_merge($addErrors, validatePhoneAndAddress($old['phone'], $addressFields));
    if (strlen($password) < 8) {
        $addErrors['password'] = 'Password must be at least 8 characters.';
    }
    if ($password !== $password2) {
        $addErrors['password_confirm'] = 'Passwords do not match.';
    }
    if (!in_array($old['role'], ['buyer', 'farmer', 'logistics'], true)) {
        $addErrors['role'] = 'Select a valid role.';
    }

    if (empty($addErrors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $addErrors['email'] = 'This email is already registered.';
        }
    }

    if (empty($addErrors)) {
        $fullAddress = formatPhilippineAddress($addressFields);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password, phone, address, address_province, address_city, address_barangay, address_street, role)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $old['name'],
            $old['email'],
            $hash,
            $old['phone'],
            $fullAddress,
            $addressFields['province'],
            $addressFields['city'],
            $addressFields['barangay'] !== '' ? $addressFields['barangay'] : null,
            $addressFields['street'] !== '' ? $addressFields['street'] : null,
            $old['role'],
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User added successfully.'];
        redirect('/admin/users.php');
    }

    $showAddModal = true;
}

$roleFilter = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');

$userTotal  = $userModel->countAll($roleFilter, $search);
$pagination = paginationMeta($userTotal);
$users      = $userModel->getAll($roleFilter, $search, $pagination['per_page'], $pagination['offset']);

$pageTitle = 'Users';
$activeNav = 'users';
require_once __DIR__ . '/../includes/sidebar.php';
?>


<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-0">Users</h1>
        <p class="page-subtitle mb-0">All registered platform users</p>
    </div>
    <button type="button" class="btn btn-agri" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="ti ti-user-plus me-1"></i> Add User
    </button>
</div>


<!-- Filters -->
<form method="get" class="row g-2 mb-4">
    <div class="col-auto">
        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All roles</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="farmer" <?= $roleFilter === 'farmer' ? 'selected' : '' ?>>Farmer</option>
            <option value="buyer" <?= $roleFilter === 'buyer' ? 'selected' : '' ?>>Buyer</option>
            <option value="logistics" <?= $roleFilter === 'logistics' ? 'selected' : '' ?>>Logistics</option>
        </select>
    </div>
    <div class="col">
        <input type="search" name="search" class="form-control form-control-sm" placeholder="Search by name or email..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-agri">Filter</button>
    </div>
</form>

<?php if (empty($users)): ?>
    <div class="empty-state">
        <h2>No users found</h2>
        <p>Try adjusting your search or filter.</p>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-agri align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="fw-600"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"
                                    style="text-transform:capitalize;font-size:0.8rem">
                                    <?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= renderListFooter($pagination, 'user') ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/sidebar_end.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        <?php if ($showAddModal): ?>
            const el = document.getElementById('addUserModal');
            if (el) {
                const modal = new bootstrap.Modal(el);
                modal.show();
            }
        <?php endif; ?>
    });
</script>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 620px">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Add User</h5>
                    <div class="text-muted" style="font-size:0.875rem">Create a new buyer or farmer account</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="<?= url('/admin/users.php') ?>" novalidate>
                    <input type="hidden" name="action" value="add_user">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Full name</label>
                            <input type="text"
                                class="form-control <?= !empty($addErrors['name']) ? 'is-invalid' : '' ?>" id="name"
                                name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                required>
                            <?php if (!empty($addErrors['name'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($addErrors['name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email"
                                class="form-control <?= !empty($addErrors['email']) ? 'is-invalid' : '' ?>" id="email"
                                name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                required>
                            <?php if (!empty($addErrors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($addErrors['email'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel"
                                class="form-control <?= !empty($addErrors['phone']) ? 'is-invalid' : '' ?>" id="phone"
                                name="phone" value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                required>
                            <?php if (!empty($addErrors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($addErrors['phone'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        $prefix = '';
                        $values = [
                            'province' => $old['province'] ?? '',
                            'city'     => $old['city'] ?? '',
                            'barangay' => $old['barangay'] ?? '',
                            'street'   => $old['street'] ?? '',
                        ];
                        $errors = $addErrors;
                        $showPhone = false;
                        require __DIR__ . '/../includes/address_fields.php';
                        ?>

                        <div class="col-12">
                            <label class="form-label">I am a</label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="role" id="roleBuyer"
                                        value="buyer" <?= ($old['role'] ?? 'buyer') === 'buyer' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="roleBuyer">Buyer / Consumer</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="role" id="roleFarmer"
                                        value="farmer" <?= ($old['role'] ?? '') === 'farmer' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="roleFarmer">Farmer / Seller</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="role" id="roleLogistics"
                                        value="logistics" <?= ($old['role'] ?? '') === 'logistics' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="roleLogistics">Logistics</label>
                                </div>
                            </div>
                            <?php if (!empty($addErrors['role'])): ?>
                                <div class="text-danger" style="font-size:0.8rem">
                                    <?= htmlspecialchars($addErrors['role'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password"
                                class="form-control <?= !empty($addErrors['password']) ? 'is-invalid' : '' ?>"
                                id="password" name="password" autocomplete="new-password" required>
                            <?php if (!empty($addErrors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($addErrors['password'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Minimum 8 characters.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="password_confirm" class="form-label">Confirm password</label>
                            <input type="password"
                                class="form-control <?= !empty($addErrors['password_confirm']) ? 'is-invalid' : '' ?>"
                                id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                            <?php if (!empty($addErrors['password_confirm'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($addErrors['password_confirm'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-agri w-100">Create account</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php if ($showAddModal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('addUserModal');
            if (!modalEl) return;
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    </script>
<?php endif; ?>