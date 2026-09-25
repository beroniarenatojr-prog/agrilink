<?php

class DeliveryTracking
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function activate(int $orderId, int $logisticsUserId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO order_delivery_tracking (order_id, logistics_user_id, latitude, longitude, is_active)
             VALUES (?, ?, 0, 0, 1)
             ON DUPLICATE KEY UPDATE logistics_user_id = VALUES(logistics_user_id),
                                     is_active = 1,
                                     updated_at = NOW()'
        );
        $stmt->execute([$orderId, $logisticsUserId]);
    }

    public function deactivate(int $orderId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE order_delivery_tracking SET is_active = 0, updated_at = NOW() WHERE order_id = ?'
        );
        $stmt->execute([$orderId]);
    }

    public function updatePosition(
        int $orderId,
        int $logisticsUserId,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?float $heading = null,
        ?float $speed = null
    ): bool {
        $stmt = $this->pdo->prepare(
            'SELECT updated_at, latitude, longitude FROM order_delivery_tracking
             WHERE order_id = ? AND logistics_user_id = ? AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([$orderId, $logisticsUserId]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        $isFirstFix = ((float) $row['latitude'] === 0.0 && (float) $row['longitude'] === 0.0);
        $lastUpdate = strtotime($row['updated_at'] ?? '');
        if (!$isFirstFix && $lastUpdate && (time() - $lastUpdate) < 5) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE order_delivery_tracking
             SET latitude = ?, longitude = ?, accuracy = ?, heading = ?, speed = ?, updated_at = NOW()
             WHERE order_id = ? AND logistics_user_id = ? AND is_active = 1'
        );
        $stmt->execute([
            $latitude,
            $longitude,
            $accuracy,
            $heading,
            $speed,
            $orderId,
            $logisticsUserId,
        ]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        $pointStmt = $this->pdo->prepare(
            'INSERT INTO order_tracking_points (order_id, latitude, longitude) VALUES (?,?,?)'
        );
        $pointStmt->execute([$orderId, $latitude, $longitude]);

        return true;
    }

    public function getSnapshot(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.id, o.logistics_status, o.delivery_address, o.delivery_lat, o.delivery_lng,
                    t.logistics_user_id, t.latitude, t.longitude, t.accuracy, t.updated_at, t.is_active
             FROM orders o
             LEFT JOIN order_delivery_tracking t ON t.order_id = o.id
             WHERE o.id = ?
             LIMIT 1'
        );
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return null;
        }

        if ($order['delivery_lat'] === null || $order['delivery_lng'] === null) {
            $orderModel = new Order($this->pdo);
            $coords = $orderModel->ensureDeliveryCoordinates($orderId);
            if ($coords !== null) {
                $order['delivery_lat'] = $coords['lat'];
                $order['delivery_lng'] = $coords['lng'];
            }
        }

        $trail = [];
        $trailStmt = $this->pdo->prepare(
            'SELECT latitude, longitude FROM order_tracking_points
             WHERE order_id = ?
             ORDER BY recorded_at ASC
             LIMIT 500'
        );
        $trailStmt->execute([$orderId]);
        foreach ($trailStmt->fetchAll() as $point) {
            $trail[] = [(float) $point['latitude'], (float) $point['longitude']];
        }

        $rider = null;
        if ($order['latitude'] !== null && $order['longitude'] !== null
            && ((float) $order['latitude'] !== 0.0 || (float) $order['longitude'] !== 0.0)) {
            $updatedAt = $order['updated_at'] ?? null;
            $stale = true;
            if ($updatedAt) {
                $stale = (time() - strtotime($updatedAt)) > TRACKING_STALE_SECONDS;
            }
            $rider = [
                'lat'        => (float) $order['latitude'],
                'lng'        => (float) $order['longitude'],
                'updated_at' => $updatedAt,
                'stale'      => $stale,
            ];
        }

        $destination = null;
        if ($order['delivery_lat'] !== null && $order['delivery_lng'] !== null) {
            $destination = [
                'lat'     => (float) $order['delivery_lat'],
                'lng'     => (float) $order['delivery_lng'],
                'address' => $order['delivery_address'],
            ];
        }

        return [
            'order_id'        => (int) $order['id'],
            'status'          => $order['logistics_status'],
            'destination'     => $destination,
            'rider'           => $rider,
            'trail'           => $trail,
            'tracking_active' => (bool) ($order['is_active'] ?? false),
        ];
    }

    public function getActiveInTransitOrdersForLogistics(int $logisticsUserId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.id, o.logistics_status, t.updated_at, t.is_active
             FROM order_logistics_assignments ola
             JOIN orders o ON o.id = ola.order_id
             LEFT JOIN order_delivery_tracking t ON t.order_id = o.id
             WHERE ola.logistics_user_id = ?
               AND o.logistics_status = 'in_transit'
             ORDER BY o.id DESC"
        );
        $stmt->execute([$logisticsUserId]);
        return $stmt->fetchAll();
    }

    public function isLive(int $orderId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT updated_at, latitude, longitude FROM order_delivery_tracking
             WHERE order_id = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        if (!$row || empty($row['updated_at'])) {
            return false;
        }
        if ((float) $row['latitude'] === 0.0 && (float) $row['longitude'] === 0.0) {
            return false;
        }
        return (time() - strtotime($row['updated_at'])) <= TRACKING_STALE_SECONDS;
    }

    public function countLiveForLogistics(int $logisticsUserId): int
    {
        $stale = (int) TRACKING_STALE_SECONDS;
        $stmt  = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM order_delivery_tracking t
             JOIN order_logistics_assignments ola ON ola.order_id = t.order_id AND ola.logistics_user_id = ?
             JOIN orders o ON o.id = t.order_id
             WHERE t.is_active = 1
               AND o.logistics_status = 'in_transit'
               AND t.latitude != 0 AND t.longitude != 0
               AND t.updated_at >= DATE_SUB(NOW(), INTERVAL $stale SECOND)"
        );
        $stmt->execute([$logisticsUserId]);
        return (int) $stmt->fetchColumn();
    }
}
