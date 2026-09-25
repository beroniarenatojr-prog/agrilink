<?php

class MarketPrice
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Latest admin reference price where effective_date <= today.
     */
    public function getReferencePrice(int $categoryId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT mp.*, u.name AS admin_name, c.name AS category_name
             FROM market_prices mp
             JOIN users u ON u.id = mp.admin_id
             JOIN categories c ON c.id = mp.category_id
             WHERE mp.category_id = ? AND mp.effective_date <= CURDATE()
             ORDER BY mp.effective_date DESC, mp.id DESC
             LIMIT 1"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * AVG, MIN, MAX, COUNT from active in-stock products for a category.
     */
    public function getPlatformBenchmark(int $categoryId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                AVG(price_per_unit) AS avg_price,
                MIN(price_per_unit) AS min_price,
                MAX(price_per_unit) AS max_price,
                COUNT(*)           AS listing_count
             FROM products
             WHERE category_id = ? AND is_available = 1 AND stock_quantity > 0"
        );
        $stmt->execute([$categoryId]);
        $row = $stmt->fetch();
        if (!$row || $row['listing_count'] == 0) return null;

        return [
            'avg_price'     => (float)$row['avg_price'],
            'min_price'     => (float)$row['min_price'],
            'max_price'     => (float)$row['max_price'],
            'listing_count' => (int)$row['listing_count'],
        ];
    }

    /**
     * Merged: reference price + platform benchmark + source flag.
     */
    public function getCategoryBenchmark(int $categoryId): array
    {
        $ref      = $this->getReferencePrice($categoryId);
        $platform = $this->getPlatformBenchmark($categoryId);

        return [
            'reference' => $ref,
            'platform'  => $platform,
            'source'    => $ref ? 'reference' : ($platform ? 'platform' : null),
        ];
    }

    /**
     * Price comparison: within_range / above_average / below_average (±10%).
     */
    public function comparePrice(float $price, int $categoryId): ?string
    {
        $ref      = $this->getReferencePrice($categoryId);
        $platform = $this->getPlatformBenchmark($categoryId);

        $baseline = $ref
            ? (float)$ref['reference_price']
            : ($platform ? $platform['avg_price'] : null);

        if ($baseline === null) return null;

        $diff = ($price - $baseline) / $baseline;

        if ($diff > 0.10)  return 'above_average';
        if ($diff < -0.10) return 'below_average';
        return 'within_range';
    }

    /**
     * All categories with reference + platform stats.
     */
    public function categoryPriceOverview(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM categories ORDER BY name');
        $categories = $stmt->fetchAll();

        $rows = [];
        foreach ($categories as $cat) {
            $ref      = $this->getReferencePrice($cat['id']);
            $platform = $this->getPlatformBenchmark($cat['id']);

            $rows[] = [
                'category_id'   => $cat['id'],
                'category_name' => $cat['name'],
                'reference'     => $ref,
                'platform'      => $platform,
            ];
        }
        return $rows;
    }

    /**
     * Reference price history for a category (for chart).
     */
    public function referencePriceHistory(int $categoryId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT effective_date, reference_price, unit_type, notes
             FROM market_prices
             WHERE category_id = ? AND effective_date <= CURDATE()
             ORDER BY effective_date ASC"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    /* --------------- Admin CRUD --------------- */

    public function getAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT mp.*, c.name AS category_name, u.name AS admin_name
             FROM market_prices mp
             JOIN categories c ON c.id = mp.category_id
             JOIN users u ON u.id = mp.admin_id
             ORDER BY mp.effective_date DESC, mp.id DESC"
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT mp.*, c.name AS category_name
             FROM market_prices mp
             JOIN categories c ON c.id = mp.category_id
             WHERE mp.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO market_prices (category_id, unit_type, reference_price, effective_date, notes, admin_id)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['unit_type'],
            $data['reference_price'],
            $data['effective_date'],
            $data['notes'] ?? null,
            $data['admin_id'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE market_prices SET category_id=?, unit_type=?, reference_price=?,
                                      effective_date=?, notes=? WHERE id=?'
        );
        $stmt->execute([
            $data['category_id'],
            $data['unit_type'],
            $data['reference_price'],
            $data['effective_date'],
            $data['notes'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM market_prices WHERE id = ?')->execute([$id]);
    }
}
