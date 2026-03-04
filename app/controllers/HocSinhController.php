<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\HocSinh;

class HocSinhController extends BaseController
{
    public function indexAction(): void
    {
        $this->requireLogin();

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
        $this->requireLogin();

        $error = null;
        $data = [
            'student_code' => '',
            'full_name' => '',
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

            if ($data['student_code'] === '' || $data['full_name'] === '' || $data['class'] === '') {
                $error = 'Vui lòng nhập Mã học sinh, Họ tên và Lớp.';
            } else {
                HocSinh::create($data);
                $this->redirect('index.php?controller=student&action=index');
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
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $student = $id ? HocSinh::find($id) : null;
        if (!$student) {
            http_response_code(404);
            echo 'Không tìm thấy học sinh.';
            return;
        }

        $error = null;
        $data = [
            'student_code' => (string)$student['student_code'],
            'full_name' => (string)$student['full_name'],
            'class' => (string)$student['class'],
            'dob' => (string)($student['dob'] ?? ''),
            'address' => (string)($student['address'] ?? ''),
            'parent_name' => (string)($student['parent_name'] ?? ''),
            'parent_phone' => (string)($student['parent_phone'] ?? ''),
            'parent_email' => (string)($student['parent_email'] ?? ''),
            'status' => (string)($student['status'] ?? 'active'),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($data as $k => $_) {
                $data[$k] = trim((string)($_POST[$k] ?? ''));
            }
            $data['status'] = in_array($data['status'], ['active', 'inactive'], true) ? $data['status'] : 'active';

            if ($data['student_code'] === '' || $data['full_name'] === '' || $data['class'] === '') {
                $error = 'Vui lòng nhập Mã học sinh, Họ tên và Lớp.';
            } else {
                HocSinh::update($id, $data);
                $this->redirect('index.php?controller=student&action=index');
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
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            HocSinh::delete($id);
        }

        $this->redirect('index.php?controller=student&action=index');
    }

    public function viewAction(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $student = $id ? HocSinh::find($id) : null;

        if (!$student) {
            http_response_code(404);
            echo 'Không tìm thấy học sinh.';
            return;
        }

        $invoices = HocSinh::getInvoices($id);

        $this->render('hocsinh/view', [
            'pageTitle' => 'Chi tiết học sinh',
            'student' => $student,
            'invoices' => $invoices,
        ]);
    }

    public function importAction(): void
    {
        $this->requireLogin();

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
                        $requiredCols = ['student_code', 'full_name', 'class'];
                        foreach ($requiredCols as $col) {
                            if (!isset($headerMap[$col])) {
                                $error = "Thiếu cột bắt buộc: $col";
                                break;
                            }
                        }

                        if ($error === null) {
                            // Read data rows
                            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                                $student = [
                                    'student_code' => trim($row[$headerMap['student_code']] ?? ''),
                                    'full_name' => trim($row[$headerMap['full_name']] ?? ''),
                                    'class' => trim($row[$headerMap['class']] ?? ''),
                                    'dob' => !empty($row[$headerMap['dob'] ?? -1]) ? trim($row[$headerMap['dob']]) : '',
                                    'address' => !empty($row[$headerMap['address'] ?? -1]) ? trim($row[$headerMap['address']]) : '',
                                    'parent_name' => !empty($row[$headerMap['parent_name'] ?? -1]) ? trim($row[$headerMap['parent_name']]) : '',
                                    'parent_phone' => !empty($row[$headerMap['parent_phone'] ?? -1]) ? trim($row[$headerMap['parent_phone']]) : '',
                                    'parent_email' => !empty($row[$headerMap['parent_email'] ?? -1]) ? trim($row[$headerMap['parent_email']]) : '',
                                    'status' => 'active',
                                ];
                                $hocsinh[] = $student;
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
                        $this->redirect('index.php?controller=student&action=index&imported=' . $success);
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
}

