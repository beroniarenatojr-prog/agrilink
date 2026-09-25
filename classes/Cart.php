<?php

/**
 * Session-based shopping cart.
 * Cart is stored in $_SESSION['cart'] as an array keyed by product_id.
 */
class Cart
{
    public function __construct()
    {
        $this->ensureCart();
    }

    private function ensureCart(): void
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    /** @return array<string, array> */
    private function &cart(): array
    {
        $this->ensureCart();
        return $_SESSION['cart'];
    }

    public function getItems(): array
    {
        return $this->cart();
    }

    public function count(): int
    {
        $total = 0;
        foreach ($this->cart() as $item) {
            $total += (int)$item['quantity'];
        }
        return $total;
    }

    public function total(): float
    {
        $sum = 0.0;
        foreach ($this->cart() as $item) {
            $sum += $item['price'] * $item['quantity'];
        }
        return $sum;
    }

    public function isEmpty(): bool
    {
        return empty($this->cart());
    }

    /**
     * Add or increment a product.
     * Returns true on success, false if stock would be exceeded.
     */
    public function add(
        int    $productId,
        string $name,
        float  $price,
        int    $quantity,
        string $unitType,
        int    $farmerId,
        ?string $image,
        int    $maxStock
    ): bool {
        $cart = &$this->cart();
        $key  = (string)$productId;

        $existingQty = isset($cart[$key]) ? (int)$cart[$key]['quantity'] : 0;
        $newQty      = $existingQty + $quantity;

        if ($newQty > $maxStock) {
            return false;
        }

        if (isset($cart[$key])) {
            $cart[$key]['quantity']  = $newQty;
            $cart[$key]['max_stock'] = $maxStock;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'name'       => $name,
                'price'      => $price,
                'quantity'   => $newQty,
                'unit_type'  => $unitType,
                'farmer_id'  => $farmerId,
                'image'      => $image,
                'max_stock'  => $maxStock,
            ];
        }

        return true;
    }

    /**
     * Set quantity for an item; remove if qty <= 0.
     */
    public function update(int $productId, int $quantity): bool
    {
        $cart = &$this->cart();
        $key  = (string)$productId;

        if (!isset($cart[$key])) {
            return false;
        }

        if ($quantity <= 0) {
            $this->remove($productId);
            return true;
        }

        if ($quantity > $cart[$key]['max_stock']) {
            return false;
        }

        $cart[$key]['quantity'] = $quantity;
        return true;
    }

    public function remove(int $productId): void
    {
        unset($this->cart()[(string)$productId]);
    }

    public function clear(): void
    {
        $_SESSION['cart'] = [];
    }

    /**
     * Build a single-item "buy now" cart (does not persist to session).
     */
    public static function buyNow(
        int    $productId,
        string $name,
        float  $price,
        int    $quantity,
        string $unitType,
        int    $farmerId,
        ?string $image,
        int    $maxStock
    ): array {
        return [
            (string)$productId => [
                'product_id' => $productId,
                'name'       => $name,
                'price'      => $price,
                'quantity'   => $quantity,
                'unit_type'  => $unitType,
                'farmer_id'  => $farmerId,
                'image'      => $image,
                'max_stock'  => $maxStock,
            ]
        ];
    }
}
