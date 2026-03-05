<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\LoaiKhoanThu;

class LoaiKhoanThuController extends BaseController
{
    public function indexAction(): void
    {
        $this->requireLogin();

        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = LoaiKhoanThu::paginate($q, $page, 20);

        $this->render('loaikhphi/index', [
            'pageTitle' => 'Khoản thu',
            'q' => $q,
            'result' => $result,
        ]);
    }

    public function createAction(): void
    {
        $this->requireLogin();

        $error = null;
        $data = [
            'name' => '',
            'description' => '',
            'default_amount' => '0',
            'unit' => 'month',
            'is_active' => '1',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $_) {
                $data[$k] = trim((string)($_POST[$k] ?? ''));
            }

            $data['unit'] = in_array($data['unit'], ['month', 'day', 'term', 'once'], true) ? $data['unit'] : 'month';
            $data['is_active'] = $data['is_active'] === '0' ? '0' : '1';

            if ($data['name'] === '') {
                $error = 'Vui lòng nhập tên khoản thu.';
            } else {
                LoaiKhoanThu::create($data);
                $this->redirect('index.php?controller=loaikhphi&action=index');
            }
        }

        $this->render('loaikhphi/create', [
            'pageTitle' => 'Tạo khoản thu',
            'error' => $error,
            'data' => $data,
        ]);
    }

    public function editAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $row = $id ? LoaiKhoanThu::find($id) : null;
        if (!$row) {
            http_response_code(404);
            echo 'Không tìm thấy khoản thu.';
            return;
        }

        $error = null;
        $data = [
            'name' => (string)$row['name'],
            'description' => (string)($row['description'] ?? ''),
            'default_amount' => (string)($row['default_amount'] ?? '0'),
            'unit' => (string)($row['unit'] ?? 'month'),
            'is_active' => (string)((int)($row['is_active'] ?? 1)),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $_) {
                $data[$k] = trim((string)($_POST[$k] ?? ''));
            }

            $data['unit'] = in_array($data['unit'], ['month', 'day', 'term', 'once'], true) ? $data['unit'] : 'month';
            $data['is_active'] = $data['is_active'] === '0' ? '0' : '1';

            if ($data['name'] === '') {
                $error = 'Vui lòng nhập tên khoản thu.';
            } else {
                LoaiKhoanThu::update($id, $data);
                $this->redirect('index.php?controller=loaikhphi&action=index');
            }
        }

        $this->render('loaikhphi/edit', [
            'pageTitle' => 'Sửa khoản thu',
            'error' => $error,
            'id' => $id,
            'data' => $data,
        ]);
    }

    public function deleteAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            LoaiKhoanThu::delete($id);
        }
        $this->redirect('index.php?controller=loaikhphi&action=index');
    }

    /**
     * AJAX: Tìm kiếm tự động khoản thu
     */
    public function searchAutocompleteAction(): void
    {
        $this->requireLogin();

        header('Content-Type: application/json; charset=utf-8');

        $q = trim($_GET['q'] ?? '');

        if (strlen($q) < 1) {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, name, description, default_amount, unit
            FROM fee_categories
            WHERE name LIKE :q
            ORDER BY name ASC
            LIMIT 10
        ");
        $stmt->execute(['q' => "%$q%"]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $results]);
    }
}

