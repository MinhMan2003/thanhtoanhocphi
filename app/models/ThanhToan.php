<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ThanhToan
{
    public static function paginate(string $q = '', int $page = 1, int $limit = 20, array $filters = []): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        // Search query
        if ($q !== '') {
            $where[] = "(p.bank_ref LIKE :q1 OR i.invoice_code LIKE :q2 OR s.full_name LIKE :q3)";
            $params['q1'] = "%$q%";
            $params['q2'] = "%$q%";
            $params['q3'] = "%$q%";
        }

        // Filter by invoice_id
        if (!empty($filters['invoice_id'])) {
            $where[] = "p.invoice_id = :invoice_id";
            $params['invoice_id'] = $filters['invoice_id'];
        }

        // Filter by payment_method
        if (!empty($filters['payment_method']) && in_array($filters['payment_method'], ['vietqr', 'bank_transfer', 'cash', 'other'], true)) {
            $where[] = "p.payment_method = :payment_method";
            $params['payment_method'] = $filters['payment_method'];
        }

        // Filter by from_date
        if (!empty($filters['from_date'])) {
            $where[] = "DATE(p.paid_at) >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        // Filter by to_date
        if (!empty($filters['to_date'])) {
            $where[] = "DATE(p.paid_at) <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM payments p
                     JOIN invoices i ON p.invoice_id = i.id
                     JOIN students s ON i.student_id = s.id
                     $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT p.*, i.invoice_code, i.total_amount as invoice_total, s.full_name as student_name, s.student_code
                FROM payments p
                JOIN invoices i ON p.invoice_id = i.id
                JOIN students s ON i.student_id = s.id
                $whereClause
                ORDER BY p.paid_at DESC
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
        $stmt = $pdo->prepare("SELECT p.*, i.invoice_code, i.total_amount as invoice_total, s.full_name as student_name
                               FROM payments p
                               JOIN invoices i ON p.invoice_id = i.id
                               JOIN students s ON i.student_id = s.id
                               WHERE p.id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, payment_method, amount, paid_at, bank_ref, note)
                                   VALUES (:invoice_id, :payment_method, :amount, :paid_at, :bank_ref, :note)");
            $stmt->execute([
                'invoice_id' => $data['invoice_id'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'amount' => $data['amount'],
                'paid_at' => $data['paid_at'],
                'bank_ref' => $data['bank_ref'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $paymentId = (int)$pdo->lastInsertId();

            // Cập nhật trạng thái invoice
            self::updateInvoiceStatus($data['invoice_id']);

            $pdo->commit();
            return $paymentId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function updateInvoiceStatus(int $invoiceId): void
    {
        $pdo = Database::getConnection();

        // Lấy tổng tiền đã thanh toán
        $stmt = $pdo->prepare('SELECT SUM(amount) FROM payments WHERE invoice_id = :invoice_id');
        $stmt->execute(['invoice_id' => $invoiceId]);
        $paidAmount = (int)$stmt->fetchColumn();

        // Lấy tổng tiền phiếu
        $stmt = $pdo->prepare('SELECT total_amount FROM invoices WHERE id = :id');
        $stmt->execute(['id' => $invoiceId]);
        $totalAmount = (int)$stmt->fetchColumn();

        $status = 'pending';
        if ($paidAmount >= $totalAmount) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partial';
        }

        $stmt = $pdo->prepare('UPDATE invoices SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $invoiceId]);
    }

    public static function getAllForSelect(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT p.id, p.amount, p.paid_at, i.invoice_code, s.full_name as student_name
                             FROM payments p
                             JOIN invoices i ON p.invoice_id = i.id
                             JOIN students s ON i.student_id = s.id
                             ORDER BY p.paid_at DESC
                             LIMIT 100");
        return $stmt->fetchAll();
    }

    public static function getStats(): array
    {
        $pdo = Database::getConnection();

        $totalStmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments");
        $totalAmount = (int)$totalStmt->fetchColumn();

        $todayStmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(paid_at) = CURDATE()");
        $todayAmount = (int)$todayStmt->fetchColumn();

        $monthStmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE MONTH(paid_at) = MONTH(CURDATE()) AND YEAR(paid_at) = YEAR(CURDATE())");
        $monthAmount = (int)$monthStmt->fetchColumn();

        return [
            'total' => $totalAmount,
            'today' => $todayAmount,
            'month' => $monthAmount,
        ];
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        $oldPayment = self::find($id);
        if (!$oldPayment) {
            return false;
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("UPDATE payments SET 
                                   invoice_id = :invoice_id,
                                   payment_method = :payment_method,
                                   amount = :amount,
                                   paid_at = :paid_at,
                                   bank_ref = :bank_ref,
                                   note = :note
                                   WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'invoice_id' => $data['invoice_id'],
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
                'paid_at' => $data['paid_at'],
                'bank_ref' => $data['bank_ref'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Cập nhật trạng thái invoice cũ và mới
            self::updateInvoiceStatus($oldPayment['invoice_id']);
            if ($oldPayment['invoice_id'] !== $data['invoice_id']) {
                self::updateInvoiceStatus($data['invoice_id']);
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
        $payment = self::find($id);
        if (!$payment) {
            return false;
        }

        $invoiceId = $payment['invoice_id'];

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('DELETE FROM payments WHERE id = :id');
            $stmt->execute(['id' => $id]);

            // Cập nhật trạng thái invoice sau khi xóa thanh toán
            self::updateInvoiceStatus($invoiceId);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
