<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ThanhToan;
use App\Models\HoaDon;

class ThanhToanController extends BaseController
{
    public function indexAction(): void
    {
        $this->requireAdmin();

        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = ThanhToan::paginate($q, $page, 20);
        $stats = ThanhToan::getStats();

        $this->render('thanhtoan/index', [
            'pageTitle' => 'Thanh toán',
            'q' => $q,
            'result' => $result,
            'stats' => $stats,
        ]);
    }

    public function createAction(): void
    {
        $this->requireAdmin();

        $error = null;
        $invoices = HoaDon::getPendingInvoices();

        // Form data (keys match form field names)
        $data = [
            'invoice_id' => '',
            'payment_method' => 'cash',
            'amount' => '',
            'paid_at' => date('Y-m-d H:i:s'),
            'bank_ref' => '',
            'note' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $v) {
                $data[$k] = trim($_POST[$k] ?? $v);
            }

            $amount = (int)$data['amount'];

            if ($data['invoice_id'] === '') {
                $error = 'Vui lòng chọn phiếu báo thu.';
            } elseif ($amount <= 0) {
                $error = 'Số tiền phải lớn hơn 0.';
            } else {
                $thanhtoanData = [
                    'invoice_id' => $data['invoice_id'],
                    // Lưu xuống DB với tên cột hiện tại
                    'thanhtoan_method' => $data['payment_method'],
                    'amount' => $amount,
                    'paid_at' => $data['paid_at'],
                    'bank_ref' => $data['bank_ref'],
                    'note' => $data['note'],
                ];

                ThanhToan::create($thanhtoanData);
                $this->redirect('index.php?controller=thanhtoan&action=index');
            }
        }

        $this->render('thanhtoan/create', [
            'pageTitle' => 'Thêm thanh toán',
            'error' => $error,
            'data' => $data,
            'invoices' => $invoices,
        ]);
    }

    public function viewAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $thanhtoan = $id ? ThanhToan::find($id) : null;

        if (!$thanhtoan) {
            http_response_code(404);
            echo 'Không tìm thấy thanh toán.';
            return;
        }

        $this->render('thanhtoan/view', [
            'pageTitle' => 'Chi tiết thanh toán',
            'thanhtoan' => $thanhtoan,
        ]);
    }

    public function editAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $thanhtoan = $id ? ThanhToan::find($id) : null;

        if (!$thanhtoan) {
            http_response_code(404);
            echo 'Không tìm thấy thanh toán.';
            return;
        }

        $error = null;
        $invoices = HoaDon::getPendingInvoices();

        // Form data (keys match form field names)
        $data = [
            'invoice_id' => (string)$thanhtoan['invoice_id'],
            'payment_method' => (string)$thanhtoan['thanhtoan_method'],
            'amount' => (string)$thanhtoan['amount'],
            'paid_at' => substr($thanhtoan['paid_at'], 0, 16),
            'bank_ref' => (string)($thanhtoan['bank_ref'] ?? ''),
            'note' => (string)($thanhtoan['note'] ?? ''),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $v) {
                $data[$k] = trim($_POST[$k] ?? $v);
            }

            $amount = (int)$data['amount'];

            if ($data['invoice_id'] === '') {
                $error = 'Vui lòng chọn phiếu báo thu.';
            } elseif ($amount <= 0) {
                $error = 'Số tiền phải lớn hơn 0.';
            } else {
                $thanhtoanData = [
                    'invoice_id' => $data['invoice_id'],
                    'thanhtoan_method' => $data['payment_method'],
                    'amount' => $amount,
                    'paid_at' => $data['paid_at'],
                    'bank_ref' => $data['bank_ref'],
                    'note' => $data['note'],
                ];

                ThanhToan::update($id, $thanhtoanData);
                $this->redirect('index.php?controller=thanhtoan&action=index');
            }
        }

        $this->render('thanhtoan/edit', [
            'pageTitle' => 'Sửa thanh toán',
            'error' => $error,
            'id' => $id,
            'data' => $data,
            'invoices' => $invoices,
        ]);
    }

    public function deleteAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            ThanhToan::delete($id);
        }

        $this->redirect('index.php?controller=thanhtoan&action=index');
    }

    /**
     * AJAX: Tìm kiếm tự động thanh toán
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
            SELECT t.id, t.amount, t.thanhtoan_method, t.paid_at,
                   i.invoice_code, i.total_amount as invoice_total,
                   s.full_name as student_name, s.student_code
            FROM thanhtoans t
            LEFT JOIN invoices i ON t.invoice_id = i.id
            LEFT JOIN students s ON i.student_id = s.id
            WHERE i.invoice_code LIKE :q1 OR s.full_name LIKE :q2 OR s.student_code LIKE :q3 OR t.bank_ref LIKE :q4
            ORDER BY t.paid_at DESC
            LIMIT 10
        ");
        $stmt->execute(['q1' => "%$q%", 'q2' => "%$q%", 'q3' => "%$q%", 'q4' => "%$q%"]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $results]);
    }
}
