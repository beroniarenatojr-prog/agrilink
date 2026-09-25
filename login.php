<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireGuest();

$error = '';
$next  = $_GET['next'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            redirectAfterLogin($_POST['next'] ?? null);
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= url('/assets/css/agrilink.css') ?>" rel="stylesheet">
    <?php require __DIR__ . '/includes/head_assets.php'; ?>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-icon"><i class="ti ti-flower"></i></div>
            <div class="auth-title">Welcome back</div>
            <div class="auth-subtitle">Sign in to your <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> account</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 mb-3" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('/login.php') ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-agri w-100">Sign in</button>
        </form>

        <p class="text-center mt-3 mb-0" style="font-size:0.875rem">
            No account yet? <a href="<?= url('/register.php') ?>">Create one</a>
        </p>
        <p class="text-center mt-2 mb-0" style="font-size:0.875rem">
            <a href="<?= url('/marketplace/index.php') ?>" class="text-muted"><i class="bi bi-arrow-left"></i> Go to Marketplace</a>
        </p>

        <div class="auth-demo">
            <div class="auth-demo__label">Quick demo — tap to fill credentials</div>
            <div class="auth-demo__grid">
                <button type="button" class="auth-demo__btn auth-demo__btn--admin" onclick="fillDemo('admin@agrilink.com','admin1234')">
                    <i class="ti ti-shield-check"></i>
                    <span>Admin</span>
                </button>
                <button type="button" class="auth-demo__btn auth-demo__btn--farmer" onclick="fillDemo('farmer@agrilink.com','farmer1234')">
                    <i class="ti ti-plant-2"></i>
                    <span>Farmer</span>
                </button>
                <button type="button" class="auth-demo__btn auth-demo__btn--buyer" onclick="fillDemo('john@agrilink.com','buyer1234')">
                    <i class="ti ti-shopping-bag"></i>
                    <span>Buyer</span>
                </button>
                <button type="button" class="auth-demo__btn auth-demo__btn--logistics" onclick="fillDemo('maria@agrilink.com','logistics1234')">
                    <i class="ti ti-truck-delivery"></i>
                    <span>Logistics</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillDemo(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>
</body>
</html>
