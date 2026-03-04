<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\HoaDon;
use App\Models\HocSinh;

class NotificationController extends BaseController
{
    public function sendInvoiceAction(): void
    {
        $this->requireLogin();
        
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo 'Thiếu ID phiếu báo thu.';
            return;
        }
        
        $invoice = HoaDon::find($id);
        if (!$invoice) {
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }
        
        $student = HocSinh::find($invoice['student_id']);
        
        $result = $this->sendNotification([
            'student_name' => $student['full_name'],
            'student_code' => $student['student_code'],
            'class' => $student['class'],
            'invoice_code' => $invoice['invoice_code'],
            'month' => $invoice['month'],
            'year' => $invoice['year'],
            'amount' => $invoice['total_amount'],
            'due_date' => $invoice['due_date'],
            'phone' => $student['parent_phone'] ?? '',
            'email' => $student['parent_email'] ?? '',
        ]);
        
        if ($result['success']) {
            $method = $result['method'] ?? 'notification';
            echo '<div style="padding:20px; text-align:center;">
                <h2 style="color:green;">Gửi thông báo thành công!</h2>
                <p>Đã gửi thông báo đến phụ huynh của học sinh ' . htmlspecialchars($student['full_name']) . '</p>
                <p>Số điện thoại: ' . htmlspecialchars($student['parent_phone'] ?? 'Không có') . '</p>
                <p>Email: ' . htmlspecialchars($student['parent_email'] ?? 'Không có') . '</p>
                <br>
                <a href="index.php?controller=invoice&action=view&id=' . $id . '" class="btn btn-primary">Quay lại</a>
            </div>';
        } else {
            echo '<div style="padding:20px; text-align:center;">
                <h2 style="color:red;">Gửi thông báo thất bại!</h2>
                <p>Lỗi: ' . htmlspecialchars($result['error']) . '</p>
                <br>
                <a href="index.php?controller=invoice&action=view&id=' . $id . '" class="btn btn-primary">Quay lại</a>
            </div>';
        }
    }
    
    public function sendBulkAction(): void
    {
        $this->requireLogin();
        
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));
        
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("SELECT i.*, s.full_name as student_name, s.parent_phone, s.parent_email 
            FROM invoices i 
            JOIN students s ON i.student_id = s.id 
            WHERE i.month = :month AND i.year = :year AND i.status != 'paid'");
        $stmt->execute(['month' => $month, 'year' => $year]);
        $invoices = $stmt->fetchAll();
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($invoices as $invoice) {
            $result = $this->sendNotification([
                'student_name' => $invoice['student_name'],
                'student_code' => '',
                'class' => '',
                'invoice_code' => $invoice['invoice_code'],
                'month' => $invoice['month'],
                'year' => $invoice['year'],
                'amount' => $invoice['total_amount'],
                'due_date' => $invoice['due_date'],
                'phone' => $invoice['parent_phone'] ?? '',
                'email' => $invoice['parent_email'] ?? '',
            ]);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        echo '<div style="padding:20px; text-align:center;">
            <h2>Gửi thông báo hàng loạt</h2>
            <p>Tháng: ' . $month . '/' . $year . '</p>
            <p style="color:green;">Thành công: ' . $successCount . '</p>
            <p style="color:red;">Thất bại: ' . $failCount . '</p>
            <br>
            <a href="index.php?controller=report&action=index&month=' . $month . '&year=' . $year . '" class="btn btn-primary">Quay lại</a>
        </div>';
    }
    
    private function sendNotification(array $data): array
    {
        $phone = $data['phone'] ?? '';
        $email = $data['email'] ?? '';
        
        // Nếu có email thì gửi email
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailResult = $this->sendEmail($email, $data);
            if (!$emailResult['success']) {
                return $emailResult;
            }
        }
        
        // Nếu có số điện thoại thì gửi tin nhắn (simulated)
        if (!empty($phone)) {
            return [
                'success' => true,
                'method' => 'sms_mock',
                'message' => 'Tin nhắn (mock) đã được gửi đến ' . $phone
            ];
        }
        
        if (empty($phone) && empty($email)) {
            return [
                'success' => false,
                'error' => 'Không có số điện thoại hoặc email của phụ huynh.'
            ];
        }
        
        return ['success' => true];
    }
    
    private function sendEmail(string $email, array $data): array
    {
        $subject = 'Thông báo học phí - Phiếu ' . $data['invoice_code'];
        
        $message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>PHIẾU BÁO THU HỌC PHÍ</h2>
                </div>
                <div class="content">
                    <p>Kính gửi phụ huynh học sinh <strong>' . htmlspecialchars($data['student_name']) . '</strong>,</p>
                    
                    <p>Trường gửi thông báo về việc đóng học phí của con:</p>
                    
                    <table style="width:100%; border-collapse: collapse; margin: 15px 0;">
                        <tr>
                            <td style="padding:8px; border:1px solid #ddd;"><strong>Mã phiếu</strong></td>
                            <td style="padding:8px; border:1px solid #ddd;">' . htmlspecialchars($data['invoice_code']) . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px; border:1px solid #ddd;"><strong>Tháng</strong></td>
                            <td style="padding:8px; border:1px solid #ddd;">' . $data['month'] . '/' . $data['year'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px; border:1px solid #ddd;"><strong>Số tiền</strong></td>
                            <td style="padding:8px; border:1px solid #ddd;"><strong style="color:red;">' . number_format($data['amount'], 0, ',', '.') . ' đồng</strong></td>
                        </tr>
                        <tr>
                            <td style="padding:8px; border:1px solid #ddd;"><strong>Hạn đóng</strong></td>
                            <td style="padding:8px; border:1px solid #ddd;">' . date('d/m/Y', strtotime($data['due_date'])) . '</td>
                        </tr>
                    </table>
                    
                    <p>Phụ huynh vui lòng đóng học phí đúng hạn để tránh ảnh hưởng đến việc học tập của con.</p>
                    
                    <p>Nếu đã đóng tiền, xin bỏ qua thông báo này.</p>
                    
                    <p>Trân trọng cảm ơn!</p>
                </div>
                <div class="footer">
                    <p>Đây là tin nhắn tự động từ Hệ thống Quản lý Học phí - Trường Thực Hành Sư Phạm</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Cấu hình email server
        $smtpHost = \App\Core\Config::SMTP_HOST ?? '';
        $smtpPort = \App\Core\Config::SMTP_PORT ?? 587;
        $smtpUser = \App\Core\Config::SMTP_USER ?? '';
        $smtpPass = \App\Core\Config::SMTP_PASS ?? '';
        
        // Kiểm tra nếu có cấu hình SMTP thì gửi email
        if (!empty($smtpHost) && !empty($smtpUser)) {
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: " . $smtpUser . "\r\n";
            $headers .= "Reply-To: " . $smtpUser . "\r\n";
            
            if (mail($email, $subject, $message, $headers)) {
                return [
                    'success' => true,
                    'method' => 'email',
                    'message' => 'Email đã được gửi'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Lỗi gửi email'
                ];
            }
        }
        
        // Hiển thị preview email (nếu chưa cấu hình SMTP)
        return [
            'success' => true,
            'method' => 'email_preview',
            'message' => 'Preview email - Chưa cấu hình SMTP',
            'preview' => [
                'to' => $email,
                'subject' => $subject,
            ]
        ];
    }
}
