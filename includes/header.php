<?php
/**
 * Top navbar for buyer / guest / auth pages.
 * Expects $pageTitle to be set before including.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$cartCount = 0;
if (isLoggedIn() && currentRole() === 'buyer') {
    $cart = $_SESSION['cart'] ?? [];
    foreach ($cart as $item) {
        $cartCount += (int)($item['quantity'] ?? 1);
    }
}

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('/assets/css/agrilink.css') ?>" rel="stylesheet">
    <?php require __DIR__ . '/head_assets.php'; ?>
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="agri-layout">

<nav class="agri-navbar navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= url('/marketplace/index.php') ?>">
            <span class="brand-icon"><i class="ti ti-flower"></i></span>
            <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentPath, '/marketplace') ? 'active' : '' ?>" href="<?= url('/marketplace/index.php') ?>">Marketplace</a>
                </li>
                <?php if (isLoggedIn() && in_array(currentRole(), ['farmer','admin'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentPath, '/market-prices') ? 'active' : '' ?>" href="<?= url('/market-prices.php') ?>">Market Prices</a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav align-items-center gap-1">
                <?php if (isLoggedIn()): ?>
                    <?php if (currentRole() === 'buyer'): ?>
                        <li class="nav-item">
                            <a class="nav-link cart-badge-link" href="<?= url('/cart/index.php') ?>" title="Cart">
                                <i class="ti ti-shopping-cart fs-5"></i>
                                <?php if ($cartCount > 0): ?>
                                    <span class="cart-count"><?= min($cartCount, 99) ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= str_contains($currentPath, '/buyer/orders') ? 'active' : '' ?>" href="<?= url('/buyer/orders.php') ?>">My Orders</a>
                        </li>
                    <?php elseif (currentRole() === 'farmer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/farmer/dashboard.php') ?>">Dashboard</a>
                        </li>
                    <?php elseif (currentRole() === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/admin/dashboard.php') ?>">Admin</a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-600" href="#" role="button" data-bs-toggle="dropdown">
                            <?= htmlspecialchars(currentUser()['name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg" style="border-radius: var(--agri-radius-sm);">
                            <li><a class="dropdown-item" href="<?= url('/profile.php') ?>">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= url('/logout.php') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item text-danger">Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('/login.php') ?>">Sign in</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-agri btn-sm ms-1" href="<?= url('/register.php') ?>">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="agri-main-content py-4">
    <div class="container">
