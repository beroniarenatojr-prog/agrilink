<?php
/**
 * Run once after importing seed.sql to fix demo passwords with real bcrypt hashes.
 * Usage: php database/hash_seed.php
 */
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getInstance();

$users = [
    ['email' => 'admin@agrilink.com',   'password' => 'admin1234'],
    ['email' => 'farmer@agrilink.com',  'password' => 'farmer1234'],
    ['email' => 'buyer@agrilink.com',   'password' => 'buyer1234'],
    ['email' => 'maria@agrilink.com',   'password' => 'logistics1234'],
];

$stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt->execute([$hash, $u['email']]);
    echo "Updated: {$u['email']}\n";
}

echo "Done.\n";
