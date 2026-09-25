<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../classes/FarmerLogistics.php';

requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/farmer/logistics.php');
    exit;
}

csrf_verify();

$pdo = Database::getInstance();
$model = new FarmerLogistics($pdo);

$farmerId = (int) currentUser()['id'];
$logisticsUserId = (int) ($_POST['logistics_user_id'] ?? 0);

if ($logisticsUserId <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid logistics user.'];
    redirect('/farmer/logistics.php');
    exit;
}

$existing = $model->findLogisticsUserForFarmer($farmerId, $logisticsUserId);
if (!$existing) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Logistics user not found.'];
    redirect('/farmer/logistics.php');
    exit;
}

$model->deleteAssignment($farmerId, $logisticsUserId);
$_SESSION['flash'] = ['type' => 'success', 'message' => 'Logistics user removed.'];

redirect('/farmer/logistics.php');
