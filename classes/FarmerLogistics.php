<?php

class FarmerLogistics
{
    private PDO $pdo;


    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listLogisticsUsersForFarmer(int $farmerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id,
                    u.name,
                    u.email,
                    u.phone,
                    u.address,
                    u.role,
                    ul.is_active,
                    ul.created_at,
                    ul.updated_at
             FROM farmer_logistics ul
             JOIN users u ON u.id = ul.logistics_user_id
             WHERE ul.farmer_id = ?
             AND u.role = 'logistics'
             ORDER BY ul.created_at DESC"
        );
        $stmt->execute([$farmerId]);

        return $stmt->fetchAll();
    }

    public function listActiveLogisticsUsersForFarmer(int $farmerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id,
                    u.name,
                    u.email,
                    u.phone,
                    u.address,
                    u.role,
                    ul.is_active,
                    ul.created_at,
                    ul.updated_at
             FROM farmer_logistics ul
             JOIN users u ON u.id = ul.logistics_user_id
             WHERE ul.farmer_id = ?
             AND ul.is_active = 1
             AND u.role = 'logistics'
             ORDER BY u.name ASC"
        );
        $stmt->execute([$farmerId]);

        return $stmt->fetchAll();
    }

    public function findLogisticsUserForFarmer(int $farmerId, int $logisticsUserId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id,
                    u.name,
                    u.email,
                    u.phone,
                    u.address,
                    u.role,
                    ul.is_active,
                    ul.created_at,
                    ul.updated_at
             FROM farmer_logistics ul
             JOIN users u ON u.id = ul.logistics_user_id
             WHERE ul.farmer_id = ?
             AND ul.logistics_user_id = ?
             AND u.role = 'logistics'
             LIMIT 1"
        );
        $stmt->execute([$farmerId, $logisticsUserId]);
        return $stmt->fetch() ?: null;
    }

    public function userBelongsToFarmer(int $farmerId, int $logisticsUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM farmer_logistics WHERE farmer_id = ? AND logistics_user_id = ? LIMIT 1'
        );
        $stmt->execute([$farmerId, $logisticsUserId]);
        return (bool) $stmt->fetchColumn();
    }

    public function userIsActiveForFarmer(int $farmerId, int $logisticsUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM farmer_logistics
             WHERE farmer_id = ? AND logistics_user_id = ? AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([$farmerId, $logisticsUserId]);
        return (bool) $stmt->fetchColumn();
    }

    public function createAssignment(int $farmerId, int $logisticsUserId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO farmer_logistics (farmer_id, logistics_user_id) VALUES (?,?)'
        );
        $stmt->execute([$farmerId, $logisticsUserId]);
    }

    public function deleteAssignment(int $farmerId, int $logisticsUserId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM farmer_logistics WHERE farmer_id = ? AND logistics_user_id = ?'
        );
        $stmt->execute([$farmerId, $logisticsUserId]);
    }

    /**
     * Fetch the farmer assigned to a specific logistics user.
     * Returns the first match (system currently supports one active assignment per pair).
     */
    public function getAssignedFarmerForLogisticsUser(int $logisticsUserId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.id,
                    f.name,
                    f.email,
                    f.phone,
                    f.address,
                    f.role
             FROM farmer_logistics fl
             JOIN users f ON f.id = fl.farmer_id
             WHERE fl.logistics_user_id = ?
               AND fl.is_active = 1
               AND f.role = 'farmer'
             ORDER BY fl.created_at DESC
             LIMIT 1"
        );
        $stmt->execute([$logisticsUserId]);
        return $stmt->fetch() ?: null;
    }
}
