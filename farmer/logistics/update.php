<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/FarmerLogistics.php';

requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/farmer/logistics.php');
    exit;
}

csrf_verify();

$pdo = Database::getInstance();
$userModel = new User($pdo);
$farmerLogisticsModel = new FarmerLogistics($pdo);

$farmerId = (int) currentUser()['id'];
$logisticsUserId = (int) ($_POST['logistics_user_id'] ?? 0);

if ($logisticsUserId <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid logistics user.'];
    redirect('/farmer/logistics.php');
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$active = !empty($_POST['is_active']) ? 1 : 0;
$newPassword = trim((string) ($_POST['password'] ?? ''));

if ($name === '' || $email === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name and email are required.'];
    redirect('/farmer/logistics.php');
    exit;
}

$existing = $farmerLogisticsModel->findLogisticsUserForFarmer($farmerId, $logisticsUserId);
if (!$existing) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Logistics user not found.'];
    redirect('/farmer/logistics.php');
    exit;
}

$emailOwner = $userModel->findByEmail($email);
if ($emailOwner && (int) $emailOwner['id'] !== $logisticsUserId) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'This email is already used by another account.'];
    redirect('/farmer/logistics.php');
    exit;
}

$stmt = $pdo->prepare(
    'UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ? AND role = ?'
);
$stmt->execute([
    $name,
    $email,
    $phone !== '' ? $phone : null,
    $address !== '' ? $address : null,
    $logisticsUserId,
    'logistics',
]);

if ($newPassword !== '') {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ? AND role = ?');
    $stmt->execute([$hash, $logisticsUserId, 'logistics']);
}

$stmt = $pdo->prepare(
    'UPDATE farmer_logistics SET is_active = ?, updated_at = NOW() WHERE farmer_id = ? AND logistics_user_id = ?'
);
$stmt->execute([$active ? 1 : 0, $farmerId, $logisticsUserId]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Logistics user updated.'];
redirect('/farmer/logistics.php');
