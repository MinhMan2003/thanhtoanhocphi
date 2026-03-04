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
        $this->requireLogin();

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
        $this->requireLogin();

        $error = null;
        $invoices = HoaDon::getPendingInvoices();

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
                $paymentData = [
                    'invoice_id' => $data['invoice_id'],
                    'payment_method' => $data['payment_method'],
                    'amount' => $amount,
                    'paid_at' => $data['paid_at'],
                    'bank_ref' => $data['bank_ref'],
                    'note' => $data['note'],
                ];

                ThanhToan::create($paymentData);
                $this->redirect('index.php?controller=payment&action=index');
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
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $payment = $id ? ThanhToan::find($id) : null;

        if (!$payment) {
            http_response_code(404);
            echo 'Không tìm thấy thanh toán.';
            return;
        }

        $this->render('thanhtoan/view', [
            'pageTitle' => 'Chi tiết thanh toán',
            'payment' => $payment,
        ]);
    }

    public function editAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $payment = $id ? ThanhToan::find($id) : null;

        if (!$payment) {
            http_response_code(404);
            echo 'Không tìm thấy thanh toán.';
            return;
        }

        $error = null;
        $invoices = HoaDon::getPendingInvoices();

        $data = [
            'invoice_id' => (string)$payment['invoice_id'],
            'payment_method' => (string)$payment['payment_method'],
            'amount' => (string)$payment['amount'],
            'paid_at' => substr($payment['paid_at'], 0, 16),
            'bank_ref' => (string)($payment['bank_ref'] ?? ''),
            'note' => (string)($payment['note'] ?? ''),
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
                $paymentData = [
                    'invoice_id' => $data['invoice_id'],
                    'payment_method' => $data['payment_method'],
                    'amount' => $amount,
                    'paid_at' => $data['paid_at'],
                    'bank_ref' => $data['bank_ref'],
                    'note' => $data['note'],
                ];

                ThanhToan::update($id, $paymentData);
                $this->redirect('index.php?controller=payment&action=index');
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
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            ThanhToan::delete($id);
        }

        $this->redirect('index.php?controller=payment&action=index');
    }
}
