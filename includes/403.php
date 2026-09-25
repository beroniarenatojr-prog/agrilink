<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Access Denied — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= asset('/assets/css/agrilink.css') ?>" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100" style="background:var(--agri-bg)">
<div class="text-center py-5">
    <h1 class="page-title">Access Denied</h1>
    <p class="text-muted mb-4">You do not have permission to view this page.</p>
    <a href="<?= url('/marketplace/index.php') ?>" class="btn btn-agri">Go to Marketplace</a>
</div>
</body>
</html>
