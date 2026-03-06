<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class HocSinhPortal
{
    /**
     * Tìm học sinh theo ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, student_code, full_name, grade, class, dob, address, parent_name, parent_phone, parent_email
                                FROM students 
                                WHERE id = :id AND status = 'active'");
        $stmt->execute(['id' => $id]);
        $student = $stmt->fetch();
        
        return $student ?: null;
    }
    
    /**
     * Tra cứu học sinh bằng mã học sinh và ngày sinh
     */
    public static function lookup(string $studentCode, string $dob): ?array
    {
        $pdo = Database::getConnection();
        
        // Chuẩn hóa ngày sinh (chấp nhận DD/MM/YYYY hoặc YYYY-MM-DD)
        $dobNormalized = self::normalizeDate($dob);
        if ($dobNormalized === null) {
            return null;
        }
        
        $stmt = $pdo->prepare("SELECT id, student_code, full_name, grade, class, dob, address, parent_name, parent_phone, parent_email
                                FROM students 
                                WHERE student_code = :student_code AND dob = :dob AND status = 'active'");
        $stmt->execute([
            'student_code' => trim($studentCode),
            'dob' => $dobNormalized
        ]);
        $student = $stmt->fetch();
        
        return $student ?: null;
    }
    
    /**
     * Lấy thông tin hóa đơn của học sinh
     */
    public static function getInvoices(int $studentId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT i.*, 
            COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id = i.id), 0) as paid_amount
            FROM invoices i 
            WHERE i.student_id = :student_id 
            ORDER BY i.year DESC, i.month DESC, i.created_at DESC");
        $stmt->execute(['student_id' => $studentId]);
        $invoices = $stmt->fetchAll();
        
        // Tính toán trạng thái
        foreach ($invoices as &$inv) {
            $paidAmount = (int)$inv['paid_amount'];
            $totalAmount = (int)$inv['total_amount'];
            
            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $inv['status'] = 'paid';
            } elseif ($paidAmount > 0) {
                $inv['status'] = 'partial';
            } else {
                $inv['status'] = 'pending';
            }
            $inv['remaining_amount'] = $totalAmount - $paidAmount;
        }
        
        return $invoices;
    }
    
    /**
     * Lấy chi tiết một hóa đơn
     */
    public static function getInvoiceDetail(int $invoiceId, int $studentId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT i.*, s.full_name as student_name, s.student_code, s.grade, s.class, s.dob,
            COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id = i.id), 0) as paid_amount
            FROM invoices i
            JOIN students s ON i.student_id = s.id
            WHERE i.id = :invoice_id AND i.student_id = :student_id");
        $stmt->execute(['invoice_id' => $invoiceId, 'student_id' => $studentId]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            return null;
        }
        
        // Lấy items
        $itemsStmt = $pdo->prepare("SELECT ii.*, fc.name as fee_category_name
                                    FROM invoice_items ii
                                    LEFT JOIN fee_categories fc ON ii.fee_category_id = fc.id
                                    WHERE ii.invoice_id = :invoice_id
                                    ORDER BY ii.sort_order");
        $itemsStmt->execute(['invoice_id' => $invoiceId]);
        $invoice['items'] = $itemsStmt->fetchAll();
        
        // Tính trạng thái
        $paidAmount = (int)$invoice['paid_amount'];
        $totalAmount = (int)$invoice['total_amount'];
        
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $invoice['status'] = 'paid';
        } elseif ($paidAmount > 0) {
            $invoice['status'] = 'partial';
        } else {
            $invoice['status'] = 'pending';
        }
        $invoice['remaining_amount'] = $totalAmount - $paidAmount;
        
        return $invoice;
    }
    
    /**
     * Chuẩn hóa ngày sinh về YYYY-MM-DD
     */
    private static function normalizeDate(string $date): ?string
    {
        $date = trim($date);
        
        // Nếu đã là YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        
        // Nếu là DD/MM/YYYY
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) {
            $parts = explode('/', $date);
            $day = (int)$parts[0];
            $month = (int)$parts[1];
            $year = (int)$parts[2];
            
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        
        return null;
    }
}
