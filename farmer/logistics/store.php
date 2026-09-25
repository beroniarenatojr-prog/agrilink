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

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$active = !empty($_POST['is_active']) ? 1 : 0;

if ($name === '' || $email === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name and email are required.'];
    redirect('/farmer/logistics.php');
    exit;
}

$existing = $userModel->findByEmail($email);
$logisticsUserId = 0;
$tempPassword = null;
$isNewUser = false;

if ($existing) {
    if (($existing['role'] ?? '') !== 'logistics') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'This email is already used by another account.'];
        redirect('/farmer/logistics.php');
        exit;
    }

    if (!$farmerLogisticsModel->userBelongsToFarmer($farmerId, (int) $existing['id'])) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'This logistics user already belongs to another farmer.'];
        redirect('/farmer/logistics.php');
        exit;
    }

    $logisticsUserId = (int) $existing['id'];

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
} else {
    $tempPassword = bin2hex(random_bytes(6));
    $hash = password_hash($tempPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, phone, address, role) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([
        $name,
        $email,
        $hash,
        $phone !== '' ? $phone : null,
        $address !== '' ? $address : null,
        'logistics',
    ]);
    $logisticsUserId = (int) $pdo->lastInsertId();
    $isNewUser = true;

    $farmerLogisticsModel->createAssignment($farmerId, $logisticsUserId);
}

$stmt = $pdo->prepare(
    'UPDATE farmer_logistics SET is_active = ?, updated_at = NOW() WHERE farmer_id = ? AND logistics_user_id = ?'
);
$stmt->execute([$active ? 1 : 0, $farmerId, $logisticsUserId]);

if ($isNewUser && $tempPassword !== null) {
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Logistics user created. Temporary password: ' . $tempPassword . ' (share this once; they can change it after login).',
    ];
} else {
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Logistics user saved.'];
}

redirect('/farmer/logistics.php');
