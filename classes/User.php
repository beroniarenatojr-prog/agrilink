<?php

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, phone, address, role, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, password, phone, address, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(string $role = '', string $search = '', ?int $limit = null, int $offset = 0): array
    {
        $params = [];
        $where  = ['1=1'];

        if ($role !== '') {
            $where[]  = 'role = ?';
            $params[] = $role;
        }

        if ($search !== '') {
            $where[]  = '(name LIKE ? OR email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql = 'SELECT id, name, email, phone, address, role, created_at FROM users WHERE '
            . implode(' AND ', $where)
            . ' ORDER BY created_at DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(string $role = '', string $search = ''): int
    {
        $params = [];
        $where  = ['1=1'];

        if ($role !== '') {
            $where[]  = 'role = ?';
            $params[] = $role;
        }

        if ($search !== '') {
            $where[]  = '(name LIKE ? OR email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql = 'SELECT COUNT(*) FROM users WHERE ' . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function countByRole(): array
    {
        $stmt = $this->pdo->query('SELECT role, COUNT(*) AS cnt FROM users GROUP BY role');
        $rows = $stmt->fetchAll();
        $out  = ['admin' => 0, 'farmer' => 0, 'buyer' => 0, 'logistics' => 0];
        foreach ($rows as $row) {
            $out[$row['role']] = (int)$row['cnt'];
        }
        return $out;
    }

    public function getRecentSignups(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, role, created_at
             FROM users
             WHERE role IN ('farmer', 'buyer', 'logistics')
             ORDER BY created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
