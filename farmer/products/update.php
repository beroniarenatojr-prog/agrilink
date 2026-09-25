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

$productId   = (int)($_POST['product_id'] ?? 0);
$existing    = $prodModel->findById($productId);

if (!$existing || (int)$existing['farmer_id'] !== (int)$user['id']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Product not found.'];
    redirect('/farmer/products.php');
    exit;
}

$name        = trim($_POST['name']         ?? '');
$categoryId  = (int)($_POST['category_id']  ?? 0);
$unitType    = $_POST['unit_type']          ?? 'kg';
$price       = (float)($_POST['price_per_unit'] ?? 0);
$stock       = (int)($_POST['stock_quantity']    ?? 0);
$description = trim($_POST['description']  ?? '');
$isAvailable = isset($_POST['is_available']) ? 1 : 0;

if ($name === '' || $price <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name and price are required.'];
    redirect('/farmer/products/edit.php?id=' . $productId);
    exit;
}

// Handle main image upload
$mainImage = $existing['image'];
if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $newImage = uploadImage($_FILES['image'], 'p');
    if ($newImage === null) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid main image.'];
        redirect('/farmer/products/edit.php?id=' . $productId);
        exit;
    }
    deleteUploadedFile($existing['image']);
    $mainImage = $newImage;
}

// Handle additional images
$additionalImages = $existing['additional_images'];
if (!empty($_FILES['additional_images']['tmp_name'][0])) {
    // Delete old additional images
    foreach (($additionalImages ?? []) as $old) {
        deleteUploadedFile($old);
    }
    $additionalImages = [];
    foreach ($_FILES['additional_images']['tmp_name'] as $k => $tmp) {
        if (count($additionalImages) >= 3) break;
        if (!$tmp || $_FILES['additional_images']['error'][$k] !== UPLOAD_ERR_OK) continue;
        $singleFile = [
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => $_FILES['additional_images']['size'][$k],
        ];
        $path = uploadImage($singleFile, 'pa');
        if ($path) $additionalImages[] = $path;
    }
}

$prodModel->update($productId, [
    'farmer_id'         => (int)$user['id'],
    'category_id'       => $categoryId ?: null,
    'name'              => $name,
    'description'       => $description,
    'unit_type'         => $unitType,
    'price_per_unit'    => $price,
    'stock_quantity'    => $stock,
    'image'             => $mainImage,
    'additional_images' => $additionalImages ?: null,
    'is_available'      => $isAvailable,
]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Product updated successfully.'];
redirect('/farmer/products.php');
exit;
