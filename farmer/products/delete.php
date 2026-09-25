<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/Product.php';

requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/farmer/products.php');
    exit;
}

csrf_verify();

$user      = currentUser();
$pdo       = Database::getInstance();
$prodModel = new Product($pdo);

$productId = (int)($_POST['product_id'] ?? 0);
$existing  = $prodModel->findById($productId);

if (!$existing || (int)$existing['farmer_id'] !== (int)$user['id']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Product not found.'];
    redirect('/farmer/products.php');
    exit;
}

// Delete uploaded files
deleteUploadedFile($existing['image']);
foreach (($existing['additional_images'] ?? []) as $img) {
    deleteUploadedFile($img);
}

$prodModel->delete($productId, (int)$user['id']);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Product deleted.'];
redirect('/farmer/products.php');
exit;
