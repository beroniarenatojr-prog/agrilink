<?php
/**
 * Front controller / entry point.
 * Redirects root to marketplace. All direct page URLs are also valid.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

$homePaths = [
    '/',
    '/index.php',
    APP_BASE,
    APP_BASE . '/',
    APP_BASE . '/index.php',
];

if (in_array($uri, $homePaths, true)) {
    if (isLoggedIn()) {
        $role = currentRole();
        if ($role === 'farmer') {
            redirect('/farmer/dashboard.php');
        } elseif ($role === 'admin') {
            redirect('/admin/dashboard.php');
        } elseif ($role === 'logistics') {
            redirect('/logistics/dashboard.php');
        } else {
            redirect('/marketplace/index.php');
        }
    } else {
        redirect('/marketplace/index.php');
    }
}

// Fallback 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('/assets/css/agrilink.css') ?>" rel="stylesheet">
    <link rel="icon" href="<?= asset('/favicon.svg') ?>" type="image/svg+xml">
    <link rel="icon" href="<?= asset('/favicon.ico') ?>" sizes="48x48">
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100" style="background:var(--agri-bg)">
    <div class="text-center py-5">
        <h1 class="page-title">Page Not Found</h1>
        <p class="text-muted mb-4">The page you're looking for doesn't exist.</p>
        <a href="<?= url('/marketplace/index.php') ?>" class="btn btn-agri">Go to Marketplace</a>
    </div>
</body>

</html>