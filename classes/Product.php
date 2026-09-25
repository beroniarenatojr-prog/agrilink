<?php

class Product
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetch a single product with category name and farmer name.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, c.name AS category_name, u.name AS farmer_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.farmer_id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['additional_images'] = $row['additional_images']
            ? json_decode($row['additional_images'], true)
            : [];
        return $row;
    }

    /**
     * Marketplace listing: available + in-stock, with filters.
     */
    public function getMarketplace(
        string $search     = '',
        int    $categoryId = 0,
        string $sort       = 'newest',
        ?int   $limit      = null,
        int    $offset     = 0
    ): array {
        $params = [];
        $where  = ['p.is_available = 1', 'p.stock_quantity > 0'];

        if ($search !== '') {
            $where[]  = 'p.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        if ($categoryId > 0) {
            $where[]  = 'p.category_id = ?';
            $params[] = $categoryId;
        }

        $orderBy = match($sort) {
            'price_asc'  => 'p.price_per_unit ASC',
            'price_desc' => 'p.price_per_unit DESC',
            'name_asc'   => 'p.name ASC',
            default      => 'p.created_at DESC',
        };

        $sql = 'SELECT p.id, p.name, p.price_per_unit, p.unit_type, p.image, p.stock_quantity,
                       p.farmer_id, u.name AS farmer_name, c.name AS category_name,
                       COALESCE(SUM(oi.quantity), 0) AS sold_count
                FROM products p
                LEFT JOIN users u ON u.id = p.farmer_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN order_items oi ON oi.product_id = p.id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY p.id
                ORDER BY ' . $orderBy;

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countMarketplace(string $search = '', int $categoryId = 0): int
    {
        $params = [];
        $where  = ['p.is_available = 1', 'p.stock_quantity > 0'];

        if ($search !== '') {
            $where[]  = 'p.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        if ($categoryId > 0) {
            $where[]  = 'p.category_id = ?';
            $params[] = $categoryId;
        }

        $sql = 'SELECT COUNT(DISTINCT p.id)
                FROM products p
                WHERE ' . implode(' AND ', $where);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * All products for a specific farmer.
     */
    public function getByFarmer(int $farmerId, ?int $limit = null, int $offset = 0): array
    {
        $sql = 'SELECT p.*, c.name AS category_name,
                    COALESCE(SUM(oi.quantity), 0) AS sold_count
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN order_items oi ON oi.product_id = p.id
             WHERE p.farmer_id = ?
             GROUP BY p.id
             ORDER BY p.created_at DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$farmerId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['additional_images'] = $row['additional_images']
                ? json_decode($row['additional_images'], true)
                : [];
        }
        return $rows;
    }

    public function countForFarmer(int $farmerId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE farmer_id = ?');
        $stmt->execute([$farmerId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Active listings for a seller page.
     */
    public function getByFarmerPublic(int $farmerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.price_per_unit, p.unit_type, p.image, p.stock_quantity
             FROM products p
             WHERE p.farmer_id = ? AND p.is_available = 1 AND p.stock_quantity > 0
             ORDER BY p.created_at DESC'
        );
        $stmt->execute([$farmerId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (farmer_id, category_id, name, description, unit_type,
                                   price_per_unit, stock_quantity, image, additional_images, is_available)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['farmer_id'],
            $data['category_id'] ?: null,
            $data['name'],
            $data['description'] ?? null,
            $data['unit_type'],
            $data['price_per_unit'],
            $data['stock_quantity'],
            $data['image'] ?? null,
            isset($data['additional_images']) ? json_encode($data['additional_images']) : null,
            $data['is_available'] ?? 1,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET category_id=?, name=?, description=?, unit_type=?,
                                  price_per_unit=?, stock_quantity=?, image=?, additional_images=?,
                                  is_available=?
             WHERE id=? AND farmer_id=?'
        );
        $stmt->execute([
            $data['category_id'] ?: null,
            $data['name'],
            $data['description'] ?? null,
            $data['unit_type'],
            $data['price_per_unit'],
            $data['stock_quantity'],
            $data['image'] ?? null,
            isset($data['additional_images']) ? json_encode($data['additional_images']) : null,
            $data['is_available'] ?? 1,
            $id,
            $data['farmer_id'],
        ]);
    }

    public function delete(int $id, int $farmerId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = ? AND farmer_id = ?');
        $stmt->execute([$id, $farmerId]);
    }

    public function countByFarmer(int $farmerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(is_available = 1 AND stock_quantity > 0) AS active,
                SUM(stock_quantity = 0) AS out_of_stock,
                SUM(stock_quantity > 0 AND stock_quantity <= 5) AS low_stock
             FROM products WHERE farmer_id = ?'
        );
        $stmt->execute([$farmerId]);
        return $stmt->fetch();
    }

    public function getTotalCount(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    public function getPlatformInventoryStats(): array
    {
        $row = $this->pdo->query(
            'SELECT
                COUNT(*) AS total,
                COALESCE(SUM(is_available = 1 AND stock_quantity > 0), 0) AS active,
                COALESCE(SUM(stock_quantity = 0), 0) AS out_of_stock,
                COALESCE(SUM(stock_quantity > 0 AND stock_quantity <= 5), 0) AS low_stock
             FROM products'
        )->fetch();

        return $row ?: ['total' => 0, 'active' => 0, 'out_of_stock' => 0, 'low_stock' => 0];
    }

    /**
     * Deduct stock; mark unavailable if reaches 0.
     */
    public function deductStock(int $productId, int $qty): void
    {
        $this->pdo->prepare(
            'UPDATE products
             SET stock_quantity = GREATEST(0, stock_quantity - ?),
                 is_available   = IF(stock_quantity - ? <= 0, 0, is_available)
             WHERE id = ?'
        )->execute([$qty, $qty, $productId]);
    }

    /**
     * Restore stock on cancellation.
     */
    public function restoreStock(int $productId, int $qty): void
    {
        $this->pdo->prepare(
            'UPDATE products SET stock_quantity = stock_quantity + ?, is_available = 1 WHERE id = ?'
        )->execute([$qty, $productId]);
    }

    public function existsByNameAndFarmer(string $name, int $farmerId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM products WHERE name = ? AND farmer_id = ? LIMIT 1');
        $stmt->execute([$name, $farmerId]);
        return (bool)$stmt->fetch();
    }
}
