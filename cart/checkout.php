<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/Product.php';

requireRole('buyer');

$pdo   = Database::getInstance();
$user  = currentUser();

$stmt = $pdo->prepare(
    'SELECT phone, address, address_province, address_city, address_barangay, address_street
     FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
$deliveryValues = addressFieldsFromRow($profile ?: [], 'address');

$isBuyNow = !empty($_POST['buynow']) || !empty($_GET['buynow']);
$buyNowItems = [];

if ($isBuyNow && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity  = (int)($_POST['quantity']   ?? 1);

    $prodModel = new Product($pdo);
    $product   = $prodModel->findById($productId);

    if (!$product || !$product['is_available'] || $product['stock_quantity'] <= 0) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Product is no longer available.'];
        redirect('/marketplace/index.php');
        exit;
    }
    $quantity = min($quantity, $product['stock_quantity']);

    $buyNowItems = Cart::buyNow(
        $productId,
        $product['name'],
        (float)$product['price_per_unit'],
        $quantity,
        $product['unit_type'],
        (int)$product['farmer_id'],
        $product['image'],
        (int)$product['stock_quantity']
    );

    $_SESSION['buynow_items'] = $buyNowItems;
}

$cart  = new Cart();
$items = $isBuyNow ? ($buyNowItems ?: ($_SESSION['buynow_items'] ?? [])) : $cart->getItems();

if (empty($items)) {
    redirect('/cart/index.php');
    exit;
}

$total = 0.0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

$itemCount = array_sum(array_column($items, 'quantity'));

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
?>

<nav class="checkout-steps" aria-label="Checkout progress">
    <?php if (!$isBuyNow): ?>
        <a href="<?= url('/cart/index.php') ?>" class="checkout-step">
            <i class="ti ti-shopping-cart"></i> Cart
        </a>
        <span class="checkout-step__sep" aria-hidden="true"><i class="ti ti-chevron-right"></i></span>
    <?php endif; ?>
    <span class="checkout-step checkout-step--active">
        <i class="ti ti-credit-card"></i> Checkout
    </span>
</nav>

<div class="cart-page-header page-header">
    <div class="cart-page-header__intro">
        <div class="cart-page-header__icon" aria-hidden="true">
            <i class="ti ti-truck-delivery"></i>
        </div>
        <div>
            <h1 class="page-title mb-0">Checkout</h1>
            <p class="page-subtitle mb-0">
                <?= $itemCount ?> <?= $itemCount === 1 ? 'item' : 'items' ?> — choose delivery and payment
            </p>
        </div>
    </div>
    <?php if (!$isBuyNow): ?>
        <a href="<?= url('/cart/index.php') ?>" class="btn btn-agri-outline">
            <i class="ti ti-arrow-left me-1"></i> Back to Cart
        </a>
    <?php endif; ?>
</div>

<div class="row g-4 align-items-start">
    <div class="col-12 col-lg-7">
        <div class="checkout-form-card">
            <div class="checkout-form-card__header">
                <i class="ti ti-map-pin"></i> Delivery Details
            </div>
            <div class="checkout-form-card__body">
                <form method="post" action="<?= url('/cart/place-order.php') ?>" id="checkoutForm" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="is_buynow" value="<?= $isBuyNow ? '1' : '0' ?>">

                    <div class="row g-3">
                        <?php
                        $prefix = 'delivery_';
                        $values = $deliveryValues;
                        $errors = [];
                        $showPhone = true;
                        $phoneValue = $profile['phone'] ?? '';
                        require __DIR__ . '/../includes/address_fields.php';
                        ?>

                        <div class="col-12">
                            <div class="checkout-section-label">Payment method</div>
                            <div class="payment-options">
                                <label class="payment-option is-selected" data-payment-option>
                                    <input type="radio" name="payment_method" id="payCOD" value="cod" checked>
                                    <div class="payment-option__icon" aria-hidden="true"><i class="ti ti-cash"></i></div>
                                    <div class="payment-option__title">Cash on Delivery</div>
                                    <div class="payment-option__desc">Pay when your order arrives</div>
                                </label>
                                <label class="payment-option" data-payment-option>
                                    <input type="radio" name="payment_method" id="payMeetup" value="meetup">
                                    <div class="payment-option__icon" aria-hidden="true"><i class="ti ti-users"></i></div>
                                    <div class="payment-option__title">Meetup</div>
                                    <div class="payment-option__desc">Arrange pickup with the seller</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="cart-summary">
            <div class="cart-summary__title">Order Summary</div>

            <?php foreach ($items as $item): ?>
                <div class="checkout-summary-item">
                    <?php if (!empty($item['image'])): ?>
                        <img class="checkout-summary-item__img"
                             src="<?= url('/media.php?path=' . urlencode($item['image'])) ?>"
                             alt="">
                    <?php else: ?>
                        <div class="checkout-summary-item__img checkout-summary-item__img--empty" aria-hidden="true">
                            <i class="ti ti-photo-off"></i>
                        </div>
                    <?php endif; ?>
                    <div class="checkout-summary-item__info">
                        <div class="checkout-summary-item__name"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="checkout-summary-item__qty">Qty: <?= (int) $item['quantity'] ?></div>
                    </div>
                    <div class="checkout-summary-item__price"><?= peso((float) $item['price'] * $item['quantity']) ?></div>
                </div>
            <?php endforeach; ?>

            <div class="order-summary-row order-summary-total mt-2">
                <span>Total</span>
                <span><?= peso($total) ?></span>
            </div>

            <button type="submit" form="checkoutForm" class="btn btn-agri w-100 mt-3">
                Place Order <i class="ti ti-check ms-1"></i>
            </button>
            <p class="cart-summary__note mb-0">
                <i class="ti ti-lock"></i> Your details are kept secure
            </p>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-payment-option]').forEach(function (option) {
    option.addEventListener('click', function () {
        document.querySelectorAll('[data-payment-option]').forEach(function (el) {
            el.classList.remove('is-selected');
        });
        option.classList.add('is-selected');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
