<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Payment as PaymentModel;
use App\Models\HoaDon;
use App\Models\AuditLog;

/**
 * Payment Matching Controller
 * Xử lý webhook thanh toán từ ngân hàng và auto-matching với hóa đơn
 */
class PaymentMatchingController extends BaseController
{
    /**
     * Webhook endpoint - Nhận dữ liệu từ ngân hàng
     * POST /payment-matching/webhook
     */
    public function webhookAction(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // Get raw JSON input
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (empty($data)) {
            $this->json(['success' => false, 'error' => 'Invalid JSON'], 400);
            return;
        }
        
        // Extract payment data from webhook (flexible format)
        $paymentData = $this->normalizeWebhookData($data);
        
        // Validate required fields
        if (empty($paymentData['trans_id']) || empty($paymentData['amount'])) {
            $this->json(['success' => false, 'error' => 'Missing trans_id or amount'], 400);
            return;
        }
        
        // Check if payment already exists (idempotent)
        $existing = PaymentModel::findByTransId($paymentData['trans_id']);
        if ($existing) {
            $this->json([
                'success' => true,
                'message' => 'Payment already processed',
                'data' => [
                    'id' => $existing['id'],
                    'match_status' => $existing['match_status'],
                    'matched_hoadon_id' => $existing['matched_hoadon_id'],
                ]
            ], 200);
            return;
        }
        
        // Save payment to database
        $paymentData['raw_payload'] = json_encode($data);
        $paymentId = PaymentModel::create($paymentData);
        
        // Auto-match with hoadon
        $matchResult = $this->autoMatchPayment($paymentId, $paymentData);
        
        $this->json([
            'success' => true,
            'message' => $matchResult['matched'] ? 'Payment matched successfully' : 'Payment saved but not matched',
            'data' => [
                'id' => $paymentId,
                'trans_id' => $paymentData['trans_id'],
                'amount' => $paymentData['amount'],
                'match_status' => $matchResult['match_status'],
                'matched_hoadon_id' => $matchResult['hoadon_id'] ?? null,
                'hoadon_code' => $matchResult['hoadon_code'] ?? null,
            ]
        ], 201);
    }

    /**
     * Normalize webhook data from different bank formats
     */
    private function normalizeWebhookData(array $data): array
    {
        // VietQR format
        if (isset($data['transId'])) {
            return [
                'trans_id' => (string)$data['transId'],
                'amount' => (int)($data['amount'] ?? 0),
                'content' => trim($data['description'] ?? $data['content'] ?? ''),
                'bank_time' => !empty($data['transDate']) ? $this->formatBankDate($data['transDate']) : null,
                'account_no' => $data['accountNumber'] ?? $data['account_no'] ?? '',
                'account_name' => $data['accountName'] ?? $data['account_name'] ?? '',
                'bank_id' => $data['bankId'] ?? $data['bank_id'] ?? '',
            ];
        }
        
        // Standard format
        return [
            'trans_id' => (string)($data['trans_id'] ?? $data['transId'] ?? ''),
            'amount' => (int)($data['amount'] ?? 0),
            'content' => trim($data['content'] ?? $data['description'] ?? ''),
            'bank_time' => !empty($data['bank_time']) ? $this->formatBankDate($data['bank_time']) : null,
            'account_no' => $data['account_no'] ?? $data['accountNumber'] ?? '',
            'account_name' => $data['account_name'] ?? $data['accountName'] ?? '',
            'bank_id' => $data['bank_id'] ?? $data['bankId'] ?? '',
        ];
    }

    /**
     * Format bank date to MySQL datetime
     */
    private function formatBankDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        // If it's already a valid date format
        if (strtotime($date)) {
            return date('Y-m-d H:i:s', strtotime($date));
        }
        
        // VietQR timestamp format (milliseconds)
        if (is_numeric($date) && strlen($date) > 10) {
            $date = substr($date, 0, 10);
            return date('Y-m-d H:i:s', (int)$date);
        }
        
        return null;
    }

    /**
     * Auto-match payment with hoadon
     * 1. Find pending hoadon with matching invoice_code in content
     * 2. Check if amount matches
     * 3. Update hoadon status to PAID
     */
    private function autoMatchPayment(int $paymentId, array $paymentData): array
    {
        $pdo = \App\Core\Database::getConnection();
        
        // Extract invoice code from content
        $content = $paymentData['content'] ?? '';
        $amount = $paymentData['amount'] ?? 0;
        
        // Find potential hoadon by invoice_code in content
        // Try different patterns: PT2026010001, HD001, etc.
        $patterns = [
            '/([A-Z]{1,2}\d{10,})/i',  // PT2026010001
            '/(HD\d+)/i',              // HD001
            '/(\d{10,})/',             // Just numbers
        ];
        
        $invoiceCode = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $invoiceCode = $matches[1];
                break;
            }
        }
        
        if (!$invoiceCode) {
            // Try to find by student code
            $patterns = [
                '/HS(\d+)/i',          // HS001
                '/([A-Z]{2}\d+)/i',   // AB001
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    // Look up student then find pending hoadon
                    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_code = :code LIMIT 1");
                    $stmt->execute(['code' => $matches[1]]);
                    $student = $stmt->fetch();
                    
                    if ($student) {
                        // Find pending hoadon for this student with matching amount
                        $stmt = $pdo->prepare("
                            SELECT id, invoice_code, total_amount 
                            FROM hoadon 
                            WHERE student_id = :student_id 
                            AND status IN ('pending', 'partial')
                            AND total_amount = :amount
                            ORDER BY created_at DESC 
                            LIMIT 1
                        ");
                        $stmt->execute(['student_id' => $student['id'], 'amount' => $amount]);
                        $hoadon = $stmt->fetch();
                        
                        if ($hoadon) {
                            $invoiceCode = $hoadon['invoice_code'];
                            break;
                        }
                    }
                }
            }
        }
        
        $matchStatus = 'UNMATCHED';
        $hoadonId = null;
        $hoadonCode = null;
        
        if ($invoiceCode) {
            // Find pending hoadon with matching invoice_code and amount
            $stmt = $pdo->prepare("
                SELECT id, invoice_code, total_amount, status 
                FROM hoadon 
                WHERE invoice_code = :code 
                AND status IN ('pending', 'partial')
                LIMIT 1
            ");
            $stmt->execute(['code' => $invoiceCode]);
            $hoadon = $stmt->fetch();
            
            if ($hoadon) {
                // Check if amount matches exactly or within tolerance (1000 VND)
                $amountDiff = abs($amount - (int)$hoadon['total_amount']);
                
                if ($amountDiff <= 1000 || $amount >= (int)$hoadon['total_amount']) {
                    // Match found - update hoadon status to PAID
                    $oldStatus = $hoadon['status'];
                    $newStatus = 'paid';
                    
                    $stmt = $pdo->prepare("UPDATE hoadon SET status = :status WHERE id = :id");
                    $stmt->execute(['status' => $newStatus, 'id' => $hoadon['id']]);
                    
                    // Update payment
                    PaymentModel::update($paymentId, [
                        'matched_hoadon_id' => $hoadon['id'],
                        'match_status' => 'MATCHED',
                        'matched_at' => date('Y-m-d H:i:s'),
                    ]);
                    
                    // Log audit
                    AuditLog::logStatusChange('hoadon', $hoadon['id'], $oldStatus, $newStatus, null, 'SYSTEM_AUTO_MATCH');
                    
                    $matchStatus = 'MATCHED';
                    $hoadonId = $hoadon['id'];
                    $hoadonCode = $hoadon['invoice_code'];
                }
            }
        }
        
        // If not matched, set as UNMATCHED
        if ($matchStatus === 'UNMATCHED') {
            PaymentModel::update($paymentId, [
                'match_status' => 'UNMATCHED',
            ]);
        }
        
        return [
            'matched' => $matchStatus === 'MATCHED',
            'match_status' => $matchStatus,
            'hoadon_id' => $hoadonId,
            'hoadon_code' => $hoadonCode,
        ];
    }

    /**
     * Danh sách payments chưa match
     * GET /payment-matching/unmatched
     */
    public function unmatchedAction(): void
    {
        $this->requireLogin();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        
        $result = PaymentModel::getUnmatched($page, $limit);
        
        $this->render('payment_matching/unmatched', [
            'pageTitle' => 'Payments chưa khớp',
            'payments' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    /**
     * Danh sách payments đã match
     * GET /payment-matching/matched
     */
    public function matchedAction(): void
    {
        $this->requireLogin();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        
        $result = PaymentModel::getMatched($page, $limit);
        
        $this->render('payment_matching/matched', [
            'pageTitle' => 'Payments đã khớp',
            'payments' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    /**
     * Tất cả payments
     * GET /payment-matching/index
     */
    public function indexAction(): void
    {
        $this->requireLogin();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        
        $filters = [
            'match_status' => $_GET['status'] ?? '',
        ];
        
        $result = PaymentModel::paginate('', $page, $limit, $filters);
        
        $this->render('payment_matching/index', [
            'pageTitle' => 'Quản lý thanh toán',
            'payments' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'totalPages' => $result['totalPages'],
            'filterStatus' => $filters['match_status'],
        ]);
    }

    /**
     * Match thủ công payment với hoadon
     * POST /payment-matching/match/{payment_id}/{hoadon_id}
     */
    public function matchAction(): void
    {
        $this->requireLogin();
        
        $paymentId = (int)($_GET['payment_id'] ?? 0);
        $hoadonId = (int)($_GET['hoadon_id'] ?? 0);
        
        if ($paymentId <= 0 || $hoadonId <= 0) {
            $_SESSION['error'] = 'Thiếu thông tin payment hoặc hóa đơn';
            $this->redirectToReferer();
            return;
        }
        
        $payment = PaymentModel::find($paymentId);
        if (!$payment) {
            $_SESSION['error'] = 'Không tìm thấy payment';
            $this->redirectToReferer();
            return;
        }
        
        $hoadon = HoaDon::find($hoadonId);
        if (!$hoadon) {
            $_SESSION['error'] = 'Không tìm thấy hóa đơn';
            $this->redirectToReferer();
            return;
        }
        
        // Update payment
        PaymentModel::update($paymentId, [
            'matched_hoadon_id' => $hoadonId,
            'match_status' => 'MATCHED',
            'matched_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Update hoadon status
        $oldStatus = $hoadon['status'];
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("UPDATE hoadon SET status = 'paid' WHERE id = :id");
        $stmt->execute(['id' => $hoadonId]);
        
        // Log audit
        $userId = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['user_full_name'] ?? 'Unknown';
        AuditLog::logStatusChange('hoadon', $hoadonId, $oldStatus, 'paid', $userId, $userName);
        
        $_SESSION['success'] = 'Đã khớp payment với hóa đơn ' . $hoadon['invoice_code'];
        $this->redirectToReferer();
    }

    /**
     * Bỏ khớp payment
     * POST /payment-matching/unmatch/{payment_id}
     */
    public function unmatchAction(): void
    {
        $this->requireLogin();
        
        $paymentId = (int)($_GET['payment_id'] ?? 0);
        
        if ($paymentId <= 0) {
            $_SESSION['error'] = 'Thiếu thông tin payment';
            $this->redirectToReferer();
            return;
        }
        
        $payment = PaymentModel::find($paymentId);
        if (!$payment || empty($payment['matched_hoadon_id'])) {
            $_SESSION['error'] = 'Payment chưa được khớp';
            $this->redirectToReferer();
            return;
        }
        
        $hoadonId = $payment['matched_hoadon_id'];
        $hoadon = HoaDon::find($hoadonId);
        
        // Update payment
        PaymentModel::update($paymentId, [
            'matched_hoadon_id' => null,
            'match_status' => 'UNMATCHED',
            'matched_at' => null,
        ]);
        
        // Update hoadon status back to pending
        if ($hoadon) {
            $oldStatus = $hoadon['status'];
            $pdo = \App\Core\Database::getConnection();
            $stmt = $pdo->prepare("UPDATE hoadon SET status = 'pending' WHERE id = :id");
            $stmt->execute(['id' => $hoadonId]);
            
            // Log audit
            $userId = $_SESSION['user_id'] ?? null;
            $userName = $_SESSION['user_full_name'] ?? 'Unknown';
            AuditLog::logStatusChange('hoadon', $hoadonId, $oldStatus, 'pending', $userId, $userName);
        }
        
        $_SESSION['success'] = 'Đã bỏ khớp payment';
        $this->redirectToReferer();
    }

    /**
     * Xem danh sách hóa đơn để match thủ công
     * GET /payment-matching/select-hoadon/{payment_id}
     */
    public function selectHoaDonAction(): void
    {
        $this->requireLogin();
        
        $paymentId = (int)($_GET['payment_id'] ?? 0);
        
        $payment = PaymentModel::find($paymentId);
        if (!$payment) {
            $_SESSION['error'] = 'Không tìm thấy payment';
            $this->redirectToReferer();
            return;
        }
        
        // Get all pending hoadon
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->query("
            SELECT h.*, s.full_name as student_name, s.student_code, s.class
            FROM hoadon h
            JOIN students s ON h.student_id = s.id
            WHERE h.status IN ('pending', 'partial')
            ORDER BY h.created_at DESC
            LIMIT 100
        ");
        $hoadons = $stmt->fetchAll();
        
        $this->render('payment_matching/select_hoadon', [
            'pageTitle' => 'Chọn hóa đơn để khớp',
            'payment' => $payment,
            'hoadons' => $hoadons,
        ]);
    }

    /**
     * Tìm kiếm hóa đơn cho match thủ công (AJAX)
     * GET /payment-matching/search-hoadon
     */
    public function searchHoaDonAction(): void
    {
        $this->requireLogin();
        
        header('Content-Type: application/json; charset=utf-8');
        
        $q = trim($_GET['q'] ?? '');
        
        $pdo = \App\Core\Database::getConnection();
        
        if (!empty($q)) {
            $stmt = $pdo->prepare("
                SELECT h.*, s.full_name as student_name, s.student_code, s.class
                FROM hoadon h
                JOIN students s ON h.student_id = s.id
                WHERE h.status IN ('pending', 'partial')
                AND (h.invoice_code LIKE :q OR s.full_name LIKE :q OR s.student_code LIKE :q)
                ORDER BY h.created_at DESC
                LIMIT 20
            ");
            $stmt->execute(['q' => "%$q%"]);
        } else {
            $stmt = $pdo->query("
                SELECT h.*, s.full_name as student_name, s.student_code, s.class
                FROM hoadon h
                JOIN students s ON h.student_id = s.id
                WHERE h.status IN ('pending', 'partial')
                ORDER BY h.created_at DESC
                LIMIT 20
            ");
        }
        
        $hoadons = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $hoadons], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Lịch sử thay đổi hóa đơn
     * GET /payment-matching/audit/{hoadon_id}
     */
    public function auditAction(): void
    {
        $this->requireLogin();
        
        $hoadonId = (int)($_GET['hoadon_id'] ?? 0);
        
        if ($hoadonId <= 0) {
            $_SESSION['error'] = 'Thiếu thông tin hóa đơn';
            $this->redirectToReferer();
            return;
        }
        
        $logs = AuditLog::getByEntity('hoadon', $hoadonId);
        
        $this->render('payment_matching/audit', [
            'pageTitle' => 'Lịch sử thay đổi',
            'logs' => $logs,
            'hoadon_id' => $hoadonId,
        ]);
    }

    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function redirectToReferer(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php?controller=payment-matching&action=index';
        header("Location: $referer");
        exit;
    }
}
