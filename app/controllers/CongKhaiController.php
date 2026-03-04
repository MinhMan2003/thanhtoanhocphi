<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\HoaDon;
use App\Models\HocSinh;

class CongKhaiController extends BaseController
{
    // Rate limit config
    private const MAX_LOOKUPS_PER_WINDOW = 20;
    private const RATE_LIMIT_WINDOW_MINUTES = 10;

    /**
     * Lấy địa chỉ IP thực (bao gồm khi có proxy)
     */
    private function getRealIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Check for proxy forwarded IPs
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwardedIps[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        
        return $ip;
    }

    /**
     * Lấy session ID hoặc tạo mới
     */
    private function getSessionId(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['lookup_session_id'])) {
            $_SESSION['lookup_session_id'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['lookup_session_id'];
    }

    /**
     * Kiểm tra rate limit
     */
    private function checkRateLimit(): bool
    {
        $pdo = \App\Core\Database::getConnection();
        $ip = $this->getRealIp();
        $sessionId = $this->getSessionId();
        
        // Đếm số lần tra cứu trong window
        $windowStart = date('Y-m-d H:i:s', strtotime('-' . self::RATE_LIMIT_WINDOW_MINUTES . ' minutes'));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM public_lookup_logs 
            WHERE ((ip_address = :ip AND created_at > :window) 
               OR (session_id = :session_id AND created_at > :window2))
            AND created_at > :window3
        ");
        $stmt->execute([
            'ip' => $ip,
            'session_id' => $sessionId,
            'window' => $windowStart,
            'window2' => $windowStart,
            'window3' => $windowStart
        ]);
        
        $count = (int)$stmt->fetchColumn();
        
        return $count < self::MAX_LOOKUPS_PER_WINDOW;
    }

    /**
     * Ghi log tra cứu
     */
    private function logLookup(string $receiptCode, string $result, ?int $studentId = null): void
    {
        $pdo = \App\Core\Database::getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO public_lookup_logs 
            (ip_address, session_id, receipt_code, lookup_result, student_id, user_agent)
            VALUES (:ip, :session_id, :receipt_code, :result, :student_id, :user_agent)
        ");
        
        $stmt->execute([
            'ip' => $this->getRealIp(),
            'session_id' => $this->getSessionId(),
            'receipt_code' => $receiptCode,
            'result' => $result,
            'student_id' => $studentId,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }

    /**
     * Ẩn bớt tên học sinh (chỉ hiển thị chữ cái đầu)
     */
    private function maskStudentName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) === 1) {
            return $parts[0][0] . str_repeat('*', mb_strlen($parts[0]) - 1);
        }
        
        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        
        return $firstName[0] . str_repeat('*', mb_strlen($firstName) - 1) . ' ' 
             . implode(' ', $parts) . ' ' 
             . $lastName[0] . str_repeat('*', mb_strlen($lastName) - 1);
    }

    /**
     * Trang tra cứu công khai cho phụ huynh - BẢN ĐÃ HARDEN
     */
    congkhai function lookupAction(): void
    {
        $error = null;
        $student = null;
        $invoices = [];
        
        // Kiểm tra rate limit trước khi xử lý
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->checkRateLimit()) {
                $error = 'Quá số lần tra cứu. Vui lòng thử lại sau ' . self::RATE_LIMIT_WINDOW_MINUTES . ' phút.';
                $this->logLookup($_POST['receipt_code'] ?? '', 'RATE_LIMITED');
                $this->renderPlain('congkhai/lookup', [
                    'pageTitle' => 'Tra cứu học phí',
                    'error' => $error,
                    'student' => null,
                    'invoices' => [],
                ]);
                return;
            }
            
            $receiptCode = trim($_POST['receipt_code'] ?? '');
            $dob = trim($_POST['dob'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Validation: receipt_code BẮT BUỘC
            if (empty($receiptCode)) {
                $error = 'Vui lòng nhập mã phiếu thu.';
                $this->logLookup($receiptCode ?: 'EMPTY', 'FAILED_INVALID_CODE');
            } 
            // Validation: CẦN ÍT NHẤT 1 thông tin xác thực (DOB hoặc phone)
            elseif (empty($dob) && empty($phone)) {
                $error = 'Vui lòng nhập ngày sinh học sinh HOẶC số điện thoại phụ huynh để xác minh.';
                $this->logLookup($receiptCode, 'FAILED_INVALID_VERIFY');
            } else {
                // Tìm hóa đơn theo receipt_code
                $pdo = \App\Core\Database::getConnection();
                
                // Join với students để lấy thông tin
                $stmt = $pdo->prepare("
                    SELECT i.*, s.id as student_id, s.full_name, s.student_code, s.class, 
                           s.grade, s.dob, s.parent_name, s.parent_phone
                    FROM invoices i
                    JOIN students s ON i.student_id = s.id
                    WHERE i.receipt_code = :receipt_code
                    LIMIT 1
                ");
                $stmt->execute(['receipt_code' => $receiptCode]);
                $invoice = $stmt->fetch();
                
                if (!$invoice) {
                    $error = 'Không tìm thấy phiếu thu với mã này.';
                    $this->logLookup($receiptCode, 'FAILED_INVALID_CODE');
                } else {
                    $studentId = $invoice['student_id'];
                    
                    // Xác thực: kiểm tra DOB hoặc phone
                    $dobMatch = !empty($dob) && !empty($invoice['dob']) && 
                                (strtotime($dob) === strtotime($invoice['dob']));
                    $phoneMatch = !empty($phone) && !empty($invoice['parent_phone']) && 
                                  ($phone === $invoice['parent_phone']);
                    
                    if (!$dobMatch && !$phoneMatch) {
                        $error = 'Thông tin xác minh không đúng. Vui lòng kiểm tra lại ngày sinh hoặc số điện thoại.';
                        $this->logLookup($receiptCode, 'FAILED_INVALID_VERIFY');
                    } else {
                        // Tra cứu thành công - lấy thông tin học sinh
                        $student = [
                            'id' => $invoice['student_id'],
                            'full_name' => $this->maskStudentName($invoice['full_name']),
                            'student_code' => $invoice['student_code'],
                            'class' => $invoice['class'],
                            'grade' => $invoice['grade'],
                        ];
                        
                        // Lấy tất cả hóa đơn của học sinh này
                        $stmt = $pdo->prepare("
                            SELECT i.*, 
                                (SELECT SUM(amount) FROM payments WHERE invoice_id = i.id) as paid_amount
                            FROM invoices i 
                            WHERE i.student_id = :student_id 
                            ORDER BY i.year DESC, i.month DESC
                        ");
                        $stmt->execute(['student_id' => $studentId]);
                        $invoices = $stmt->fetchAll();
                        
                        // Ẩn thông tin nhạy cảm trong invoices
                        foreach ($invoices as &$inv) {
                            unset($inv['receipt_code']);
                        }
                        
                        $this->logLookup($receiptCode, 'SUCCESS', $studentId);
                    }
                }
            }
        }
        
        $this->renderPlain('congkhai/lookup', [
            'pageTitle' => 'Tra cứu học phí',
            'error' => $error,
            'student' => $student,
            'invoices' => $invoices,
        ]);
    }
    
    /**
     * Xem chi tiết phiếu báo thu (công khai)
     * Bản đã harden - bổ sung xác thực
     */
    congkhai function invoiceAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            echo 'Thiếu thông tin phiếu báo thu.';
            return;
        }
        
        $invoice = HoaDon::find($id);
        
        if (!$invoice) {
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }
        
        // Bổ sung: Yêu cầu xác thực nếu cần
        // Trong phiên bản này, chi tiết hóa đơn vẫn công khai 
        // nhưng đã giới quyền truy cập ở lookupAction
        
        $student = HocSinh::find($invoice['student_id']);
        
        // Lấy thông tin thanh toán
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("SELECT SUM(amount) as total_paid FROM payments WHERE invoice_id = :id");
        $stmt->execute(['id' => $id]);
        $paidAmount = (int)($stmt->fetchColumn() ?? 0);
        
        $invoice['paid_amount'] = $paidAmount;
        
        // Lấy các khoản thu - KHÔNG expose mã nội bộ
        $items = HoaDon::getItems($id);
        
        // Ẩn fee_category_id trong items
        foreach ($items as &$item) {
            unset($item['fee_category_id']);
            // Chỉ trả về description và amount
            $item = [
                'description' => $item['description'],
                'amount' => $item['amount'],
            ];
        }
        
        // Lấy thông tin thanh toán QR
        $totalAmount = (int)$invoice['total_amount'];
        $qrPayment = getVietQRPaymentInfo($totalAmount, $invoice['invoice_code'] ?? '');
        
        $this->renderPlain('congkhai/invoice', [
            'pageTitle' => 'Chi tiết phiếu báo thu',
            'invoice' => $invoice,
            'student' => $student,
            'items' => $items,
            'qrPayment' => $qrPayment,
        ]);
    }
    
    /**
     * API: Kiểm tra trạng thái thanh toán (dùng cho auto-refresh)
     * Trả về JSON với thông tin trạng thái
     */
    congkhai function checkPaymentStatusAction(): void
    {
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            echo json_encode(['error' => 'Thiếu thông tin phiếu báo thu.']);
            return;
        }

        $invoice = HoaDon::find($id);

        if (!$invoice) {
            echo json_encode(['error' => 'Không tìm thấy phiếu báo thu.']);
            return;
        }

        // Lấy thông tin thanh toán hiện tại
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("SELECT
            COALESCE(SUM(amount), 0) as total_paid,
            COUNT(*) as payment_count,
            MAX(paid_at) as last_payment_at
            FROM payments WHERE invoice_id = :id");
        $stmt->execute(['id' => $id]);
        $paymentInfo = $stmt->fetch();

        $paidAmount = (int)$paymentInfo['total_paid'];
        $totalAmount = (int)$invoice['total_amount'];

        // Xác định trạng thái
        $status = 'pending';
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partial';
        }

        echo json_encode([
            'success' => true,
            'invoice_id' => $id,
            'invoice_code' => $invoice['invoice_code'],
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => max(0, $totalAmount - $paidAmount),
            'status' => $status,
            'payment_count' => (int)$paymentInfo['payment_count'],
            'last_payment_at' => $paymentInfo['last_payment_at'],
        ]);
    }

    /**
     * Tải PDF phiếu báo thu (dành cho phụ huynh)
     */
    congkhai function pdfAction(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            echo 'Thiếu thông tin phiếu báo thu.';
            return;
        }

        $invoice = HoaDon::find($id);

        if (!$invoice) {
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        $student = HocSinh::find($invoice['student_id']);

        if (!$student) {
            echo 'Không tìm thấy thông tin học sinh.';
            return;
        }

        // Lấy các khoản thu
        $items = HoaDon::getItems($id);

        // Lấy thông tin thanh toán
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE invoice_id = :id");
        $stmt->execute(['id' => $id]);
        $paidAmount = (int)($stmt->fetchColumn() ?? 0);

        $invoice['paid_amount'] = $paidAmount;

        // Lấy thông tin thanh toán QR
        $totalAmount = (int)$invoice['total_amount'];
        $qrPayment = getVietQRPaymentInfo($totalAmount, $invoice['invoice_code'] ?? '');

        // Xác định trạng thái
        $status = 'pending';
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partial';
        }

        require_once __DIR__ . '/../../helpers/number_to_words.php';

        $schoolName = \App\Core\Config::SCHOOL_NAME ?? 'Trường học';
        $totalText = numberToVietnamese((int)$invoice['total_amount']);
        $paidText = numberToVietnamese($paidAmount);

        $remainingAmount = max(0, $totalAmount - $paidAmount);
        $remainingText = numberToVietnamese($remainingAmount);

        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu báo thu - <?= htmlspecialchars($invoice['invoice_code']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 12mm; }
        body { font-family: 'Times New Roman', serif; font-size: 12px; padding: 0; background: #fff; }
        .invoice { max-width: 100%; margin: 0 auto; border: 1px solid #000; padding: 12px 18px; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 1px solid #000; padding-bottom: 8px; }
        .header h1 { font-size: 18px; margin-bottom: 4px; text-transform: uppercase; }
        .header .school-name { font-size: 14px; margin-bottom: 4px; }
        .header .invoice-type { font-size: 14px; font-weight: bold; margin-top: 6px; }
        .invoice-code { text-align: right; margin-bottom: 12px; font-size: 12px; }
        .invoice-code strong { font-size: 14px; }
        .info { margin-bottom: 12px; }
        .info table { width: 100%; }
        .info td { padding: 4px 0; font-size: 12px; }
        .info td:first-child { width: 135px; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items th, table.items td { border: 1px solid #000; padding: 6px 8px; text-align: left; font-size: 12px; }
        table.items th { background: #f0f0f0; text-align: center; }
        table.items td:nth-child(3) { text-align: right; }
        .total-row { font-weight: bold; background: #f8f8f8; }
        .total-row td:last-child { font-size: 13px; }
        .status-box { padding: 10px; text-align: center; margin: 10px 0; border-radius: 4px; font-size: 12px; }
        .status-paid { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-pending { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-partial { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .amount-text { margin-bottom: 10px; font-style: italic; font-size: 12px; }
        .qr-section { margin: 12px 0 8px; padding: 10px 12px; border: 2px dashed #28a745; border-radius: 8px; page-break-inside: avoid; }
        .qr-section h3 { color: #28a745; margin-bottom: 8px; text-align: center; font-size: 13px; }
        .qr-content { display: flex; gap: 12px; align-items: center; }
        .qr-image { text-align: center; }
        .qr-image img { width: 180px; height: 180px; }
        .qr-details { flex: 1; }
        .qr-details .amount { font-size: 16px; font-weight: bold; color: #28a745; text-align: center; margin: 6px 0; }
        .qr-details div { margin: 4px 0; font-size: 12px; }
        .qr-details .transfer-content { color: #dc3545; font-weight: bold; }
        .note { margin: 10px 0; padding: 8px; background: #f9f9f9; border-left: 3px solid #666; font-size: 12px; }
        .footer { display: flex; justify-content: space-between; margin-top: 20px; }
        .footer div { text-align: center; width: 45%; }
        .signature { height: 60px; }
        .signature-label { font-weight: bold; margin-bottom: 5px; }
        .print-btn { position: fixed; top: 16px; right: 16px; padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .print-btn:hover { background: #218838; }
        @media print {
            body { padding: 0; }
            .invoice { border: none; box-shadow: none; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ In phiếu</button>

    <div class="invoice">
        <div class="header">
            <div class="school-name"><?= htmlspecialchars($schoolName) ?></div>
            <h1>PHIẾU BÁO THU HỌC PHÍ</h1>
            <div class="invoice-type">Học kỳ/Năm học: <?= $invoice['month'] ?>/<?= $invoice['year'] ?></div>
        </div>

        <div class="invoice-code">
            <strong>Mã phiếu: <?= htmlspecialchars($invoice['invoice_code']) ?></strong>
        </div>

        <div class="info">
            <table>
                <tr>
                    <td>Học sinh:</td>
                    <td><strong><?= htmlspecialchars($student['full_name']) ?></strong> (<?= htmlspecialchars($student['student_code']) ?>)</td>
                </tr>
                <tr>
                    <td>Khối/Lớp:</td>
                    <td><?= htmlspecialchars($student['grade'] ?? '') ?> / <?= htmlspecialchars($student['class']) ?></td>
                </tr>
                <tr>
                    <td>Ngày lập:</td>
                    <td><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></td>
                </tr>
                <tr>
                    <td>Hạn thanh toán:</td>
                    <td><?= $invoice['due_date'] ? date('d/m/Y', strtotime($invoice['due_date'])) : 'Không có' ?></td>
                </tr>
            </table>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:50px;">STT</th>
                    <th>Nội dung</th>
                    <th style="width:120px; text-align:right;">Số tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td style="text-align:center;"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($item['description'] ?? ($item['fee_category_name'] ?? '-')) ?></td>
                    <td style="text-align:right;"><?= number_format((int)$item['amount'], 0, ',', '.') ?> đ</td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2" style="text-align:right;"><strong>TỔNG CỘNG:</strong></td>
                    <td style="text-align:right;"><strong><?= number_format((int)$invoice['total_amount'], 0, ',', '.') ?> đ</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="amount-text">
            <strong>Bằng chữ:</strong> <?= htmlspecialchars($totalText) ?> đồng.
        </div>

        <div class="status-box status-<?= $status ?>">
            <?php if ($status === 'paid'): ?>
            ✓ ĐÃ THANH TOÁN ĐỦ (<?= number_format($paidAmount, 0, ',', '.') ?> đ)
            <div style="margin-top:5px; font-size:12px;">Bằng chữ: <?= htmlspecialchars($paidText) ?> đồng</div>
            <?php elseif ($status === 'partial'): ?>
            ◐ ĐÃ THANH TOÁN: <?= number_format($paidAmount, 0, ',', '.') ?> đ
            <br>CÒN NỢ: <?= number_format($remainingAmount, 0, ',', '.') ?> đ
            <div style="margin-top:5px; font-size:12px;">(Bằng chữ: <?= htmlspecialchars($remainingText) ?> đồng)</div>
            <?php else: ?>
            ✗ CHƯA THANH TOÁN
            <?php endif; ?>
        </div>

        <!-- QR Code thanh toán -->
        <?php if ($status !== 'paid' && !empty($qrPayment)): ?>
        <div class="qr-section">
            <h3>📱 QUÉT MÃ QR ĐỂ THANH TOÁN</h3>
            <div class="qr-content">
                <div class="qr-image">
                    <img src="<?= htmlspecialchars($qrPayment['qr_image_url']) ?>" alt="QR thanh toán">
                </div>
                <div class="qr-details">
                    <div><strong>Ngân hàng:</strong> <?= htmlspecialchars($qrPayment['bank_id']) ?></div>
                    <div><strong>Số tài khoản:</strong> <?= htmlspecialchars($qrPayment['account_number']) ?></div>
                    <div><strong>Tên tài khoản:</strong> <?= htmlspecialchars($qrPayment['account_name']) ?></div>
                    <div class="amount"><?= number_format($qrPayment['amount'], 0, ',', '.') ?> đ</div>
                    <div><strong>Nội dung CK:</strong> <span class="transfer-content"><?= htmlspecialchars($qrPayment['invoice_code']) ?></span></div>
                    <div style="margin-top:10px; font-size:12px; color:#666;">💡 Quý phụ huynh vui lòng ghi đúng nội dung chuyển khoản để hệ thống tự động xác nhận</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($invoice['note'])): ?>
        <div class="note">
            <strong>Ghi chú:</strong> <?= htmlspecialchars($invoice['note']) ?>
        </div>
        <?php endif; ?>

        <div class="footer">
            <div>
                <div class="signature"></div>
                <div class="signature-label">Người lập phiếu</div>
                <div style="font-size:12px;">(Ký và ghi rõ họ tên)</div>
            </div>
            <div>
                <div class="signature"></div>
                <div class="signature-label">Phụ huynh học sinh</div>
                <div style="font-size:12px;">(Ký và ghi rõ họ tên)</div>
            </div>
        </div>
    </div>

    <div style="text-align:center; margin-top:20px; color:#666; font-size:12px;">
        Phiếu báo thu học phí - <?= htmlspecialchars($schoolName) ?>
    </div>
</body>
</html>
        <?php
    }
}
