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
        $this->requireLogin();

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
        $this->requireLogin();

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
                $invoiceData = [
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

                HoaDon::create($invoiceData);
                $this->redirect('index.php?controller=invoice&action=index');
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
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $id ? HoaDon::find($id) : null;

        if (!$invoice) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        $this->renderPlain('hoadon/view', [
            'pageTitle' => 'Chi tiết phiếu báo thu',
            'invoice' => $invoice,
        ]);
    }

    public function pdfAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $id ? HoaDon::find($id) : null;

        if (!$invoice) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        $schoolName = \App\Core\Config::SCHOOL_NAME ?? 'Trường học';
        $schoolAddress = \App\Core\Config::SCHOOL_ADDRESS ?? '';
        $schoolPhone = \App\Core\Config::SCHOOL_PHONE ?? '';
        
        $totalText = numberToVietnameseWords((int)$invoice['total_amount']);
        $totalFormatted = number_format((int)$invoice['total_amount'], 0, ',', '.');
        $totalAmount = (int)$invoice['total_amount'];
        $qrPayment = getVietQRPaymentInfo($totalAmount, $invoice['invoice_code'] ?? '');

        ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu báo thu - <?= htmlspecialchars($invoice['invoice_code']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Khổ A4 */
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 12px; 
            line-height: 1.4; 
            padding: 15px; 
            background: #f5f5f5; 
        }
        
        /* Khung chính */
        .invoice { 
            width: 180mm; 
            min-height: 267mm;
            margin: 0 auto; 
            background: #fff; 
            padding: 15mm; 
            border: 1px solid #000;
        }
        
        /* Header - Căn trái */
        .header { 
            margin-bottom: 12px; 
            padding-bottom: 8px;
            border-bottom: 1px solid #333;
        }
        .header .school-name { 
            font-size: 14px; 
            font-weight: bold; 
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .header .school-info { font-size: 11px; }
        
        /* Title - Căn giữa */
        .title { 
            text-align: center; 
            margin-bottom: 12px; 
        }
        .title h2 { 
            font-size: 16px; 
            font-weight: bold; 
            text-transform: uppercase; 
            margin-bottom: 3px; 
        }
        .title .subtitle { 
            font-size: 12px; 
            font-style: italic;
        }
        
        /* Info - Thông tin học sinh */
        .info { 
            margin-bottom: 10px; 
            font-size: 11px; 
        }
        .info-row { 
            display: table; 
            width: 100%; 
        }
        .info-cell { 
            display: table-cell; 
            padding: 2px 0; 
        }
        
        /* Table - Bảng phí */
        table.items { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
            font-size: 11px; 
        }
        table.items th, table.items td { 
            border: 1px solid #000; 
            padding: 5px 6px; 
        }
        table.items th { 
            background: #eee; 
            text-align: center; 
            font-weight: bold; 
        }
        table.items td:nth-child(1) { 
            text-align: center; 
            width: 30px; 
        }
        table.items td:nth-child(2) { text-align: left; }
        table.items td:nth-child(3) { text-align: left; width: 80px; }
        table.items td:nth-child(4) { 
            text-align: right; 
            width: 80px; 
        }
        
        /* Summary - Tổng kết */
        .summary { 
            margin-bottom: 8px; 
            font-size: 11px; 
        }
        .summary-row { 
            display: table; 
            width: 100%; 
        }
        .summary-cell { 
            display: table-cell; 
            padding: 2px 0; 
        }
        .summary-cell:first-child { 
            text-align: right; 
            width: 140px; 
        }
        .summary-cell:last-child { 
            text-align: right; 
        }
        .summary-total { 
            border-top: 1px solid #000; 
            font-weight: bold; 
        }
        
        /* Amount text */
        .amount-text { 
            font-size: 11px; 
            margin-bottom: 5px; 
        }
        .note-red { 
            font-size: 10px; 
            color: #cc0000; 
            font-style: italic;
            margin-bottom: 10px;
        }
        
        /* QR Payment */
        .qr-section { 
            border: 1px dashed #198754; 
            border-radius: 4px; 
            padding: 10px; 
            margin-bottom: 10px; 
        }
        .qr-title { 
            font-weight: bold; 
            color: #198754; 
            font-size: 11px; 
            text-align: center; 
            margin-bottom: 8px; 
            text-transform: uppercase;
        }
        .qr-content { 
            display: table; 
            width: 100%; 
        }
        .qr-cell { 
            display: table-cell; 
            vertical-align: middle; 
        }
        .qr-cell img { 
            width: 150px; 
            height: 150px; 
        }
        .qr-cell:last-child { 
            padding-left: 15px; 
            font-size: 10px; 
        }
        .qr-row { 
            margin-bottom: 3px; 
        }
        
        /* Footer */
        .footer { 
            display: table; 
            width: 100%; 
            margin-top: 20px; 
            font-size: 10px; 
        }
        .footer-cell { 
            display: table-cell; 
            width: 50%; 
            text-align: center; 
            vertical-align: bottom; 
        }
        .sign-line { 
            border-top: 1px solid #000; 
            padding-top: 25px; 
        }
        
        /* Toolbar */
        .toolbar { 
            position: fixed; 
            top: 5px; 
            left: 0; 
            right: 0; 
            display: flex; 
            justify-content: center; 
            gap: 8px; 
            z-index: 1000; 
        }
        .toolbar .btn { 
            padding: 6px 12px; 
            border-radius: 4px; 
            border: 1px solid #ccc; 
            background: #fff; 
            cursor: pointer; 
            text-decoration: none; 
            color: #000; 
            font-size: 12px; 
        }
        .toolbar .btn-primary { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .toolbar .btn-success { background: #198754; color: #fff; border-color: #198754; }
        .toolbar .btn-secondary { background: #6c757d; color: #fff; border-color: #6c757d; }
        
        @media print {
            body { padding: 0; background: #fff; }
            .invoice { 
                border: none; 
                width: 100%;
                min-height: auto;
                padding: 0;
                margin: 0;
            }
            .toolbar { display: none; }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()" class="btn btn-primary">In</button>
        <a href="index.php?controller=hoadon&action=downloadPdf&id=<?= $invoice['id'] ?>" class="btn btn-success" target="_blank">Tải PDF</a>
        <a href="index.php?controller=invoice&action=index" class="btn btn-secondary">Quay lại</a>
    </div>

    <div class="invoice">
        <!-- Header - Căn trái -->
        <div class="header">
            <div class="school-name"><?= htmlspecialchars($schoolName) ?></div>
            <div class="school-info">Địa chỉ: <?= htmlspecialchars($schoolAddress) ?></div>
            <div class="school-info">Điện thoại: <?= htmlspecialchars($schoolPhone) ?></div>
        </div>

        <!-- Title - Căn giữa -->
        <div class="title">
            <h2>GIẤY BÁO THU HỌC PHÍ</h2>
            <div class="subtitle">Tháng <?= $invoice['month'] ?>/<?= $invoice['year'] ?> - Niên học <?= $invoice['year'] ?>-<?= (int)$invoice['year'] + 1 ?></div>
        </div>

        <!-- Info - Thông tin học sinh -->
        <div class="info">
            <div class="info-row">
                <div class="info-cell"><strong>Họ tên:</strong> <?= htmlspecialchars($invoice['student_name']) ?></div>
                <div class="info-cell"><strong>Mã HS:</strong> <?= htmlspecialchars($invoice['student_code']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-cell"><strong>Lớp:</strong> <?= htmlspecialchars($invoice['class']) ?></div>
                <div class="info-cell"><strong>Mã phiếu:</strong> <?= htmlspecialchars($invoice['invoice_code']) ?></div>
            </div>
        </div>

        <!-- Table - Bảng phí -->
        <table class="items">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Nội dung</th>
                    <th>Ghi chú</th>
                    <th>Số tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice['items'] as $index => $item): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($item['description'] ?? $item['fee_category_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($item['note'] ?? '') ?></td>
                    <td><?= number_format((int)$item['amount'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary - Tổng kết -->
        <div class="summary">
            <div class="summary-row">
                <div class="summary-cell">Công nợ kỳ này:</div>
                <div class="summary-cell"><?= $totalFormatted ?></div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Nợ kỳ trước:</div>
                <div class="summary-cell">0</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Khấu trừ:</div>
                <div class="summary-cell">0</div>
            </div>
            <div class="summary-row summary-total">
                <div class="summary-cell">TỔNG CỘNG:</div>
                <div class="summary-cell"><?= $totalFormatted ?></div>
            </div>
        </div>

        <!-- Amount text -->
        <div class="amount-text"><strong>Viết bằng chữ:</strong> <?= htmlspecialchars($totalText) ?></div>
        
        <?php if (!empty($invoice['note'])): ?>
        <div class="amount-text"><strong>Ghi chú:</strong> <?= htmlspecialchars($invoice['note']) ?></div>
        <?php endif; ?>

        <div class="note-red">* Vui lòng nhập đúng số tiền khi thanh toán qua QRCode</div>

        <!-- QR Payment -->
        <?php if (!empty($qrPayment) && !empty($qrPayment['qr_image_url'])): ?>
        <div class="qr-section">
            <div class="qr-title">THANH TOÁN QUA QR CODE</div>
            <div class="qr-content">
                <div class="qr-cell"><img src="<?= htmlspecialchars($qrPayment['qr_image_url']) ?>" alt="QR"></div>
                <div class="qr-cell">
                    <div class="qr-row"><strong>Ngân hàng:</strong> <?= htmlspecialchars($qrPayment['bank_id'] ?? '') ?></div>
                    <div class="qr-row"><strong>Số TK:</strong> <?= htmlspecialchars($qrPayment['account_number'] ?? '') ?></div>
                    <div class="qr-row"><strong>Chủ TK:</strong> <?= htmlspecialchars($qrPayment['account_name'] ?? '') ?></div>
                    <div class="qr-row"><strong>Số tiền:</strong> <strong style="color:#198754"><?= $totalFormatted ?></strong></div>
                    <div class="qr-row"><strong>Nội dung:</strong> <?= htmlspecialchars($invoice['invoice_code']) ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-cell"><div class="sign-line">Ngày...... tháng...... năm......<br><em>KT. HIỆU TRƯỞNG</em></div></div>
            <div class="footer-cell"><div class="sign-line">Ngày...... tháng...... năm......<br><em>NGƯỜI LẬP PHIẾU</em></div></div>
        </div>
    </div>
</body>
</html>
        <?php
    }

    public function downloadPdfAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $id ? HoaDon::find($id) : null;

        if (!$invoice) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        // Load helper và gọi hàm tạo PDF
        require_once __DIR__ . '/../helpers/tcpdf_invoice.php';
        
        $items = $invoice['items'] ?? [];
        $qrPayment = getVietQRPaymentInfo((int)$invoice['total_amount'], $invoice['invoice_code'] ?? '');
        
        // Gọi hàm generateInvoicePDF để tạo PDF
        generateInvoicePDF($invoice, $items, $qrPayment);
    }

    public function deleteAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            HoaDon::delete($id);
        }

        $this->redirect('index.php?controller=invoice&action=index');
    }

    public function markPaidAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $pdo = \App\Core\Database::getConnection();
            // Bảng trong database vẫn là "invoices"
            $stmt = $pdo->prepare('UPDATE invoices SET status = :status WHERE id = :id');
            $stmt->execute(['status' => 'paid', 'id' => $id]);
        }

        $this->redirect('index.php?controller=invoice&action=index');
    }

    public function editAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $id ? HoaDon::find($id) : null;

        if (!$invoice) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu báo thu.';
            return;
        }

        $error = null;
        $students = HocSinh::getAllForSelect();
        $feeCategories = LoaiKhoanThu::getAll();

        $data = [
            'student_id' => (string)$invoice['student_id'],
            'month' => (string)$invoice['month'],
            'year' => (string)$invoice['year'],
            'issue_date' => $invoice['issue_date'],
            'due_date' => $invoice['due_date'] ?? '',
            'status' => (string)$invoice['status'],
            'note' => (string)($invoice['note'] ?? ''),
        ];

        $items = [];
        if (!empty($invoice['items'])) {
            foreach ($invoice['items'] as $item) {
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
            $data['status'] = $_POST['status'] ?? 'pending';

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
                $invoiceData = [
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

                HoaDon::update($id, $invoiceData);
                $this->redirect('index.php?controller=invoice&action=index');
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

    public function bulkCreateAction(): void
    {
        $this->requireLogin();

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

                            $invoiceId = (int)$pdo->lastInsertId();

                            if (!empty($items)) {
                                $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, fee_category_id, description, amount, sort_order)
                                    VALUES (:invoice_id, :fee_category_id, :description, :amount, :sort_order)");
                                foreach ($items as $index => $item) {
                                    $itemStmt->execute([
                                        'invoice_id' => $invoiceId,
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
                        $this->redirect('index.php?controller=invoice&action=index');
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
