<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;

class BaoCaoController extends BaseController
{
    public function indexAction(): void
    {
        $this->requireLogin();

        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        $stats = $this->getMonthlyStats($month, $year);
        $topStudents = $this->getTopStudents($year);
        $classStats = $this->getClassStats($year);

        $this->render('baocao/index', [
            'pageTitle' => 'Báo cáo & Thống kê',
            'month' => $month,
            'year' => $year,
            'stats' => $stats,
            'topStudents' => $topStudents,
            'classStats' => $classStats,
        ]);
    }

    private function getMonthlyStats(int $month, int $year): array
    {
        $pdo = \App\Core\Database::getConnection();

        // Lấy tất cả hóa đơn trong tháng
        $stmt = $pdo->prepare("SELECT 
            i.id,
            i.total_amount,
            COALESCE(
                (SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.id),
                0
            ) as paid_amount
            FROM invoices i 
            WHERE i.month = :month AND i.year = :year");
        $stmt->execute(['month' => $month, 'year' => $year]);
        $invoices = $stmt->fetchAll();

        $totalInvoices = count($invoices);
        $totalAmount = 0;
        $paidCount = 0;
        $partialCount = 0;
        $pendingCount = 0;
        $collected = 0;

        foreach ($invoices as $inv) {
            $totalAmount += (int)$inv['total_amount'];
            $paidAmount = (int)$inv['paid_amount'];
            $collected += $paidAmount;

            if ($paidAmount >= (int)$inv['total_amount']) {
                $paidCount++;
            } elseif ($paidAmount > 0) {
                $partialCount++;
            } else {
                $pendingCount++;
            }
        }

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'paid_count' => $paidCount,
            'pending_count' => $pendingCount,
            'partial_count' => $partialCount,
            'collected' => $collected,
            'uncollected' => max(0, $totalAmount - $collected),
        ];
    }

    private function getTopStudents(int $year, int $limit = 10): array
    {
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT 
                s.id, 
                s.student_code, 
                s.full_name, 
                s.grade,
                s.class,
                COALESCE(SUM(i.total_amount), 0) AS total_invoice,
                COALESCE(
                    (
                        SELECT SUM(p.amount) 
                        FROM payments p 
                        JOIN invoices inv ON p.invoice_id = inv.id 
                        WHERE inv.student_id = s.id 
                          AND inv.year = :year_paid
                    ),
                    0
                ) AS total_paid
             FROM students s
             LEFT JOIN invoices i 
                ON s.id = i.student_id 
               AND i.year = :year_invoice
             WHERE s.status = 'active'
             GROUP BY s.id
             ORDER BY total_paid DESC
             LIMIT :limit"
        );

        $stmt->bindValue('year_paid', $year, \PDO::PARAM_INT);
        $stmt->bindValue('year_invoice', $year, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function getClassStats(int $year): array
    {
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->query("SELECT DISTINCT grade, class FROM students WHERE class != '' ORDER BY grade, class");
        $classes = $stmt->fetchAll();

        $result = [];

        foreach ($classes as $row) {
            $grade = $row['grade'];
            $class = $row['class'];
            
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
                'khoi'         => $grade,
                'class'        => $class,
                'student_count' => (int)($data['student_count'] ?? 0),
                'invoice_count' => (int)($data['invoice_count'] ?? 0),
                'total_amount'  => (int)($data['total_amount'] ?? 0),
                'collected'     => (int)($data['collected'] ?? 0),
            ];
        }

        return $result;
    }

    public function exportAction(): void
    {
        $this->requireLogin();
        
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        $pdo = \App\Core\Database::getConnection();
        
        // Lấy tất cả thanh toán trong tháng
        $stmt = $pdo->prepare("SELECT p.*, i.invoice_code, s.full_name, s.student_code, s.grade, s.class
            FROM payments p
            JOIN invoices i ON p.invoice_id = i.id
            JOIN students s ON i.student_id = s.id
            WHERE MONTH(p.paid_at) = :month AND YEAR(p.paid_at) = :year
            ORDER BY p.paid_at DESC");
        $stmt->execute(['month' => $month, 'year' => $year]);
        $payments = $stmt->fetchAll();

        // Tạo CSV - dùng dấu chấm phẩy để Excel (ngôn ngữ Việt) tách cột đúng
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="thu_chi_' . $month . '_' . $year . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        $delimiter = ';';
        
        fputcsv(
            $output,
            ['STT', 'Ngày', 'Mã phiếu', 'Mã HS', 'Họ tên', 'Khối', 'Lớp', 'Số tiền', 'Phương thức', 'Mã tham chiếu'],
            $delimiter
        );
        
        $total = 0;
        foreach ($payments as $index => $p) {
            $total += (int)$p['amount'];
            fputcsv(
                $output,
                [
                    $index + 1,
                    $p['paid_at'],
                    $p['invoice_code'],
                    $p['student_code'],
                    $p['full_name'],
                    $p['grade'] ?? '',
                    $p['class'],
                    $p['amount'],
                    $p['payment_method'],
                    $p['bank_ref'] ?? '',
                ],
                $delimiter
            );
        }
        
        fputcsv($output, ['', '', '', '', '', '', 'Tổng cộng:', $total, '', ''], $delimiter);
        
        fclose($output);
        exit;
    }
}
