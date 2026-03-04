<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\HoaDon;
use App\Models\ThanhToan;
use App\Models\HocSinh;

class InController extends BaseController
{
    public function invoiceAction(): void
    {
        $this->requireLogin();
        
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo 'Thieu ID phieu bao thu.';
            return;
        }
        
        $invoice = HoaDon::find($id);
        if (!$invoice) {
            echo 'Khong tim thay phieu bao thu.';
            return;
        }
        
        $items = HoaDon::getItems($id);
        $student = HocSinh::find($invoice['student_id']);
        
        // Lấy thông tin QR thanh toán
        $qrInfo = [];
        if (function_exists('getVietQRPaymentInfo')) {
            $qrInfo = getVietQRPaymentInfo(
                (int)$invoice['total_amount'], 
                $invoice['invoice_code']
            );
            // Thêm tên ngân hàng
            $bankNames = [
                'BIDV' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam (BIDV)',
                'VCB' => 'Ngân hàng TMCP Ngoại thương Việt Nam (Vietcombank)',
                'MB' => 'Ngân hàng TMCP Quân đội (MB Bank)',
                'TCB' => 'Ngân hàng TMCP Kỹ thương Việt Nam (Techcombank)',
                'VPB' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng (VPBank)',
            ];
            $qrInfo['bank_name'] = $bankNames[$qrInfo['bank_id']] ?? $qrInfo['bank_id'];
        }
        
        // Set flag to use print layout
        $_GET['print'] = '1';
        
        $this->renderPrint('in/invoice', [
            'pageTitle' => 'In phiếu báo thu',
            'invoice' => $invoice,
            'items' => $items,
            'student' => $student,
            'qrInfo' => $qrInfo,
        ]);
    }
    
    public function invoiceBulkAction(): void
    {
        $this->requireLogin();
        
        $ids = $_GET['ids'] ?? '';
        if (empty($ids)) {
            echo 'Khong co phieu nao duoc chon.';
            return;
        }
        
        $idArray = array_filter(array_map('intval', explode(',', $ids)));
        
        $invoices = [];
        foreach ($idArray as $id) {
            $inv = HoaDon::find($id);
            if ($inv) {
                $inv['items'] = HoaDon::getItems($id);
                $inv['student'] = HocSinh::find($inv['student_id']);
                $invoices[] = $inv;
            }
        }
        
        $_GET['print'] = '1';
        
        $this->renderPrint('in/invoice_bulk', [
            'pageTitle' => 'In nhiều phiếu báo thu',
            'invoices' => $invoices,
        ]);
    }
    
    public function reportAction(): void
    {
        $this->requireLogin();
        
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));
        
        $stats = $this->getMonthlyStats($month, $year);
        $classStats = $this->getClassStats($year);
        
        $_GET['print'] = '1';
        
        $this->renderPrint('in/report', [
            'pageTitle' => 'In báo cáo',
            'month' => $month,
            'year' => $year,
            'stats' => $stats,
            'classStats' => $classStats,
        ]);
    }
    
    private function getMonthlyStats(int $month, int $year): array
    {
        $pdo = \App\Core\Database::getConnection();
        
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as total_invoices,
            COALESCE(SUM(total_amount), 0) as total_amount,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count
            FROM invoices WHERE month = :month AND year = :year");
        $stmt->execute(['month' => $month, 'year' => $year]);
        $invoiceStats = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_collected
            FROM payments WHERE MONTH(paid_at) = :month AND YEAR(paid_at) = :year");
        $stmt->execute(['month' => $month, 'year' => $year]);
        $collected = (int)$stmt->fetchColumn();
        
        return [
            'total_invoices' => (int)($invoiceStats['total_invoices'] ?? 0),
            'total_amount' => (int)($invoiceStats['total_amount'] ?? 0),
            'paid_count' => (int)($invoiceStats['paid_count'] ?? 0),
            'pending_count' => (int)($invoiceStats['pending_count'] ?? 0),
            'partial_count' => (int)($invoiceStats['partial_count'] ?? 0),
            'collected' => $collected,
            'uncollected' => max(0, (int)($invoiceStats['total_amount'] ?? 0) - $collected),
        ];
    }
    
    private function getClassStats(int $year): array
    {
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->query("SELECT DISTINCT class FROM students WHERE class != '' ORDER BY class");
        $classes = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        $result = [];

        foreach ($classes as $class) {
            $stmt = $pdo->prepare(
                "SELECT 
                    COUNT(DISTINCT s.id) AS student_count,
                    COUNT(i.id) AS invoice_count,
                    COALESCE(SUM(i.total_amount), 0) AS total_amount,
                    COALESCE(
                        (
                            SELECT SUM(p.amount) 
                            FROM payments p 
                            JOIN invoices inv ON p.invoice_id = inv.id 
                            JOIN students st ON inv.student_id = st.id 
                            WHERE st.class = :class_sub 
                              AND inv.year = :year_paid
                        ),
                        0
                    ) AS collected
                 FROM students s
                 LEFT JOIN invoices i 
                    ON s.id = i.student_id 
                   AND i.year = :year_invoice
                 WHERE s.class = :class_main 
                   AND s.status = 'active'"
            );

            $stmt->execute([
                'class_sub'    => $class,
                'year_paid'    => $year,
                'year_invoice' => $year,
                'class_main'   => $class,
            ]);

            $data = $stmt->fetch();
            
            $result[] = [
                'class' => $class,
                'student_count' => (int)($data['student_count'] ?? 0),
                'invoice_count' => (int)($data['invoice_count'] ?? 0),
                'total_amount' => (int)($data['total_amount'] ?? 0),
                'collected' => (int)($data['collected'] ?? 0),
            ];
        }
        
        return $result;
    }
}
