<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class LoaiKhoanThu
{
    public static function allActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM fee_categories WHERE is_active = 1 ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public static function paginate(string $q = '', int $page = 1, int $limit = 20, array $filters = []): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        // Search query
        if ($q !== '') {
            $where[] = "name LIKE :q";
            $params['q'] = '%' . $q . '%';
        }

        // Filter by is_active
        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $where[] = "is_active = :is_active";
            $params['is_active'] = (int)$filters['is_active'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_categories $whereClause");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM fee_categories $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM fee_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO fee_categories (name, description, default_amount, unit, is_active)
             VALUES (:name, :description, :default_amount, :unit, :is_active)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'default_amount' => (int)($data['default_amount'] ?? 0),
            'unit' => $data['unit'],
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE fee_categories
             SET name = :name,
                 description = :description,
                 default_amount = :default_amount,
                 unit = :unit,
                 is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'default_amount' => (int)($data['default_amount'] ?? 0),
            'unit' => $data['unit'],
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM fee_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM fee_categories WHERE is_active = 1 ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }
}

