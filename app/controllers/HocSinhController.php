<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\HocSinh;

class HocSinhController extends BaseController
{
    public function indexAction(): void
    {
        $this->requireAdmin();

        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = HocSinh::paginate($q, $page, 20);

        $this->render('hocsinh/index', [
            'pageTitle' => 'Học sinh',
            'q' => $q,
            'result' => $result,
        ]);
    }

    public function createAction(): void
    {
        $this->requireAdmin();

        $error = null;
        $data = [
            'hocsinh_code' => '',
            'full_name' => '',
            'grade' => '',
            'class' => '',
            'dob' => '',
            'address' => '',
            'parent_name' => '',
            'parent_phone' => '',
            'parent_email' => '',
            'status' => 'active',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $_) {
                $data[$k] = trim((string)($_POST[$k] ?? ''));
            }
            $data['status'] = in_array($data['status'], ['active', 'inactive'], true) ? $data['status'] : 'active';

            if ($data['hocsinh_code'] === '' || $data['full_name'] === '' || $data['class'] === '') {
                $error = 'Vui lòng nhập Mã học sinh, Họ tên và Lớp.';
            } else {
                HocSinh::create($data);
                $this->redirect('index.php?controller=hocsinh&action=index');
            }
        }

        $this->render('hocsinh/create', [
            'pageTitle' => 'Thêm học sinh',
            'error' => $error,
            'data' => $data,
        ]);
    }

    public function editAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $hocsinh = $id ? HocSinh::find($id) : null;
        if (!$hocsinh) {
            http_response_code(404);
            echo 'Không tìm thấy học sinh.';
            return;
        }

        $error = null;
        $data = [
            'hocsinh_code' => (string)$hocsinh['hocsinh_code'],
            'full_name' => (string)$hocsinh['full_name'],
            'grade' => (string)($hocsinh['grade'] ?? ''),
            'class' => (string)$hocsinh['class'],
            'dob' => (string)($hocsinh['dob'] ?? ''),
            'address' => (string)($hocsinh['address'] ?? ''),
            'parent_name' => (string)($hocsinh['parent_name'] ?? ''),
            'parent_phone' => (string)($hocsinh['parent_phone'] ?? ''),
            'parent_email' => (string)($hocsinh['parent_email'] ?? ''),
            'status' => (string)($hocsinh['status'] ?? 'active'),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $_) {
                $data[$k] = trim((string)($_POST[$k] ?? ''));
            }
            $data['status'] = in_array($data['status'], ['active', 'inactive'], true) ? $data['status'] : 'active';

            if ($data['hocsinh_code'] === '' || $data['full_name'] === '' || $data['class'] === '') {
                $error = 'Vui lòng nhập Mã học sinh, Họ tên và Lớp.';
            } else {
                HocSinh::update($id, $data);
                $this->redirect('index.php?controller=hocsinh&action=index');
            }
        }

        $this->render('hocsinh/edit', [
            'pageTitle' => 'Sửa học sinh',
            'error' => $error,
            'id' => $id,
            'data' => $data,
        ]);
    }

    public function deleteAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            HocSinh::delete($id);
        }

        $this->redirect('index.php?controller=hocsinh&action=index');
    }

    public function viewAction(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $hocsinh = $id ? HocSinh::find($id) : null;

        if (!$hocsinh) {
            http_response_code(404);
            echo 'Không tìm thấy học sinh.';
            return;
        }

        $invoices = HocSinh::getInvoices($id);

        $this->render('hocsinh/view', [
            'pageTitle' => 'Chi tiết học sinh',
            'hocsinh' => $hocsinh,
            'invoices' => $invoices,
        ]);
    }

    public function importAction(): void
    {
        $this->requireAdmin();

        $error = null;
        $success = 0;
        $errors = [];
        $preview = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check if file was uploaded
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Vui lòng chọn file CSV hoặc Excel.';
            } else {
                $file = $_FILES['file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                // Parse CSV file
                $hocsinh = [];
                $handle = fopen($file['tmp_name'], 'r');

                if ($handle !== false) {
                    // Read header row
                    $headers = fgetcsv($handle, 0, ',', '"', '\\');
                    if ($headers === false) {
                        $error = 'File không hợp lệ hoặc trống.';
                    } else {
                        // Map headers to lowercase keys
                        $headerMap = [];
                        foreach ($headers as $i => $h) {
                            $h = strtolower(trim($h));
                            $headerMap[$h] = $i;
                        }

                        // Check required columns
                        $requiredCols = ['hocsinh_code', 'full_name', 'class'];
                        foreach ($requiredCols as $col) {
                            if (!isset($headerMap[$col])) {
                                $error = "Thiếu cột bắt buộc: $col";
                                break;
                            }
                        }

                        if ($error === null) {
                            // Read data rows
                            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                                $hocsinh = [
                                    'hocsinh_code' => trim($row[$headerMap['hocsinh_code']] ?? ''),
                                    'full_name' => trim($row[$headerMap['full_name']] ?? ''),
                                    'class' => trim($row[$headerMap['class']] ?? ''),
                                    'dob' => !empty($row[$headerMap['dob'] ?? -1]) ? trim($row[$headerMap['dob']]) : '',
                                    'address' => !empty($row[$headerMap['address'] ?? -1]) ? trim($row[$headerMap['address']]) : '',
                                    'parent_name' => !empty($row[$headerMap['parent_name'] ?? -1]) ? trim($row[$headerMap['parent_name']]) : '',
                                    'parent_phone' => !empty($row[$headerMap['parent_phone'] ?? -1]) ? trim($row[$headerMap['parent_phone']]) : '',
                                    'parent_email' => !empty($row[$headerMap['parent_email'] ?? -1]) ? trim($row[$headerMap['parent_email']]) : '',
                                    'status' => 'active',
                                ];
                                $hocsinh[] = $hocsinh;
                            }
                        }
                    }
                    fclose($handle);
                }

                // Process import if no errors
                if ($error === null && !empty($hocsinh)) {
                    $result = HocSinh::import($hocsinh);
                    $success = $result['success'];
                    $errors = $result['errors'];

                    if ($success > 0) {
                        $this->redirect('index.php?controller=hocsinh&action=index&imported=' . $success);
                        return;
                    }
                }
            }
        }

        $this->render('hocsinh/import', [
            'pageTitle' => 'Import học sinh',
            'error' => $error,
            'success' => $success,
            'errors' => $errors,
        ]);
    }

    /**
     * AJAX: Tìm kiếm tự động học sinh
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
            SELECT id, student_code AS hocsinh_code, full_name, class, grade
            FROM students
            WHERE student_code LIKE :q1 OR full_name LIKE :q2
            ORDER BY full_name ASC
            LIMIT 10
        ");
        $stmt->execute(['q1' => "%$q%", 'q2' => "%$q%"]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $results]);
    }
}

