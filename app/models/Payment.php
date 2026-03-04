<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Payment
{
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $payment = $stmt->fetch();

        return $payment ?: null;
    }

    public static function findByTransId(string $transId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE trans_id = :trans_id LIMIT 1');
        $stmt->execute(['trans_id' => $transId]);
        $payment = $stmt->fetch();

        return $payment ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO payments (trans_id, amount, content, bank_time, account_no, account_name, bank_id, raw_payload, match_status)
            VALUES (:trans_id, :amount, :content, :bank_time, :account_no, :account_name, :bank_id, :raw_payload, :match_status)
        ");
        
        $stmt->execute([
            'trans_id' => $data['trans_id'],
            'amount' => $data['amount'],
            'content' => $data['content'] ?? '',
            'bank_time' => $data['bank_time'] ?? null,
            'account_no' => $data['account_no'] ?? '',
            'account_name' => $data['account_name'] ?? '',
            'bank_id' => $data['bank_id'] ?? '',
            'raw_payload' => $data['raw_payload'] ?? null,
            'match_status' => $data['match_status'] ?? 'PENDING',
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        
        $fields = [];
        $params = ['id' => $id];
        
        if (isset($data['matched_hoadon_id'])) {
            $fields[] = 'matched_hoadon_id = :matched_hoadon_id';
            $params['matched_hoadon_id'] = $data['matched_hoadon_id'];
        }
        if (isset($data['match_status'])) {
            $fields[] = 'match_status = :match_status';
            $params['match_status'] = $data['match_status'];
        }
        if (isset($data['matched_at'])) {
            $fields[] = 'matched_at = :matched_at';
            $params['matched_at'] = $data['matched_at'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = 'UPDATE payments SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }

    public static function paginate(string $q = '', int $page = 1, int $limit = 20, array $filters = []): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        // Search query
        if ($q !== '') {
            $where[] = "(p.trans_id LIKE :q OR p.content LIKE :q OR p.account_name LIKE :q)";
            $params['q'] = "%$q%";
        }

        // Filter by match_status
        if (!empty($filters['match_status']) && in_array($filters['match_status'], ['MATCHED', 'UNMATCHED', 'PENDING'], true)) {
            $where[] = "p.match_status = :match_status";
            $params['match_status'] = $filters['match_status'];
        }

        // Filter by from_date
        if (!empty($filters['from_date'])) {
            $where[] = "DATE(p.created_at) >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        // Filter by to_date
        if (!empty($filters['to_date'])) {
            $where[] = "DATE(p.created_at) <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM payments p $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT p.*, 
                    h.invoice_code, h.total_amount as hoadon_total, h.student_id,
                    s.full_name as student_name, s.student_code
                FROM payments p
                LEFT JOIN hoadon h ON p.matched_hoadon_id = h.id
                LEFT JOIN students s ON h.student_id = s.id
                $whereClause
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";

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

    public static function getUnmatched(int $page = 1, int $limit = 20): array
    {
        return self::paginate('', $page, $limit, ['match_status' => 'UNMATCHED']);
    }

    public static function getMatched(int $page = 1, int $limit = 20): array
    {
        return self::paginate('', $page, $limit, ['match_status' => 'MATCHED']);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM payments WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
