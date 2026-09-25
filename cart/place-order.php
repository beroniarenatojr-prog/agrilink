<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Geocoder.php';

requireRole('buyer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cart/checkout.php');
    exit;
}

csrf_verify();

$user      = currentUser();
$isBuyNow  = !empty($_POST['is_buynow']);

if ($isBuyNow) {
    $items = $_SESSION['buynow_items'] ?? [];
} else {
    $cart  = new Cart();
    $items = $cart->getItems();
}

if (empty($items)) {
    redirect('/cart/index.php');
    exit;
}

$deliveryPhone   = trim($_POST['delivery_phone'] ?? '');
$addressFields   = addressFieldsFromPost('delivery_');
$paymentMethod   = in_array($_POST['payment_method'] ?? '', ['cod','meetup']) ? $_POST['payment_method'] : 'cod';

$errors = validatePhoneAndAddress($deliveryPhone, $addressFields, 'delivery_phone');
if (!empty($errors)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' ', array_values($errors))];
    redirect('/cart/checkout.php');
    exit;
}

$deliveryAddress = formatPhilippineAddress($addressFields);

$coords = Geocoder::geocodeFromFields($addressFields, $deliveryAddress);
$geocodeLimited = $coords === null;

$total = 0.0;
$orderItems = [];
foreach ($items as $item) {
    $subtotal     = round($item['price'] * $item['quantity'], 2);
    $total       += $subtotal;
    $orderItems[] = [
        'product_id' => $item['product_id'],
        'farmer_id'  => $item['farmer_id'],
        'name'       => $item['name'],
        'price'      => $item['price'],
        'quantity'   => $item['quantity'],
    ];
}

try {
    $pdo        = Database::getInstance();
    $orderModel = new Order($pdo);

    $orderId = $orderModel->placeOrder(
        (int)$user['id'],
        [
            'delivery_phone'      => $deliveryPhone,
            'delivery_address'    => $deliveryAddress,
            'delivery_province'   => $addressFields['province'],
            'delivery_city'       => $addressFields['city'],
            'delivery_barangay'   => $addressFields['barangay'] !== '' ? $addressFields['barangay'] : null,
            'delivery_street'     => $addressFields['street'] !== '' ? $addressFields['street'] : null,
            'delivery_lat'        => $coords !== null ? $coords['lat'] : null,
            'delivery_lng'        => $coords !== null ? $coords['lng'] : null,
            'payment_method'      => $paymentMethod,
            'total_amount'        => $total,
        ],
        $orderItems
    );

    if (!$isBuyNow) {
        $cart->clear();
    } else {
        unset($_SESSION['buynow_items']);
    }

    if ($geocodeLimited) {
        $_SESSION['flash'] = [
            'type'    => 'info',
            'message' => 'Order placed. Map tracking may be limited until the address can be located on the map.',
        ];
    } else {
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order placed successfully!'];
    }
    redirect('/buyer/orders/view.php?id=' . $orderId);
    exit;
} catch (Throwable $e) {
    error_log('Place order error: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to place order. Please try again.'];
    redirect('/cart/checkout.php');
    exit;
}
