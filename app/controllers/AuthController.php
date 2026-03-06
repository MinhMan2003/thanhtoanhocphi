<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\User;
use App\Models\HocSinhPortal;

class AuthController extends BaseController
{
    public function loginAction(): void
    {
        // Nếu đã đăng nhập thì chuyển về trang phù hợp
        if (!empty($_SESSION['user_id'])) {
            $this->redirectToUserHome($_SESSION['user_role'] ?? 'admin');
            return;
        }

        $error = null;
        $loginType = $_POST['login_type'] ?? $_GET['type'] ?? 'admin';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $loginType = $_POST['login_type'] ?? 'admin';

            if ($loginType === 'student') {
                // Đăng nhập học sinh/phụ huynh
                $studentCode = trim($_POST['student_code'] ?? '');
                $dob = $_POST['dob'] ?? '';

                if ($studentCode === '' || $dob === '') {
                    $error = 'Vui lòng nhập mã học sinh và ngày sinh.';
                } else {
                    $student = HocSinhPortal::lookup($studentCode, $dob);

                    if ($student) {
                        // Đăng nhập học sinh thành công
                        $_SESSION['portal_student_id'] = $student['id'];
                        $_SESSION['portal_student_code'] = $student['student_code'];
                        $_SESSION['portal_student_name'] = $student['full_name'];
                        $_SESSION['user_role'] = 'student';
                        $_SESSION['user_full_name'] = $student['full_name'];

                        $this->redirect('index.php?controller=portal&action=index');
                    } else {
                        $error = 'Không tìm thấy học sinh với mã và ngày sinh này.';
                    }
                }
            } else {
                // Đăng nhập admin
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';

                if ($username === '' || $password === '') {
                    $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
                } else {
                    $user = User::findByUsername($username);

                    if ($user && User::verifyPassword($password, $user['password_hash'])) {
                        // Đăng nhập admin thành công
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_username'] = $user['username'];
                        $_SESSION['user_full_name'] = $user['full_name'];
                        $_SESSION['user_role'] = $user['role'];

                        User::updateLastLogin($user['id']);

                        $this->redirectToUserHome($user['role']);
                    } else {
                        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
                    }
                }
            }
        }

        $this->renderPlain('auth/login', [
            'pageTitle' => 'Đăng nhập',
            'error' => $error,
            'loginType' => $loginType,
        ]);
    }

    /**
     * Chuyển hướng về trang chủ phù hợp với vai trò
     */
    private function redirectToUserHome(string $role): void
    {
        // Nếu là học sinh (đăng nhập qua portal)
        if ($role === 'student') {
            $this->redirect('index.php?controller=portal&action=index');
            return;
        }

        // Admin và các vai trò khác vào trang quản trị
        $this->redirect('index.php?controller=bangdieukhien&action=index');
    }

    public function logoutAction(): void
    {
        $role = $_SESSION['user_role'] ?? 'admin';
        
        // Xóa tất cả session
        session_destroy();
        
        // Chuyển hướng về trang đăng nhập phù hợp
        if ($role === 'student') {
            $this->redirect('index.php?controller=auth&action=login&type=student');
        } else {
            $this->redirect('index.php?controller=auth&action=login&type=admin');
        }
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
