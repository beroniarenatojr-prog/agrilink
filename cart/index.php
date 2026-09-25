<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Cart.php';

requireRole('buyer');

$cart      = new Cart();
$items     = $cart->getItems();
$total     = $cart->total();
$itemCount = array_sum(array_column($items, 'quantity'));

$pageTitle = 'Cart';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($items)): ?>
<nav class="checkout-steps" aria-label="Checkout progress">
    <span class="checkout-step checkout-step--active">
        <i class="ti ti-shopping-cart"></i> Cart
    </span>
    <span class="checkout-step__sep" aria-hidden="true"><i class="ti ti-chevron-right"></i></span>
    <a href="<?= url('/cart/checkout.php') ?>" class="checkout-step">
        <i class="ti ti-credit-card"></i> Checkout
    </a>
</nav>
<?php endif; ?>

<div class="cart-page-header page-header">
    <div class="cart-page-header__intro">
        <div class="cart-page-header__icon" aria-hidden="true">
            <i class="ti ti-shopping-cart"></i>
        </div>
        <div>
            <h1 class="page-title mb-0">Your Cart</h1>
            <p class="page-subtitle mb-0">
                <?php if ($itemCount > 0): ?>
                    <?= $itemCount ?> <?= $itemCount === 1 ? 'item' : 'items' ?> — review before checkout
                <?php else: ?>
                    Your cart is empty — start shopping in the marketplace
                <?php endif; ?>
            </p>
        </div>
    </div>
    <?php if (!empty($items)): ?>
        <a href="<?= url('/marketplace/index.php') ?>" class="btn btn-agri-outline">
            <i class="ti ti-arrow-left me-1"></i> Continue Shopping
        </a>
    <?php endif; ?>
</div>

<?php if (empty($items)): ?>
    <div class="cart-empty">
        <div class="cart-empty__icon" aria-hidden="true">
            <i class="ti ti-shopping-cart-off"></i>
        </div>
        <h2>Your cart is empty</h2>
        <p>Browse the marketplace and add fresh produce to your cart.</p>
        <a href="<?= url('/marketplace/index.php') ?>" class="btn btn-agri">Browse Marketplace</a>
    </div>
<?php else: ?>

<div class="row g-4 align-items-start">
    <div class="col-12 col-lg-7">
        <div class="checkout-form-card">
            <div class="checkout-form-card__header">
                <i class="ti ti-shopping-bag"></i>
                <span>Cart Items</span>
                <span class="cart-items-card__count ms-auto"><?= count($items) ?> products</span>
            </div>
            <div class="checkout-form-card__body checkout-form-card__body--flush">
                <?php foreach ($items as $item): ?>
                <div class="cart-item">
                    <?php if ($item['image']): ?>
                        <img class="cart-item-img"
                             src="<?= url('/media.php?path=' . urlencode($item['image'])) ?>"
                             alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <div class="cart-item-img cart-item-img--empty" aria-hidden="true">
                            <i class="ti ti-photo-off"></i>
                        </div>
                    <?php endif; ?>

                    <div class="cart-item-body">
                        <div class="cart-item-name"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="cart-item-meta"><?= peso((float) $item['price']) ?> / <?= htmlspecialchars($item['unit_type'], ENT_QUOTES, 'UTF-8') ?></div>

                        <form method="post" action="<?= url('/cart/update.php') ?>" class="mt-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <div class="qty-stepper">
                                <button type="button" onclick="adjustQty(this, -1)" aria-label="Decrease quantity">−</button>
                                <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>"
                                       min="1" max="<?= (int) $item['max_stock'] ?>"
                                       onchange="this.form.submit()" aria-label="Quantity">
                                <button type="button" onclick="adjustQty(this, 1)" aria-label="Increase quantity">+</button>
                            </div>
                        </form>
                    </div>

                    <div class="cart-item-actions">
                        <div class="cart-item-price"><?= peso((float) $item['price'] * $item['quantity']) ?></div>
                        <form method="post" action="<?= url('/cart/remove.php') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger cart-item-remove" title="Remove item">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="cart-items-card__footer">
                    <form method="post" action="<?= url('/cart/clear.php') ?>" data-vex-confirm="Clear all items from your cart?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Clear cart</button>
                    </form>
                </div>
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

            <a href="<?= url('/cart/checkout.php') ?>" class="btn btn-agri w-100 mt-3">
                Proceed to Checkout <i class="ti ti-arrow-right ms-1"></i>
            </a>
            <p class="cart-summary__note mb-0">
                <i class="ti ti-lock"></i> Secure checkout — pay on delivery
            </p>
        </div>
    </div>
</div>

<script>
function adjustQty(btn, delta) {
    const input = btn.parentElement.querySelector('input[type=number]');
    const max   = parseInt(input.max, 10);
    const min   = parseInt(input.min, 10);
    let val = parseInt(input.value, 10) + delta;
    if (val < min) val = min;
    if (val > max) val = max;
    input.value = val;
    input.form.submit();
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
