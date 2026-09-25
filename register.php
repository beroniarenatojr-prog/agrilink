<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

requireGuest();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $addressFields = addressFieldsFromPost();
    $old = [
        'name'    => trim($_POST['name']    ?? ''),
        'email'   => trim($_POST['email']   ?? ''),
        'phone'   => trim($_POST['phone']   ?? ''),
        'role'    => $_POST['role']         ?? 'buyer',
    ];
    $old = array_merge($old, $addressFields);
    $password  = $_POST['password']         ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if ($old['name'] === '') {
        $errors['name'] = 'Full name is required.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    $errors = array_merge($errors, validatePhoneAndAddress($old['phone'], $addressFields));
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($password !== $password2) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }
    if (!in_array($old['role'], ['buyer','farmer'], true)) {
        $errors['role'] = 'Select a valid role.';
    }

    if (empty($errors)) {
        $pdo = Database::getInstance();

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    if (empty($errors)) {
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
        $userId = (int)$pdo->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => $userId,
            'name'  => $old['name'],
            'email' => $old['email'],
            'role'  => $old['role'],
        ];
        redirectAfterLogin();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('/assets/css/agrilink.css') ?>" rel="stylesheet">
    <?php require __DIR__ . '/includes/head_assets.php'; ?>
</head>
<body>

<div class="auth-wrapper" style="align-items:flex-start;padding-top:2rem">
    <div class="auth-card" style="max-width:560px">
        <div class="auth-brand">
            <div class="brand-icon"><i class="ti ti-flower"></i></div>
            <div class="auth-title">Create your account</div>
            <div class="auth-subtitle">Join <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> as a buyer or farmer</div>
        </div>

        <form method="post" action="<?= url('/register.php') ?>" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3">
                <!-- Role selection -->
                <div class="col-12">
                    <label class="form-label">I am a</label>
                    <div class="d-flex gap-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="roleBuyer" value="buyer" <?= ($old['role'] ?? 'buyer') === 'buyer' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="roleBuyer">Buyer / Consumer</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role" id="roleFarmer" value="farmer" <?= ($old['role'] ?? '') === 'farmer' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="roleFarmer">Farmer / Seller</label>
                        </div>
                    </div>
                    <?php if (!empty($errors['role'])): ?>
                        <div class="text-danger" style="font-size:0.8rem"><?= htmlspecialchars($errors['role']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Name -->
                <div class="col-12">
                    <label for="name" class="form-label">Full name</label>
                    <input type="text" class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    <?php if (!empty($errors['name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="col-12">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Phone + address -->
                <?php
                $prefix = '';
                $values = [
                    'province' => $old['province'] ?? '',
                    'city'     => $old['city'] ?? '',
                    'barangay' => $old['barangay'] ?? '',
                    'street'   => $old['street'] ?? '',
                ];
                $showPhone = true;
                $phoneValue = $old['phone'] ?? '';
                require __DIR__ . '/includes/address_fields.php';
                ?>

                <!-- Password -->
                <div class="col-12 col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Minimum 8 characters.</div>
                </div>

                <!-- Confirm password -->
                <div class="col-12 col-md-6">
                    <label for="password_confirm" class="form-label">Confirm password</label>
                    <div class="password-field">
                        <input type="password" class="form-control <?= !empty($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" data-password-toggle="password_confirm" aria-label="Show password" aria-pressed="false">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                    <?php if (!empty($errors['password_confirm'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12 mt-1">
                    <button type="submit" class="btn btn-agri w-100">Create account</button>
                </div>
            </div>
        </form>

        <p class="text-center mt-3 mb-0" style="font-size:0.875rem">
            Already have an account? <a href="<?= url('/login.php') ?>">Sign in</a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.passwordToggle);
        var show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.querySelector('i').className = show ? 'ti ti-eye-off' : 'ti ti-eye';
    });
});
</script>
</body>
</html>
