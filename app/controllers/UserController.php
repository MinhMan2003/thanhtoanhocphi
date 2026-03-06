<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\User;

class UserController extends BaseController
{
    public function indexAction(): void
    {
        $this->requireAdmin();
        $this->requireAdmin();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, username, full_name, role, last_login, created_at FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        $totalStmt = $pdo->query('SELECT COUNT(*) FROM users');
        $total = (int)$totalStmt->fetchColumn();
        $pages = max(1, (int)ceil($total / $limit));

        $this->render('users/index', [
            'pageTitle' => 'Quản lý người dùng',
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public function createAction(): void
    {
        $this->requireAdmin();
        $this->requireAdmin();

        $error = null;
        $data = [
            'username' => '',
            'password' => '',
            'full_name' => '',
            'role' => 'staff',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data['username'] = trim($_POST['username'] ?? '');
            $data['password'] = $_POST['password'] ?? '';
            $data['full_name'] = trim($_POST['full_name'] ?? '');
            $data['role'] = $_POST['role'] ?? 'staff';

            if ($data['username'] === '') {
                $error = 'Vui lòng nhập tên đăng nhập.';
            } elseif (strlen($data['password']) < 4) {
                $error = 'Mật khẩu phải có ít nhất 4 ký tự.';
            } else {
                // Check exists
                $existing = User::findByUsername($data['username']);
                if ($existing) {
                    $error = 'Tên đăng nhập đã tồn tại.';
                } else {
                    User::create($data);
                    $this->redirect('index.php?controller=user&action=index');
                }
            }
        }

        $this->render('users/create', [
            'pageTitle' => 'Tạo người dùng',
            'error' => $error,
            'data' => $data,
        ]);
    }

    public function editAction(): void
    {
        $this->requireAdmin();
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $user = $id ? User::findById($id) : null;

        if (!$user) {
            http_response_code(404);
            echo 'Không tìm thấy người dùng.';
            return;
        }

        $error = null;
        $data = [
            'username' => (string)$user['username'],
            'password' => '',
            'full_name' => (string)($user['full_name'] ?? ''),
            'role' => (string)($user['role'] ?? 'staff'),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data['username'] = trim($_POST['username'] ?? '');
            $data['password'] = $_POST['password'] ?? '';
            $data['full_name'] = trim($_POST['full_name'] ?? '');
            $data['role'] = $_POST['role'] ?? 'staff';

            if ($data['username'] === '') {
                $error = 'Vui lòng nhập tên đăng nhập.';
            } elseif ($data['password'] !== '' && strlen($data['password']) < 4) {
                $error = 'Mật khẩu phải có ít nhất 4 ký tự.';
            } else {
                // Check exists
                $existing = User::findByUsername($data['username']);
                if ($existing && $existing['id'] !== $id) {
                    $error = 'Tên đăng nhập đã tồn tại.';
                } else {
                    User::update($id, $data);
                    $this->redirect('index.php?controller=user&action=index');
                }
            }
        }

        $this->render('users/edit', [
            'pageTitle' => 'Sửa người dùng',
            'error' => $error,
            'id' => $id,
            'data' => $data,
        ]);
    }

    public function deleteAction(): void
    {
        $this->requireAdmin();
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        
        // Cannot delete yourself
        if ($id !== ($_SESSION['user_id'] ?? 0)) {
            User::delete($id);
        }

        $this->redirect('index.php?controller=user&action=index');
    }
}
