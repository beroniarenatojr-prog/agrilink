<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin', 'farmer', 'buyer', 'logistics');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/profile.php');
    exit;
}

csrf_verify();

$user          = currentUser();
$name          = trim($_POST['name'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$addressFields = addressFieldsFromPost();

if ($name === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Full name is required.'];
    redirect('/profile.php');
    exit;
}

$errors = validatePhoneAndAddress($phone, $addressFields);
if (!empty($errors)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' ', array_values($errors))];
    redirect('/profile.php');
    exit;
}

$fullAddress = formatPhilippineAddress($addressFields);

$pdo  = Database::getInstance();
$stmt = $pdo->prepare(
    'UPDATE users SET name = ?, phone = ?, address = ?, address_province = ?, address_city = ?, address_barangay = ?, address_street = ? WHERE id = ?'
);
$stmt->execute([
    $name,
    $phone,
    $fullAddress,
    $addressFields['province'],
    $addressFields['city'],
    $addressFields['barangay'] !== '' ? $addressFields['barangay'] : null,
    $addressFields['street'] !== '' ? $addressFields['street'] : null,
    $user['id'],
]);

$_SESSION['user']['name'] = $name;

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
redirect('/profile.php');
exit;
