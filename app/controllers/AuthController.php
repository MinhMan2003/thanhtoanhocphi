<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\User;

class AuthController extends BaseController
{
    public function loginAction(): void
    {
        // Nếu đã đăng nhập thì chuyển về bangdieukhien
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('index.php?controller=bangdieukhien&action=index');
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($username === '' || $password === '') {
                $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
            } else {
                $user = User::findByUsername($username);

                if ($user && User::verifyPassword($password, $user['password_hash'])) {
                    // Đăng nhập thành công
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_full_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];

                    User::updateLastLogin($user['id']);

                    $this->redirect('index.php?controller=bangdieukhien&action=index');
                } else {
                    $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
                }
            }
        }

        $this->renderPlain('auth/login', [
            'pageTitle' => 'Đăng nhập',
            'error' => $error,
        ]);
    }

    public function logoutAction(): void
    {
        session_destroy();
        $this->redirect('index.php?controller=auth&action=login');
    }

    public function changePasswordAction(): void
    {
        // Yêu cầu đăng nhập
        if (empty($_SESSION['user_id'])) {
            $this->redirect('index.php?controller=auth&action=login');
        }

        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($currentPassword === '') {
                $error = 'Vui lòng nhập mật khẩu hiện tại.';
            } elseif ($newPassword === '') {
                $error = 'Vui lòng nhập mật khẩu mới.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Mật khẩu mới và xác nhận mật khẩu không khớp.';
            } else {
                // Kiểm tra mật khẩu hiện tại
                $user = User::findById((int)$_SESSION['user_id']);

                if (!$user || !User::verifyPassword($currentPassword, $user['password_hash'])) {
                    $error = 'Mật khẩu hiện tại không đúng.';
                } else {
                    // Cập nhật mật khẩu mới
                    if (User::updatePassword((int)$_SESSION['user_id'], $newPassword)) {
                        $success = 'Đổi mật khẩu thành công.';
                    } else {
                        $error = 'Đã xảy ra lỗi. Vui lòng thử lại.';
                    }
                }
            }
        }

        $this->render('auth/change_password', [
            'pageTitle' => 'Đổi mật khẩu',
            'error' => $error,
            'success' => $success,
        ]);
    }
}
