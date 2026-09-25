<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Product.php';

requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/farmer/sample-products.php');
    exit;
}

csrf_verify();

$user      = currentUser();
$pdo       = Database::getInstance();
$prodModel = new Product($pdo);
$farmerId  = (int)$user['id'];

$sampleProducts = [
    ['name'=>'Fresh Tomatoes',      'slug'=>'vegetables',       'unit'=>'kg',     'price'=>55.00,   'stock'=>100, 'desc'=>'Fresh, ripe tomatoes straight from the farm.', 'unsplash'=>'photo-1546094096-0df4bcabd337'],
    ['name'=>'Organic Kangkong',    'slug'=>'vegetables',       'unit'=>'bundle', 'price'=>25.00,   'stock'=>80,  'desc'=>'Freshly harvested water spinach grown without pesticides.','unsplash'=>'photo-1597362925123-77861d3fbac7'],
    ['name'=>'Cavendish Bananas',   'slug'=>'fruits',           'unit'=>'dozen',  'price'=>120.00,  'stock'=>60,  'desc'=>'Sweet and nutritious Cavendish bananas.','unsplash'=>'photo-1571771894821-ce9b6c11b08e'],
    ['name'=>'Sweet Mangoes',       'slug'=>'fruits',           'unit'=>'kg',     'price'=>90.00,   'stock'=>50,  'desc'=>'Philippine Carabao mangoes.','unsplash'=>'photo-1601493700631-2b16ec4b4716'],
    ['name'=>'Jasmine Rice',        'slug'=>'grains-cereals',   'unit'=>'sack',   'price'=>2200.00, 'stock'=>20,  'desc'=>'50kg sack of premium jasmine rice.','unsplash'=>'photo-1586201375761-83865001e31c'],
    ['name'=>'Garlic Bulbs',        'slug'=>'herbs-spices',     'unit'=>'kg',     'price'=>180.00,  'stock'=>40,  'desc'=>'Locally grown Philippine garlic.','unsplash'=>'photo-1591351765952-e2e503e3b2e0'],
    ['name'=>'Kamote (Sweet Potato)','slug'=>'root-crops',      'unit'=>'kg',     'price'=>40.00,   'stock'=>120, 'desc'=>'Orange-flesh sweet potatoes.','unsplash'=>'photo-1574226516831-e1dff420e562'],
    ['name'=>'Free-Range Eggs',     'slug'=>'dairy-eggs',       'unit'=>'dozen',  'price'=>95.00,   'stock'=>70,  'desc'=>'Farm-fresh free-range eggs.','unsplash'=>'photo-1582722872445-44dc5f7e3c8f'],
];

$catMap = [];
$stmt   = $pdo->query('SELECT id, slug FROM categories');
foreach ($stmt->fetchAll() as $row) {
    $catMap[$row['slug']] = (int)$row['id'];
}

$seedAll    = !empty($_POST['seed_all']);
$targetName = $_POST['product_name'] ?? null;

$seeded  = 0;
$skipped = 0;

$uploadDir = __DIR__ . '/../../uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

foreach ($sampleProducts as $sp) {
    if (!$seedAll && $targetName !== $sp['name']) continue;
    if ($prodModel->existsByNameAndFarmer($sp['name'], $farmerId)) {
        $skipped++;
        continue;
    }

    // Try to download image from Unsplash
    $imagePath = null;
    $unsplashUrl = 'https://images.unsplash.com/' . $sp['unsplash'] . '?w=600&q=75&fm=jpg&auto=format';

    $context = stream_context_create([
        'http' => [
            'timeout'       => 8,
            'ignore_errors' => true,
            'header'        => "User-Agent: AgriShield/1.0\r\n",
        ]
    ]);

    $imageData = @file_get_contents($unsplashUrl, false, $context);
    if ($imageData !== false && strlen($imageData) > 5000) {
        $filename  = 'sample_' . bin2hex(random_bytes(6)) . '.jpg';
        $dest      = $uploadDir . $filename;
        if (file_put_contents($dest, $imageData) !== false) {
            $imagePath = 'products/' . $filename;
        }
    }

    $prodModel->create([
        'farmer_id'      => $farmerId,
        'category_id'    => $catMap[$sp['slug']] ?? null,
        'name'           => $sp['name'],
        'description'    => $sp['desc'],
        'unit_type'      => $sp['unit'],
        'price_per_unit' => $sp['price'],
        'stock_quantity' => $sp['stock'],
        'image'          => $imagePath,
        'is_available'   => 1,
    ]);
    $seeded++;
}

if ($seeded > 0) {
    $_SESSION['flash'] = ['type' => 'success', 'message' => "$seeded product(s) seeded successfully."];
} elseif ($skipped > 0) {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'All selected products already exist.'];
} else {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Nothing to seed.'];
}

redirect('/farmer/sample-products.php');
exit;
