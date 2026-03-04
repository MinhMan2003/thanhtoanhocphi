<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AuditLog
{
    public static function log(array $data): int
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (entity_type, entity_id, action, field_changed, old_value, new_value, user_id, user_name, ip_address, user_agent)
            VALUES (:entity_type, :entity_id, :action, :field_changed, :old_value, :new_value, :user_id, :user_name, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'],
            'action' => $data['action'],
            'field_changed' => $data['field_changed'] ?? null,
            'old_value' => $data['old_value'] ?? null,
            'new_value' => $data['new_value'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'user_name' => $data['user_name'] ?? 'system',
            'ip_address' => $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function logStatusChange(string $entityType, int $entityId, string $oldStatus, string $newStatus, ?int $userId = null, ?string $userName = null): int
    {
        return self::log([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => 'status_changed',
            'field_changed' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'user_id' => $userId,
            'user_name' => $userName,
        ]);
    }

    public static function logCreated(string $entityType, int $entityId, ?int $userId = null, ?string $userName = null): int
    {
        return self::log([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => 'created',
            'user_id' => $userId,
            'user_name' => $userName,
        ]);
    }

    public static function logUpdated(string $entityType, int $entityId, string $field, $oldValue, $newValue, ?int $userId = null, ?string $userName = null): int
    {
        return self::log([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => 'updated',
            'field_changed' => $field,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : (string)$oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : (string)$newValue,
            'user_id' => $userId,
            'user_name' => $userName,
        ]);
    }

    public static function logDeleted(string $entityType, int $entityId, ?int $userId = null, ?string $userName = null): int
    {
        return self::log([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => 'deleted',
            'user_id' => $userId,
            'user_name' => $userName,
        ]);
    }

    public static function getByEntity(string $entityType, int $entityId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM audit_logs 
            WHERE entity_type = :entity_type AND entity_id = :entity_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        return $stmt->fetchAll();
    }

    public static function paginate(string $q = '', int $page = 1, int $limit = 20, array $filters = []): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($filters['entity_type'])) {
            $where[] = "entity_type = :entity_type";
            $params['entity_type'] = $filters['entity_type'];
        }

        if (!empty($filters['entity_id'])) {
            $where[] = "entity_id = :entity_id";
            $params['entity_id'] = $filters['entity_id'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['from_date'])) {
            $where[] = "DATE(created_at) >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $where[] = "DATE(created_at) <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM audit_logs $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT * FROM audit_logs $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
        ];
    }
}
