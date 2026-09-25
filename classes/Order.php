<?php

class Order
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
             FROM orders o
             JOIN users u ON u.id = o.buyer_id
             WHERE o.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Geocode and persist delivery coordinates when missing (structured address or legacy text).
     */
    public function ensureDeliveryCoordinates(int $orderId): ?array
    {
        $order = $this->findById($orderId);
        if (!$order) {
            return null;
        }

        if ($order['delivery_lat'] !== null && $order['delivery_lng'] !== null) {
            return [
                'lat' => (float) $order['delivery_lat'],
                'lng' => (float) $order['delivery_lng'],
            ];
        }

        require_once __DIR__ . '/Geocoder.php';

        if (!function_exists('addressFieldsFromRow')) {
            require_once __DIR__ . '/../includes/helpers.php';
        }

        $fields = addressFieldsFromRow($order, 'delivery');
        $coords = Geocoder::geocodeFromFields($fields, $order['delivery_address'] ?? null);

        if ($coords === null) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE orders SET delivery_lat = ?, delivery_lng = ? WHERE id = ?'
        );
        $stmt->execute([$coords['lat'], $coords['lng'], $orderId]);

        return $coords;
    }

    public function getItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT oi.*, u.name AS farmer_name
             FROM order_items oi
             JOIN users u ON u.id = oi.farmer_id
             WHERE oi.order_id = ?
             ORDER BY oi.id'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Orders visible to a buyer.
     */
    public function getByBuyer(int $buyerId, string $status = '', ?int $limit = null, int $offset = 0): array
    {
        $params = [$buyerId];
        $where = 'o.buyer_id = ?';

        if ($status !== '') {
            $where .= ' AND o.logistics_status = ?';
            $params[] = $status;
        }

        $sql = "SELECT o.id, o.logistics_status, o.payment_status, o.payment_method,
                    o.total_amount, o.created_at,
                    COUNT(oi.id) AS item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE $where
             GROUP BY o.id
             ORDER BY o.created_at DESC";

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countByBuyer(int $buyerId, string $status = ''): int
    {
        $params = [$buyerId];
        $where = 'buyer_id = ?';

        if ($status !== '') {
            $where .= ' AND logistics_status = ?';
            $params[] = $status;
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Orders that contain items from a specific farmer.
     */
    public function getByFarmer(int $farmerId, string $status = '', ?int $limit = null, int $offset = 0): array
    {
        $extraWhere = '';
        $params = [$farmerId, $farmerId, $farmerId];

        if ($status !== '') {
            $extraWhere = ' AND o.logistics_status = ?';
            $params[] = $status;
        }

        $sql = "SELECT DISTINCT o.id, o.logistics_status, o.payment_status,
                    o.total_amount, o.created_at,
                    u.name AS buyer_name,
                    lu.name AS assignee_name,
                    (SELECT SUM(oi2.subtotal) FROM order_items oi2
                     WHERE oi2.order_id = o.id AND oi2.farmer_id = ?) AS farmer_subtotal
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id AND oi.farmer_id = ?
             JOIN users u ON u.id = o.buyer_id
             LEFT JOIN order_logistics_assignments ola ON ola.order_id = o.id AND ola.farmer_id = ?
             LEFT JOIN users lu ON lu.id = ola.logistics_user_id
             WHERE 1=1 $extraWhere
             ORDER BY o.created_at DESC";

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countByFarmer(int $farmerId, string $status = ''): int
    {
        $extraWhere = '';
        $params = [$farmerId];

        if ($status !== '') {
            $extraWhere = ' AND o.logistics_status = ?';
            $params[] = $status;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT o.id)
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id AND oi.farmer_id = ?
             WHERE 1=1 $extraWhere"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function countAll(string $status = '', string $search = ''): int
    {
        $params = [];
        $where = ['1=1'];

        if ($status !== '') {
            $where[] = 'o.logistics_status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR o.id = ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = (int) $search;
        }

        $sql = 'SELECT COUNT(*)
                FROM orders o
                JOIN users u ON u.id = o.buyer_id
                WHERE ' . implode(' AND ', $where);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * All orders for admin list.
     */
    public function getAll(string $status = '', string $search = '', ?int $limit = null, int $offset = 0): array
    {
        $params = [];
        $where = ['1=1'];

        if ($status !== '') {
            $where[] = 'o.logistics_status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR o.id = ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = (int) $search;
        }

        $sql = 'SELECT o.id, o.logistics_status, o.payment_status, o.payment_method,
                       o.total_amount, o.created_at, u.name AS buyer_name
                FROM orders o
                JOIN users u ON u.id = o.buyer_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY o.created_at DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        } else {
            $sql .= ' LIMIT 500';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create order + items inside a transaction.
     * Returns the new order ID.
     */
    public function placeOrder(int $buyerId, array $orderData, array $items): int
    {
        $this->pdo->beginTransaction();

        try {
            $lat = $orderData['delivery_lat'] ?? null;
            $lng = $orderData['delivery_lng'] ?? null;

            $stmt = $this->pdo->prepare(
                'INSERT INTO orders (buyer_id, delivery_phone, delivery_address,
                                     delivery_province, delivery_city, delivery_barangay, delivery_street,
                                     delivery_lat, delivery_lng, payment_method, total_amount)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $buyerId,
                $orderData['delivery_phone'],
                $orderData['delivery_address'],
                $orderData['delivery_province'] ?? null,
                $orderData['delivery_city'] ?? null,
                $orderData['delivery_barangay'] ?? null,
                $orderData['delivery_street'] ?? null,
                $lat,
                $lng,
                $orderData['payment_method'],
                $orderData['total_amount'],
            ]);
            $orderId = (int) $this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, farmer_id, product_name_snapshot,
                                          price_snapshot, quantity, subtotal)
                 VALUES (?,?,?,?,?,?,?)'
            );

            $productStmt = $this->pdo->prepare(
                'UPDATE products
                 SET stock_quantity = GREATEST(0, stock_quantity - ?),
                     is_available   = IF(stock_quantity - ? <= 0, 0, is_available)
                 WHERE id = ?'
            );

            foreach ($items as $item) {
                $subtotal = round($item['price'] * $item['quantity'], 2);
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['farmer_id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $subtotal,
                ]);
                $productStmt->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
            }

            $this->pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $orderId, string $newStatus, array $extra = []): void
    {
        $sets = ['logistics_status = ?', 'updated_at = NOW()'];
        $params = [$newStatus];

        if ($newStatus === 'in_transit') {
            $sets[] = 'in_transit_at = NOW()';
        }

        if ($newStatus === 'delivered') {
            $sets[] = 'payment_status = ?';
            $params[] = 'paid';
            $sets[] = 'completed_at = NOW()';
        }

        if ($newStatus === 'cancelled') {
            $sets[] = 'cancelled_at = NOW()';
            if (!empty($extra['cancellation_reason'])) {
                $sets[] = 'cancellation_reason = ?';
                $params[] = $extra['cancellation_reason'];
            }
        }

        $params[] = $orderId;
        $sql = 'UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $this->pdo->prepare($sql)->execute($params);
    }

    public function assignLogistics(int $orderId, int $farmerId, int $logisticsUserId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO order_logistics_assignments (order_id, farmer_id, logistics_user_id, assigned_at)
             VALUES (?,?,?,NOW())
             ON DUPLICATE KEY UPDATE logistics_user_id = VALUES(logistics_user_id), assigned_at = NOW()'
        );
        $stmt->execute([$orderId, $farmerId, $logisticsUserId]);
    }

    public function getLogisticsAssignment(int $orderId, int $farmerId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ola.order_id,
                    ola.farmer_id,
                    ola.logistics_user_id,
                    ola.assigned_at,
                    u.name AS logistics_name,
                    u.email AS logistics_email,
                    u.phone AS logistics_phone
             FROM order_logistics_assignments ola
             JOIN users u ON u.id = ola.logistics_user_id
             WHERE ola.order_id = ? AND ola.farmer_id = ?
             LIMIT 1"
        );
        $stmt->execute([$orderId, $farmerId]);
        return $stmt->fetch() ?: null;
    }

    public function isAssignedToLogisticsUser(int $orderId, int $logisticsUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM order_logistics_assignments
             WHERE order_id = ? AND logistics_user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$orderId, $logisticsUserId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Orders assigned to a logistics user.
     *
     * @param string $status       Single logistics_status filter, or empty for all
     * @param array  $statuses     Multiple statuses (e.g. processing + in_transit)
     */
    public function getByLogisticsUser(int $logisticsUserId, string $status = '', array $statuses = [], ?int $limit = null, int $offset = 0): array
    {
        $params = [$logisticsUserId];
        $where = 'ola.logistics_user_id = ?';

        if ($status !== '') {
            $where .= ' AND o.logistics_status = ?';
            $params[] = $status;
        } elseif (!empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $where .= " AND o.logistics_status IN ($placeholders)";
            $params = array_merge($params, $statuses);
        } else {
            // Default: hide already delivered orders from logistics assigned list
            $where .= " AND o.logistics_status <> 'delivered'";
        }

        $sql = "SELECT o.id, o.logistics_status, o.payment_status, o.payment_method,
                    o.total_amount, o.created_at, o.delivery_phone, o.delivery_address,
                    u.name AS buyer_name,
                    f.name AS farmer_name,
                    ola.assigned_at
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             JOIN users u ON u.id = o.buyer_id
             JOIN users f ON f.id = ola.farmer_id
             WHERE $where
             ORDER BY ola.assigned_at DESC";

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        } else {
            $sql .= ' LIMIT 500';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countByLogisticsUser(int $logisticsUserId, string $status = '', array $statuses = []): int
    {
        $params = [$logisticsUserId];
        $where = 'ola.logistics_user_id = ?';

        if ($status !== '') {
            $where .= ' AND o.logistics_status = ?';
            $params[] = $status;
        } elseif (!empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $where .= " AND o.logistics_status IN ($placeholders)";
            $params = array_merge($params, $statuses);
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             WHERE $where"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function countAssignedToLogistics(int $logisticsUserId, ?string $status = null): int
    {
        $params = [$logisticsUserId];
        $where = 'ola.logistics_user_id = ?';

        if ($status !== null) {
            $where .= ' AND o.logistics_status = ?';
            $params[] = $status;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             WHERE $where"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function countActiveDeliveriesForLogistics(int $logisticsUserId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             WHERE ola.logistics_user_id = ?
               AND o.logistics_status IN ('processing','in_transit')"
        );
        $stmt->execute([$logisticsUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function countDeliveriesTodayForLogistics(int $logisticsUserId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             WHERE ola.logistics_user_id = ?
               AND (DATE(ola.assigned_at) = CURDATE() OR DATE(o.in_transit_at) = CURDATE())"
        );
        $stmt->execute([$logisticsUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function getDeliverySuccessRateForLogistics(int $logisticsUserId): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                SUM(o.logistics_status = 'delivered') AS delivered,
                SUM(o.logistics_status IN ('delivered','cancelled')) AS completed
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             WHERE ola.logistics_user_id = ?"
        );
        $stmt->execute([$logisticsUserId]);
        $row = $stmt->fetch();

        $completed = (int) ($row['completed'] ?? 0);
        if ($completed === 0) {
            return null;
        }

        $delivered = (int) ($row['delivered'] ?? 0);
        return round(($delivered / $completed) * 100) . '%';
    }

    public function getRecentAssignedForLogistics(int $logisticsUserId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.id, o.logistics_status, ola.assigned_at,
                    u.name AS buyer_name,
                    f.name AS farmer_name
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             JOIN users u ON u.id = o.buyer_id
             JOIN users f ON f.id = ola.farmer_id
             WHERE ola.logistics_user_id = ?
             ORDER BY ola.assigned_at DESC
             LIMIT ?"
        );
        $stmt->execute([$logisticsUserId, $limit]);
        return $stmt->fetchAll();
    }

    public function countByStatusForLogistics(int $logisticsUserId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.logistics_status, COUNT(*) AS cnt
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             WHERE ola.logistics_user_id = ?
             GROUP BY o.logistics_status"
        );
        $stmt->execute([$logisticsUserId]);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['logistics_status']] = (int) $row['cnt'];
        }
        return $out;
    }

    public function getLogisticsPeriodSummary(int $logisticsUserId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(CASE WHEN ola.assigned_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 END) AS assigned_this_month,
                COUNT(CASE WHEN o.logistics_status = 'delivered'
                            AND o.completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 END) AS delivered_this_month,
                COUNT(CASE WHEN o.logistics_status = 'delivered'
                            AND o.completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) AS delivered_this_week
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             WHERE ola.logistics_user_id = ?"
        );
        $stmt->execute([$logisticsUserId]);
        return $stmt->fetch() ?: [];
    }

    public function getLogisticsDailyAssignmentCount(int $logisticsUserId, int $days = 14): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(ola.assigned_at) AS day, COUNT(*) AS cnt
             FROM order_logistics_assignments ola
             WHERE ola.logistics_user_id = ?
               AND ola.assigned_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(ola.assigned_at)
             ORDER BY day"
        );
        $stmt->execute([$logisticsUserId, $days]);
        return $stmt->fetchAll();
    }

    public function restoreStockForOrder(int $orderId): void
    {
        $items = $this->getItems($orderId);
        foreach ($items as $item) {
            if ($item['product_id']) {
                $this->pdo->prepare(
                    'UPDATE products SET stock_quantity = stock_quantity + ?, is_available = 1 WHERE id = ?'
                )->execute([$item['quantity'], $item['product_id']]);
            }
        }
    }

    /* --------------- Stats / Analytics --------------- */

    public function getTotalRevenue(): float
    {
        return (float) $this->pdo->query(
            "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE logistics_status = 'delivered'"
        )->fetchColumn();
    }

    public function countByStatus(): array
    {
        $stmt = $this->pdo->query('SELECT logistics_status, COUNT(*) AS cnt FROM orders GROUP BY logistics_status');
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['logistics_status']] = (int) $row['cnt'];
        }
        return $out;
    }

    public function getTotalCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public function getRecentForAdmin(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.id, o.logistics_status, o.total_amount, o.created_at, u.name AS buyer_name
             FROM orders o
             JOIN users u ON u.id = o.buyer_id
             ORDER BY o.created_at DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getAdminDailyRevenue(int $days = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(completed_at) AS day, SUM(total_amount) AS revenue
             FROM orders
             WHERE logistics_status = 'delivered'
               AND completed_at IS NOT NULL
               AND completed_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(completed_at)
             ORDER BY day"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function getAdminDailyOrderCount(int $days = 14): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function getAdminTopProducts(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.name, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_revenue
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             JOIN orders o ON o.id = oi.order_id
             WHERE o.logistics_status = 'delivered'
             GROUP BY oi.product_id, p.name
             ORDER BY total_qty DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getAdminPeriodSummary(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                SUM(CASE WHEN created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 ELSE 0 END) AS orders_this_month,
                SUM(CASE WHEN created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
                          AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 ELSE 0 END) AS orders_last_month,
                COALESCE(SUM(CASE WHEN logistics_status = 'delivered'
                                  AND completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN total_amount ELSE 0 END), 0) AS revenue_this_month,
                COALESCE(SUM(CASE WHEN logistics_status = 'delivered'
                                  AND completed_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
                                  AND completed_at < DATE_FORMAT(NOW(), '%Y-%m-01') THEN total_amount ELSE 0 END), 0) AS revenue_last_month,
                SUM(CASE WHEN logistics_status = 'delivered'
                          AND completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 ELSE 0 END) AS delivered_this_month
             FROM orders"
        );
        return $stmt->fetch() ?: [];
    }

    /* --------------- Farmer analytics --------------- */

    public function getFarmerRevenue(int $farmerId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(oi.subtotal),0)
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.farmer_id = ? AND o.logistics_status = 'delivered'"
        );
        $stmt->execute([$farmerId]);
        return (float) $stmt->fetchColumn();
    }

    public function getFarmerCompletedCount(int $farmerId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT o.id)
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             WHERE oi.farmer_id = ? AND o.logistics_status = 'delivered'"
        );
        $stmt->execute([$farmerId]);
        return (int) $stmt->fetchColumn();
    }

    public function countByStatusForFarmer(int $farmerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.logistics_status, COUNT(DISTINCT o.id) AS cnt
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id AND oi.farmer_id = ?
             GROUP BY o.logistics_status"
        );
        $stmt->execute([$farmerId]);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['logistics_status']] = (int) $row['cnt'];
        }
        return $out;
    }

    public function getFarmerPeriodSummary(int $farmerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(DISTINCT CASE WHEN o.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN o.id END) AS orders_this_month,
                COUNT(DISTINCT CASE WHEN o.created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
                                      AND o.created_at < DATE_FORMAT(NOW(), '%Y-%m-01') THEN o.id END) AS orders_last_month,
                COALESCE(SUM(CASE WHEN o.logistics_status = 'delivered'
                                  AND o.completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN oi.subtotal ELSE 0 END), 0) AS revenue_this_month,
                COALESCE(SUM(CASE WHEN o.logistics_status = 'delivered'
                                  AND o.completed_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
                                  AND o.completed_at < DATE_FORMAT(NOW(), '%Y-%m-01') THEN oi.subtotal ELSE 0 END), 0) AS revenue_last_month,
                COUNT(DISTINCT CASE WHEN o.logistics_status = 'delivered'
                                    AND o.completed_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN o.id END) AS delivered_this_month
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.farmer_id = ?"
        );
        $stmt->execute([$farmerId]);
        return $stmt->fetch() ?: [];
    }

    public function getFarmerDailyOrderCount(int $farmerId, int $days = 14): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(o.created_at) AS day, COUNT(DISTINCT o.id) AS cnt
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id AND oi.farmer_id = ?
             WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(o.created_at)
             ORDER BY day"
        );
        $stmt->execute([$farmerId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Revenue per day for last N days (delivered orders).
     */
    public function getFarmerDailyRevenue(int $farmerId, int $days = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(o.completed_at) AS day, SUM(oi.subtotal) AS revenue
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.farmer_id = ?
               AND o.logistics_status = 'delivered'
               AND o.completed_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(o.completed_at)
             ORDER BY day"
        );
        $stmt->execute([$farmerId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Top N products by quantity sold.
     */
    public function getFarmerTopProducts(int $farmerId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT oi.product_name_snapshot AS name, SUM(oi.quantity) AS total_qty
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.farmer_id = ? AND o.logistics_status = 'delivered'
             GROUP BY oi.product_name_snapshot
             ORDER BY total_qty DESC
             LIMIT ?"
        );
        $stmt->execute([$farmerId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Demand forecast: top N products over last 90 days.
     */
    public function getFarmerDemandForecast(int $farmerId, int $limit = 3): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                oi.product_name_snapshot AS name,
                SUM(oi.quantity) AS total_qty,
                COUNT(DISTINCT WEEK(o.completed_at)) AS weeks_active
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.farmer_id = ?
               AND o.logistics_status = 'delivered'
               AND o.completed_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
             GROUP BY oi.product_name_snapshot
             ORDER BY total_qty DESC
             LIMIT ?"
        );
        $stmt->execute([$farmerId, $limit]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $weeksActive = max(1, (int) $row['weeks_active']);
            $weeklyAvg = round($row['total_qty'] / $weeksActive, 1);
            $suggestedStock = round($weeklyAvg * 1.2);

            // Month-over-month: compare last 30 vs prev 30 days
            $momStmt = $this->pdo->prepare(
                "SELECT
                    SUM(IF(o.completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY), oi.quantity, 0)) AS this_month,
                    SUM(IF(o.completed_at  < DATE_SUB(CURDATE(), INTERVAL 30 DAY), oi.quantity, 0)) AS prev_month
                 FROM order_items oi
                 JOIN orders o ON o.id = oi.order_id
                 WHERE oi.farmer_id = ?
                   AND oi.product_name_snapshot = ?
                   AND o.logistics_status = 'delivered'
                   AND o.completed_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)"
            );
            $momStmt->execute([$farmerId, $row['name']]);
            $mom = $momStmt->fetch();

            $prevMonth = (float) ($mom['prev_month'] ?? 0);
            $thisMonth = (float) ($mom['this_month'] ?? 0);
            $momPercent = $prevMonth > 0
                ? round((($thisMonth - $prevMonth) / $prevMonth) * 100, 1)
                : null;

            $row['weekly_avg'] = $weeklyAvg;
            $row['suggested_stock'] = $suggestedStock;
            $row['mom_percent'] = $momPercent;
        }

        return $rows;
    }

    public function getRecentForFarmer(int $farmerId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT o.id, o.logistics_status, o.total_amount, o.created_at,
                    u.name AS buyer_name
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id AND oi.farmer_id = ?
             JOIN users u ON u.id = o.buyer_id
             ORDER BY o.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$farmerId, $limit]);
        return $stmt->fetchAll();
    }

    public function getRecentSalesForFarmer(int $farmerId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT oi.product_name_snapshot AS name, oi.quantity, oi.subtotal, o.created_at
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.farmer_id = ? AND o.logistics_status = 'delivered'
             ORDER BY o.completed_at DESC
             LIMIT ?"
        );
        $stmt->execute([$farmerId, $limit]);
        return $stmt->fetchAll();
    }
}
