<?php
/**
 * Sidebar layout shell for farmer and admin pages.
 * Expects $pageTitle and $activeNav to be set before including.
 * Also outputs the full <html><head> opening.
 *
 * Usage:
 *   $pageTitle = 'Dashboard';
 *   $activeNav = 'dashboard';
 *   require_once '../../includes/sidebar.php'; // adjust path
 *
 * Close with includes/sidebar_end.php (or just the script tags).
 */

$role = currentRole();
$user = currentUser();
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$topbarFirstName = explode(' ', trim($user['name'] ?? ''), 2)[0] ?: 'there';

require_once __DIR__ . '/helpers.php';

function nav_active(string $key, string $active): string
{
    return $key === $active ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('/assets/css/agrilink.css') ?>" rel="stylesheet">
    <?php require __DIR__ . '/head_assets.php'; ?>
    <?php if (isset($extraHead))
        echo $extraHead; ?>
</head>

<body>

    <!-- Sidebar backdrop (mobile) -->
    <div class="agri-sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar -->
    <aside class="agri-sidebar" id="sidebar">
        <a class="agri-sidebar-brand"
            href="<?= url($role === 'admin' ? '/admin/dashboard.php' : '/farmer/dashboard.php') ?>">
            <span class="brand-icon"><i class="ti ti-flower"></i></span>
            <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </a>

        <?php if ($role === 'farmer'): ?>
            <ul class="agri-nav">
                <li><span class="agri-nav-section">Main</span></li>
                <li>
                    <a href="<?= url('/farmer/dashboard.php') ?>" class="<?= nav_active('dashboard', $activeNav ?? '') ?>">
                        <i class="ti ti-layout-dashboard"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= url('/farmer/products.php') ?>" class="<?= nav_active('products', $activeNav ?? '') ?>">
                        <i class="ti ti-package"></i> Products
                    </a>
                </li>
                <li>
                    <a href="<?= url('/farmer/orders.php') ?>" class="<?= nav_active('orders', $activeNav ?? '') ?>">
                        <i class="ti ti-receipt"></i> Orders
                    </a>
                </li>
                <li>
                    <a href="<?= url('/farmer/logistics.php') ?>" class="<?= nav_active('logistics', $activeNav ?? '') ?>">
                        <i class="ti ti-truck"></i> Logistics
                    </a>
                </li>
                <li><span class="agri-nav-section">Tools</span></li>
                <li>
                    <a href="<?= url('/market-prices.php') ?>" class="<?= nav_active('market-prices', $activeNav ?? '') ?>">
                        <i class="ti ti-trending-up"></i> Market Prices
                    </a>
                </li>
                <li>
                    <a href="<?= url('/farmer/sample-products.php') ?>"
                        class="<?= nav_active('sample-products', $activeNav ?? '') ?>">
                        <i class="ti ti-stars"></i> Sample Products
                    </a>
                </li>
                <li><span class="agri-nav-section">Account</span></li>
                <li>
                    <a href="<?= url('/profile.php') ?>" class="<?= nav_active('profile', $activeNav ?? '') ?>">
                        <i class="ti ti-user-circle"></i> Profile
                    </a>
                </li>
            </ul>

        <?php elseif ($role === 'admin'): ?>
            <ul class="agri-nav">
                <li><span class="agri-nav-section">Overview</span></li>

                <li>
                    <a href="<?= url('/admin/dashboard.php') ?>" class="<?= nav_active('dashboard', $activeNav ?? '') ?>">
                        <i class="ti ti-dashboard"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= url('/admin/orders.php') ?>" class="<?= nav_active('orders', $activeNav ?? '') ?>">
                        <i class="ti ti-receipt"></i> Orders
                    </a>
                </li>
                <li>
                    <a href="<?= url('/admin/users.php') ?>" class="<?= nav_active('users', $activeNav ?? '') ?>">
                        <i class="ti ti-users"></i> Users
                    </a>
                </li>
                <li><span class="agri-nav-section">Prices</span></li>
                <li>
                    <a href="<?= url('/admin/market-prices.php') ?>"
                        class="<?= nav_active('market-prices', $activeNav ?? '') ?>">
                        <i class="ti ti-tag"></i> Reference Prices
                    </a>
                </li>
                <li>
                    <a href="<?= url('/market-prices.php') ?>"
                        class="<?= nav_active('price-overview', $activeNav ?? '') ?>">
                        <i class="ti ti-trending-up"></i> Price Overview
                    </a>
                </li>
                <li><span class="agri-nav-section">Account</span></li>
                <li>
                    <a href="<?= url('/profile.php') ?>" class="<?= nav_active('profile', $activeNav ?? '') ?>">
                        <i class="ti ti-user-circle"></i> Profile
                    </a>
                </li>

            </ul>
        <?php elseif ($role === 'logistics'): ?>
            <ul class="agri-nav">
                <li><span class="agri-nav-section">Logistics</span></li>
                <li>
                    <a href="<?= url('/logistics/dashboard.php') ?>"
                        class="<?= nav_active('dashboard', $activeNav ?? '') ?>">
                        <i class="ti ti-layout-dashboard"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= url('/logistics/deliveries.php') ?>"
                        class="<?= nav_active('deliveries', $activeNav ?? '') ?>">
                        <i class="ti ti-truck"></i> Deliveries
                    </a>
                </li>
                <li>
                    <a href="<?= url('/logistics/assigned-orders.php') ?>"
                        class="<?= nav_active('assigned-orders', $activeNav ?? '') ?>">
                        <i class="ti ti-receipt"></i> Assigned Orders
                    </a>
                </li>
                <li>
                    <a href="<?= url('/logistics/delivery-history.php') ?>"
                        class="<?= nav_active('delivery-history', $activeNav ?? '') ?>">
                        <i class="ti ti-history"></i> Delivery History
                    </a>
                </li>
                <li><span class="agri-nav-section">Account</span></li>
                <li>
                    <a href="<?= url('/profile.php') ?>" class="<?= nav_active('profile', $activeNav ?? '') ?>">
                        <i class="ti ti-user-circle"></i> Profile
                    </a>
                </li>
            </ul>

        <?php endif; ?>

        <div class="agri-sidebar-footer">

            <div class="fw-600" style="font-size:0.875rem;color:var(--agri-text)">
                <?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div style="font-size:0.775rem;margin-top:0.1rem;text-transform:capitalize">
                <?= htmlspecialchars($role ?? '', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <form method="post" action="<?= url('/logout.php') ?>" class="mt-2">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100" style="font-size:0.8rem">Sign
                    out</button>
            </form>
        </div>
    </aside>

    <!-- Main wrapper -->
    <div class="agri-main">
        <!-- Topbar -->
        <header class="agri-topbar">
            <button class="agri-menu-toggle" id="menuToggle" aria-label="Open menu">
                <i class="ti ti-menu-2"></i>
            </button>

            <div class="agri-topbar-start">
                <p class="agri-topbar-greeting"><?= timeGreeting() ?>,
                    <span class="agri-topbar-greeting-name"><?= htmlspecialchars($topbarFirstName, ENT_QUOTES, 'UTF-8') ?></span>
                </p>
            </div>

            <div class="agri-topbar-actions">
                <a href="<?= url('/profile.php') ?>" class="agri-topbar-user" title="My profile">
                    <span
                        class="agri-topbar-avatar"><?= htmlspecialchars(userInitials($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="agri-topbar-user-info d-none d-lg-flex">
                        <span
                            class="agri-topbar-user-name"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        <span
                            class="agri-topbar-user-role"><?= htmlspecialchars($role ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </a>
            </div>
        </header>

        <!-- Page content -->
        <main class="agri-content">