<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\HocSinhPortal;

class PortalController
{
    /**
     * Trang chủ portal - form tra cứu
     */
    public function indexAction(): void
    {
        $error = '';
        $student = null;
        $invoices = [];
        
        // Kiểm tra nếu đã đăng nhập portal từ trước (qua session)
        if (!empty($_SESSION['portal_student_id'])) {
            $studentId = (int)$_SESSION['portal_student_id'];
            $student = HocSinhPortal::findById($studentId);
            if ($student) {
                $invoices = HocSinhPortal::getInvoices($studentId);
            }
        }
        
        // Nếu submit form đăng nhập mới
        if (!$student && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['student_code']) && !empty($_POST['dob'])) {
            $studentCode = $_POST['student_code'] ?? '';
            $dob = $_POST['dob'] ?? '';
            
            $student = HocSinhPortal::lookup($studentCode, $dob);
            
            if ($student) {
                // Lưu vào session để giữ thông tin
                $_SESSION['portal_student_id'] = $student['id'];
                $_SESSION['portal_student_code'] = $student['student_code'];
                $_SESSION['portal_student_name'] = $student['full_name'];
                
                $invoices = HocSinhPortal::getInvoices($student['id']);
            } else {
                $error = 'Không tìm thấy học sinh với mã và ngày sinh này. Vui lòng kiểm tra lại.';
            }
        }
        
        // Hiển thị view
        $baseUrl = $this->getBaseUrl();
        $pageTitle = 'Tra cứu thông tin học sinh';
        
        include __DIR__ . '/../views/portal/index.php';
    }
    
    /**
     * Xem chi tiết hóa đơn
     */
    public function invoiceAction(): void
    {
        // Kiểm tra đăng nhập session
        if (empty($_SESSION['portal_student_id'])) {
            $this->redirect('index.php?controller=portal&action=index');
            return;
        }
        
        $studentId = (int)$_SESSION['portal_student_id'];
        $invoiceId = (int)($_GET['id'] ?? 0);
        
        if (!$invoiceId) {
            $this->redirect('index.php?controller=portal&action=index');
            return;
        }
        
        $invoice = HocSinhPortal::getInvoiceDetail($invoiceId, $studentId);
        
        if (!$invoice) {
            echo 'Không tìm thấy hóa đơn.';
            return;
        }
        
        // Tạo QR payment nếu chưa thanh toán
        $qrPayment = [];
        if ($invoice['status'] !== 'paid' && $invoice['total_amount'] > 0) {
            require_once __DIR__ . '/../helpers/vietqr.php';
            $qrPayment = getVietQRPaymentInfo(
                (int)$invoice['total_amount'], 
                $invoice['invoice_code'] ?? ''
            );
        }
        
        $baseUrl = $this->getBaseUrl();
        $pageTitle = 'Chi tiết hóa đơn';
        
        include __DIR__ . '/../views/portal/invoice.php';
    }
    
    /**
     * Đăng xuất khỏi portal
     */
    public function logoutAction(): void
    {
        unset($_SESSION['portal_student_id']);
        unset($_SESSION['portal_student_code']);
        unset($_SESSION['portal_student_name']);
        
        $this->redirect('index.php?controller=portal&action=index');
    }
    
    /**
     * Xem bảng điểm
     */
    public function scoresAction(): void
    {
        // Kiểm tra đăng nhập session
        if (empty($_SESSION['portal_student_id'])) {
            $this->redirect('index.php?controller=portal&action=index');
            return;
        }
        
        $studentId = (int)$_SESSION['portal_student_id'];
        
        // Lấy tham lọc học kỳ và năm học
        $semester = $_GET['semester'] ?? '';
        $schoolYear = $_GET['school_year'] ?? '';
        
        // Lấy danh sách học kỳ có điểm
        $semesters = \App\Models\Score::getSemesters($studentId);
        
        // Lấy điểm theo môn học (điểm trung bình)
        $scoreData = \App\Models\Score::getAverageBySubject($studentId, $semester, $schoolYear);
        
        // Lấy chi tiết điểm các loại
        $scoreDetails = \App\Models\Score::getByStudent($studentId, $semester, $schoolYear);
        
        // Nhóm điểm theo môn
        $scoresBySubject = [];
        foreach ($scoreDetails as $detail) {
            $subjectKey = $detail['subject'];
            if (!isset($scoresBySubject[$subjectKey])) {
                $scoresBySubject[$subjectKey] = [
                    'subject_name' => $detail['subject_name'] ?? $detail['subject'],
                    'subject_code' => $detail['subject'],
                    'scores' => []
                ];
            }
            $scoresBySubject[$subjectKey]['scores'][] = $detail;
        }
        
        $baseUrl = $this->getBaseUrl();
        $pageTitle = 'Bảng điểm';
        
        include __DIR__ . '/../views/portal/scores.php';
    }
    
    /**
     * Lấy base URL
     */
    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return rtrim("$protocol://$host$path", '/');
    }
    
    /**
     * Chuyển hướng
     */
    private function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
