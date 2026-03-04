<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class HoaDon
{
    public static function paginate(string $q = '', int $page = 1, int $limit = 20, array $filters = []): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        // Search query
        if ($q !== '') {
            $where[] = "(i.invoice_code LIKE :q OR s.full_name LIKE :q OR s.student_code LIKE :q)";
            $params['q'] = "%$q%";
        }

        // Filter by status
        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'paid', 'partial', 'cancelled'], true)) {
            $where[] = "i.status = :status";
            $params['status'] = $filters['status'];
        }

        // Filter by student_id
        if (!empty($filters['student_id'])) {
            $where[] = "i.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
        }

        // Filter by month
        if (!empty($filters['month'])) {
            $where[] = "i.month = :month";
            $params['month'] = $filters['month'];
        }

        // Filter by year
        if (!empty($filters['year'])) {
            $where[] = "i.year = :year";
            $params['year'] = $filters['year'];
        }

        // Filter by grade (khoi)
        if (!empty($filters['grade'])) {
            $where[] = "s.grade = :grade";
            $params['grade'] = $filters['grade'];
        }

        // Filter by class
        if (!empty($filters['class'])) {
            $where[] = "s.class = :class";
            $params['class'] = $filters['class'];
        }

        // Filter by from_date
        if (!empty($filters['from_date'])) {
            $where[] = "i.issue_date >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        // Filter by to_date
        if (!empty($filters['to_date'])) {
            $where[] = "i.issue_date <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM invoices i 
                     JOIN students s ON i.student_id = s.id 
                     $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT i.*, s.full_name as student_name, s.student_code, s.grade as khoi, s.class
                FROM invoices i
                JOIN students s ON i.student_id = s.id
                $whereClause
                ORDER BY i.created_at DESC
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

    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT i.*, s.full_name as student_name, s.student_code, s.grade, s.class
                               FROM invoices i
                               JOIN students s ON i.student_id = s.id
                               WHERE i.id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch();

        if ($invoice) {
            $invoice['items'] = self::getItems($id);
        }

        return $invoice ?: null;
    }

    public static function getItems(int $invoiceId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT ii.*, fc.name as fee_category_name
                               FROM invoice_items ii
                               LEFT JOIN fee_categories fc ON ii.fee_category_id = fc.id
                               WHERE ii.invoice_id = :invoice_id
                               ORDER BY ii.sort_order");
        $stmt->execute(['invoice_id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_code, student_id, month, year, issue_date, due_date, total_amount, status, note)
                                   VALUES (:invoice_code, :student_id, :month, :year, :issue_date, :due_date, :total_amount, :status, :note)");
            $stmt->execute([
                'invoice_code' => $data['invoice_code'],
                'student_id' => $data['student_id'],
                'month' => $data['month'],
                'year' => $data['year'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'total_amount' => $data['total_amount'],
                'status' => $data['status'] ?? 'pending',
                'note' => $data['note'] ?? null,
            ]);

            $invoiceId = (int)$pdo->lastInsertId();

            if (!empty($data['items'])) {
                $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, fee_category_id, description, amount, sort_order)
                                           VALUES (:invoice_id, :fee_category_id, :description, :amount, :sort_order)");
                foreach ($data['items'] as $index => $item) {
                    $itemStmt->execute([
                        'invoice_id' => $invoiceId,
                        'fee_category_id' => $item['fee_category_id'] ?? null,
                        'description' => $item['description'],
                        'amount' => $item['amount'],
                        'sort_order' => $index,
                    ]);
                }
            }

            $pdo->commit();
            return $invoiceId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("UPDATE invoices SET 
                                   student_id = :student_id,
                                   month = :month,
                                   year = :year,
                                   issue_date = :issue_date,
                                   due_date = :due_date,
                                   total_amount = :total_amount,
                                   status = :status,
                                   note = :note
                                   WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'student_id' => $data['student_id'],
                'month' => $data['month'],
                'year' => $data['year'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'total_amount' => $data['total_amount'],
                'status' => $data['status'] ?? 'pending',
                'note' => $data['note'] ?? null,
            ]);

            // Xóa items cũ và thêm mới
            $pdo->exec("DELETE FROM invoice_items WHERE invoice_id = $id");

            if (!empty($data['items'])) {
                $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, fee_category_id, description, amount, sort_order)
                                           VALUES (:invoice_id, :fee_category_id, :description, :amount, :sort_order)");
                foreach ($data['items'] as $index => $item) {
                    $itemStmt->execute([
                        'invoice_id' => $id,
                        'fee_category_id' => $item['fee_category_id'] ?? null,
                        'description' => $item['description'],
                        'amount' => $item['amount'],
                        'sort_order' => $index,
                    ]);
                }
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM invoices WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function generateCode(): string
    {
        $pdo = Database::getConnection();
        $year = date('Y');
        $month = date('m');
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE year = :year AND month = :month");
        $stmt->execute(['year' => $year, 'month' => $month]);
        $count = (int)$stmt->fetchColumn() + 1;
        
        return sprintf("PT%s%s%04d", $year, $month, $count);
    }

    public static function getAllForSelect(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT i.id, i.invoice_code, s.full_name as student_name, i.total_amount, i.status, i.month, i.year
                             FROM invoices i
                             JOIN students s ON i.student_id = s.id
                             ORDER BY i.created_at DESC
                             LIMIT 100");
        return $stmt->fetchAll();
    }

    public static function getPendingInvoices(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT i.*, s.full_name as student_name, s.student_code
                             FROM invoices i
                             JOIN students s ON i.student_id = s.id
                             WHERE i.status IN ('pending', 'partial')
                             ORDER BY i.due_date ASC, i.created_at DESC");
        return $stmt->fetchAll();
    }
}
