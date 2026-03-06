<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\HoaDon;
use App\Models\HocSinh;
use App\Models\LoaiKhoanThu;

class HoaDonController extends BaseController
{
    public function indexAction(): void
    {
        $this->requireAdmin();

        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = HoaDon::paginate($q, $page, 20);

        $this->render('hoadon/index', [
            'pageTitle' => 'Phiếu báo thu',
            'q' => $q,
            'result' => $result,
        ]);
    }

    public function createAction(): void
    {
        $this->requireAdmin();

        $error = null;
        $students = HocSinh::getAllForSelect();
        $feeCategories = LoaiKhoanThu::getAll();

        $data = [
            'student_id' => '',
            'month' => date('m'),
            'year' => date('Y'),
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'note' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $v) {
                $data[$k] = trim($_POST[$k] ?? $v);
            }

            $items = [];
            $totalAmount = 0;
            if (!empty($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    $amount = (int)($item['amount'] ?? 0);
                    if ($amount > 0) {
                        $items[] = [
                            'fee_category_id' => $item['fee_category_id'] ?? null,
                            'description' => $item['description'] ?? '',
                            'amount' => $amount,
                        ];
                        $totalAmount += $amount;
                    }
                }
            }

            if ($data['student_id'] === '') {
                $error = 'Vui lòng chọn học sinh.';
            } elseif (empty($items)) {
                $error = 'Vui lòng thêm ít nhất một khoản thu.';
            } else {
                $hoadonData = [
                    'invoice_code' => HoaDon::generateCode(),
                    'student_id' => $data['student_id'],
                    'month' => $data['month'],
                    'year' => $data['year'],
                    'issue_date' => $data['issue_date'],
                    'due_date' => $data['due_date'],
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'note' => $data['note'],
                    'items' => $items,
                ];

                HoaDon::create($hoadonData);
                $this->redirect('index.php?controller=hoadon&action=index');
            }
        }

        $this->render('hoadon/create', [
            'pageTitle' => 'Tạo phiếu báo thu',
            'error' => $error,
            'data' => $data,
            'students' => $students,
            'feeCategories' => $feeCategories,
        ]);
    }

    public function viewAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $hoadon = $id ? HoaDon::find($id) : null;

        if (!$hoadon) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        $this->renderPlain('hoadon/view', [
            'pageTitle' => 'Chi tiết phiếu báo thu',
            'invoice' => $hoadon,
        ]);
    }

    public function pdfAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $hoadon = $id ? HoaDon::find($id) : null;

        if (!$hoadon) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        // Load helper và gọi hàm tạo PDF - Tải file PDF trực tiếp
        require_once __DIR__ . '/../helpers/tcpdf_invoice.php';
        
        $items = $hoadon['items'] ?? [];
        $qrPayment = getVietQRPaymentInfo((int)$hoadon['total_amount'], $hoadon['invoice_code'] ?? '');
        
        // Gọi hàm generateInvoicePDFNew để tạo và tải PDF
        generateInvoicePDFNew($hoadon, $items, $qrPayment);
    }

    public function downloadPdfAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $hoadon = $id ? HoaDon::find($id) : null;

        if (!$hoadon) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        // Load helper và gọi hàm tạo PDF
        require_once __DIR__ . '/../helpers/tcpdf_invoice.php';
        
        $items = $hoadon['items'] ?? [];
        $qrPayment = getVietQRPaymentInfo((int)$hoadon['total_amount'], $hoadon['invoice_code'] ?? '');
        
        // Gọi hàm generateInvoicePDFNew để tạo PDF
        generateInvoicePDFNew($hoadon, $items, $qrPayment);
    }

    /**
     * Xuất PDF "Giấy báo thu và thanh toán" (layout giống mẫu A4)
     */
    public function giayBaoThuPdfAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $hoadon = $id ? HoaDon::find($id) : null;

        if (!$hoadon) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        require_once __DIR__ . '/../helpers/tcpdf_giay_bao_thu.php';
        require_once __DIR__ . '/../helpers/number_to_words.php';

        $items = $hoadon['items'] ?? [];
        $totalAmount = (int)($hoadon['total_amount'] ?? 0);
        
        // Lấy trạng thái thanh toán (đã tính trong HoaDon::find)
        $status = $hoadon['status'] ?? 'pending';
        $isPaid = ($status === 'paid');
        
        // Lấy thông tin QR thanh toán nếu chưa thanh toán
        $qrPayment = [];
        if (!$isPaid && $totalAmount > 0) {
            require_once __DIR__ . '/../helpers/vietqr.php';
            $qrPayment = getVietQRPaymentInfo($totalAmount, $hoadon['invoice_code'] ?? '');
        }

        $dob = $hoadon['dob'] ?? '';
        if ($dob && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            $dob = date('d/m/Y', strtotime($dob));
        }

        $month = (int)($hoadon['month'] ?? date('n'));
        $year = (int)($hoadon['year'] ?? date('Y'));
        $periodText = "Cả Năm, Niên học {$year} - " . ($year + 1);

        $itemsForPdf = [];
        foreach ($items as $it) {
            $itemsForPdf[] = [
                'description' => $it['description'] ?? '',
                'note' => $it['note'] ?? '',
                'amount' => (int)($it['amount'] ?? 0),
            ];
        }

        $data = [
            'school_name' => \App\Core\Config::SCHOOL_NAME,
            'school_address' => \App\Core\Config::SCHOOL_ADDRESS,
            'school_phone' => \App\Core\Config::SCHOOL_PHONE,
            'student_name' => $hoadon['student_name'] ?? '',
            'class_name' => $hoadon['class'] ?? '',
            'dob' => $dob,
            'student_code' => $hoadon['student_code'] ?? '',
            'meal_days' => (int)($hoadon['meal_days'] ?? 0),
            'items' => $itemsForPdf,
            'current_debt' => $totalAmount,
            'prev_debt' => 0,
            'prev_discount' => 0,
            'current_discount' => 0,
            'total' => $totalAmount,
            'amount_in_words' => numberToVietnameseWords($totalAmount),
            'print_date' => date('d/m/Y'),
            'creator' => $_SESSION['user_full_name'] ?? '',
            'period_text' => $periodText,
            'status' => $status,
            'qrPayment' => $qrPayment,
        ];

        generateGiayBaoThuPDF($data);
    }

    public function deleteAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            HoaDon::delete($id);
        }

        $this->redirect('index.php?controller=hoadon&action=index');
    }

    public function markPaidAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $pdo = \App\Core\Database::getConnection();
            
            // Lấy thông tin hóa đơn
            $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $invoice = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($invoice) {
                $pdo->beginTransaction();
                try {
                    // Cập nhật trạng thái hóa đơn
                    $updateStmt = $pdo->prepare('UPDATE invoices SET status = :status WHERE id = :id');
                    $updateStmt->execute(['status' => 'paid', 'id' => $id]);
                    
                    // Kiểm tra xem đã có thanh toán chưa
                    $checkStmt = $pdo->prepare('SELECT SUM(amount) FROM payments WHERE invoice_id = :invoice_id');
                    $checkStmt->execute(['invoice_id' => $id]);
                    $paidAmount = (int)$checkStmt->fetchColumn();
                    
                    // Nếu chưa có thanh toán, tạo bản ghi thanh toán
                    if ($paidAmount === 0) {
                        $paymentStmt = $pdo->prepare("INSERT INTO payments (invoice_id, payment_method, amount, paid_at, bank_ref, note)
                            VALUES (:invoice_id, :payment_method, :amount, :paid_at, :bank_ref, :note)");
                        $paymentStmt->execute([
                            'invoice_id' => $id,
                            'payment_method' => 'cash',
                            'amount' => $invoice['total_amount'],
                            'paid_at' => date('Y-m-d H:i:s'),
                            'bank_ref' => 'MANUAL-' . $invoice['invoice_code'],
                            'note' => 'Thanh toán thủ công',
                        ]);
                    }
                    
                    $pdo->commit();
                } catch (\Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
        }

        $this->redirect('index.php?controller=hoadon&action=index');
    }

    public function editAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $hoadon = $id ? HoaDon::find($id) : null;

        if (!$hoadon) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        $error = null;
        $students = HocSinh::getAllForSelect();
        $feeCategories = LoaiKhoanThu::getAll();

        $data = [
            'student_id' => (string)$hoadon['student_id'],
            'month' => (string)$hoadon['month'],
            'year' => (string)$hoadon['year'],
            'issue_date' => $hoadon['issue_date'],
            'due_date' => $hoadon['due_date'] ?? '',
            'status' => (string)$hoadon['status'],
            'note' => (string)($hoadon['note'] ?? ''),
        ];

        $items = [];
        if (!empty($hoadon['items'])) {
            foreach ($hoadon['items'] as $item) {
                $items[] = [
                    'fee_category_id' => $item['fee_category_id'] ?? '',
                    'description' => $item['description'] ?? '',
                    'amount' => (string)$item['amount'],
                ];
            }
        }
        if (empty($items)) {
            $items[] = ['fee_category_id' => '', 'description' => '', 'amount' => ''];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $v) {
                if ($k !== 'status') {
                    $data[$k] = trim($_POST[$k] ?? $v);
                }
            }
            $validStatuses = ['pending', 'paid', 'partial', 'cancelled'];
            $data['status'] = isset($_POST['status']) && in_array($_POST['status'], $validStatuses, true) 
                ? $_POST['status'] 
                : 'pending';

            $items = [];
            $totalAmount = 0;
            if (!empty($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    $amount = (int)($item['amount'] ?? 0);
                    if ($amount > 0) {
                        $items[] = [
                            'fee_category_id' => $item['fee_category_id'] ?? null,
                            'description' => $item['description'] ?? '',
                            'amount' => $amount,
                        ];
                        $totalAmount += $amount;
                    }
                }
            }

            if ($data['student_id'] === '') {
                $error = 'Vui lòng chọn học sinh.';
            } elseif (empty($items)) {
                $error = 'Vui lòng thêm ít nhất một khoản thu.';
            } else {
                $hoadonData = [
                    'student_id' => $data['student_id'],
                    'month' => $data['month'],
                    'year' => $data['year'],
                    'issue_date' => $data['issue_date'],
                    'due_date' => $data['due_date'],
                    'total_amount' => $totalAmount,
                    'status' => $data['status'],
                    'note' => $data['note'],
                    'items' => $items,
                ];

                HoaDon::update($id, $hoadonData);
                $this->redirect('index.php?controller=hoadon&action=index');
            }
        }

        $this->render('hoadon/edit', [
            'pageTitle' => 'Sửa phiếu báo thu',
            'error' => $error,
            'id' => $id,
            'data' => $data,
            'items' => $items,
            'students' => $students,
            'feeCategories' => $feeCategories,
        ]);
    }

    /**
     * AJAX: Tìm kiếm tự động phiếu báo thu
     */
    public function searchAutocompleteAction(): void
    {
        $this->requireAdmin();

        header('Content-Type: application/json; charset=utf-8');

        $q = trim($_GET['q'] ?? '');

        if (strlen($q) < 1) {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT h.id, h.invoice_code, h.total_amount, h.status, h.month, h.year,
                   s.full_name as student_name, s.student_code, s.class
            FROM invoices h
            JOIN students s ON h.student_id = s.id
            WHERE h.invoice_code LIKE :q1 OR s.full_name LIKE :q2 OR s.student_code LIKE :q3
            ORDER BY h.created_at DESC
            LIMIT 10
        ");
        $stmt->execute(['q1' => "%$q%", 'q2' => "%$q%", 'q3' => "%$q%"]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $results]);
    }

    public function bulkCreateAction(): void
    {
        $this->requireAdmin();

        $error = null;
        $success = 0;
        $classes = HocSinh::getClasses();
        $feeCategories = LoaiKhoanThu::getAll();

        $data = [
            'class' => '',
            'month' => date('m'),
            'year' => date('Y'),
            'fee_category_ids' => [],
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'note' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data['class'] = trim($_POST['class'] ?? '');
            $data['month'] = trim($_POST['month'] ?? date('m'));
            $data['year'] = trim($_POST['year'] ?? date('Y'));
            $data['fee_category_ids'] = $_POST['fee_category_ids'] ?? [];
            $data['issue_date'] = trim($_POST['issue_date'] ?? date('Y-m-d'));
            $data['due_date'] = trim($_POST['due_date'] ?? '');
            $data['note'] = trim($_POST['note'] ?? '');

            if ($data['class'] === '') {
                $error = 'Vui lòng chọn lớp.';
            } elseif (empty($data['fee_category_ids'])) {
                $error = 'Vui lòng chọn ít nhất một khoản thu.';
            } else {
                $students = HocSinh::getByClass($data['class']);
                
                if (empty($students)) {
                    $error = 'Không có học sinh nào trong lớp này.';
                } else {
                    $pdo = \App\Core\Database::getConnection();
                    $pdo->beginTransaction();

                    try {
                        $selectedCategories = [];
                        foreach ($feeCategories as $fc) {
                            if (in_array($fc['id'], $data['fee_category_ids'])) {
                                $selectedCategories[] = $fc;
                            }
                        }

                        foreach ($students as $student) {
                            $totalAmount = 0;
                            $items = [];
                            foreach ($selectedCategories as $fc) {
                                $amount = (int)$fc['default_amount'];
                                $totalAmount += $amount;
                                $items[] = [
                                    'fee_category_id' => $fc['id'],
                                    'description' => $fc['name'],
                                    'amount' => $amount,
                                ];
                            }

                            // Bảng trong database là "invoices"
                            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_code, student_id, month, year, issue_date, due_date, total_amount, status, note)
                                VALUES (:invoice_code, :student_id, :month, :year, :issue_date, :due_date, :total_amount, :status, :note)");
                            $stmt->execute([
                                'invoice_code' => HoaDon::generateCode(),
                                'student_id' => $student['id'],
                                'month' => $data['month'],
                                'year' => $data['year'],
                                'issue_date' => $data['issue_date'],
                                'due_date' => $data['due_date'] ?: null,
                                'total_amount' => $totalAmount,
                                'status' => 'pending',
                                'note' => $data['note'] ?: null,
                            ]);

                            $hoadonId = (int)$pdo->lastInsertId();

                            if (!empty($items)) {
                                $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, fee_category_id, description, amount, sort_order)
                                    VALUES (:invoice_id, :fee_category_id, :description, :amount, :sort_order)");
                                foreach ($items as $index => $item) {
                                    $itemStmt->execute([
                                        'invoice_id' => $hoadonId,
                                        'fee_category_id' => $item['fee_category_id'],
                                        'description' => $item['description'],
                                        'amount' => $item['amount'],
                                        'sort_order' => $index,
                                    ]);
                                }
                            }

                            $success++;
                        }

                        $pdo->commit();
                        $this->redirect('index.php?controller=hoadon&action=index');
                    } catch (\Exception $e) {
                        $pdo->rollBack();
                        $error = 'Lỗi khi tạo phiếu: ' . $e->getMessage();
                    }
                }
            }
        }

        $this->render('hoadon/bulk_create', [
            'pageTitle' => 'Tạo phiếu hàng loạt',
            'error' => $error,
            'success' => $success,
            'data' => $data,
            'classes' => $classes,
            'feeCategories' => $feeCategories,
        ]);
    }
}
