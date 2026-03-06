<?php
/** @var string|null $error */
/** @var string $pageTitle */
/** @var string|null $loginType - 'admin' hoặc 'student' */

$pageTitle = $pageTitle ?? 'Đăng nhập';
$loginType = $_GET['type'] ?? $_POST['login_type'] ?? 'admin';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background:#0b1220; }
        .login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .login-card { width:100%; max-width:420px; background:#fff; border-radius:12px; padding:20px; box-shadow:0 12px 30px rgba(0,0,0,.35); }
        .login-title { font-size:20px; font-weight:700; margin:0 0 6px; }
        .login-sub { margin:0 0 16px; color:#6b7280; font-size:14px; }
        .alert { padding:10px 12px; border-radius:8px; margin-bottom:12px; font-size:14px; }
        .alert-error { background:#fee2e2; color:#991b1b; }
        .btn-full { width:100%; }
        .hint { margin-top:12px; font-size:13px; color:#6b7280; }
        code { background:#f3f4f6; padding:2px 6px; border-radius:6px; }
        
        /* Tab styles */
        .login-tabs { display:flex; gap:8px; margin-bottom:20px; border-bottom:2px solid #e5e7eb; }
        .login-tab { 
            flex:1; padding:10px; text-align:center; cursor:pointer; 
            border:none; background:none; font-size:14px; font-weight:500; color:#6b7280;
            border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.2s;
        }
        .login-tab:hover { color:#3b82f6; }
        .login-tab.active { color:#3b82f6; border-bottom-color:#3b82f6; }
        
        .tab-content { display:none; }
        .tab-content.active { display:block; }
        
        .form-icon { position:relative; }
        .form-icon i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; }
        .form-icon input { padding-left:40px; }
        
        .login-footer { text-align:center; margin-top:16px; padding-top:16px; border-top:1px solid #e5e7eb; }
        .login-footer a { color:#3b82f6; text-decoration:none; font-size:14px; }
        .login-footer a:hover { text-decoration:underline; }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-tabs">
            <button type="button" class="login-tab <?= $loginType === 'admin' ? 'active' : '' ?>" onclick="switchTab('admin')">
                Quản trị viên
            </button>
            <button type="button" class="login-tab <?= $loginType === 'student' ? 'active' : '' ?>" onclick="switchTab('student')">
                Học sinh / Phụ huynh
            </button>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Tab Admin -->
        <div class="tab-content <?= $loginType === 'admin' ? 'active' : '' ?>" id="tab-admin">
            <h1 class="login-title">Đăng nhập quản trị</h1>
            <p class="login-sub">Hệ thống thanh toán học phí</p>

            <form method="post" action="index.php?controller=auth&action=login" autocomplete="off">
                <input type="hidden" name="login_type" value="admin">
                <div class="form-group">
                    <label class="form-label">Tên đăng nhập</label>
                    <input class="form-control" type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mật khẩu</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <button class="btn btn-primary btn-full" type="submit">Đăng nhập</button>
            </form>

            <div class="hint">
                Tài khoản mẫu: <code>admin</code> / <code>admin123</code>
            </div>
        </div>

        <!-- Tab Học sinh/Phụ huynh -->
        <div class="tab-content <?= $loginType === 'student' ? 'active' : '' ?>" id="tab-student">
            <h1 class="login-title">Tra cứu thông tin</h1>
            <p class="login-sub">Xem hóa đơn học phí và bảng điểm</p>

            <form method="post" action="index.php?controller=auth&action=login" autocomplete="off">
                <input type="hidden" name="login_type" value="student">
                <div class="form-group">
                    <label class="form-label">Mã học sinh</label>
                    <input class="form-control" type="text" name="student_code" placeholder="Nhập mã học sinh" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ngày sinh</label>
                    <input class="form-control" type="date" name="dob" required>
                </div>
                <button class="btn btn-primary btn-full" type="submit">Tra cứu</button>
            </form>

            <div class="hint">
                Sử dụng mã học sinh và ngày sinh để tra cứu thông tin
            </div>
        </div>

        <div class="login-footer">
            <a href="index.php?controller=portal&action=index">Truy cập cổng thông tin học sinh</a>
        </div>
    </div>
</div>

<script>
function switchTab(type) {
    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('type', type);
    window.history.pushState({}, '', url);
    
    // Update tab UI
    document.querySelectorAll('.login-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    document.querySelector('.login-tab:nth-child(' + (type === 'admin' ? '1' : '2') + ')').classList.add('active');
    document.getElementById('tab-' + type).classList.add('active');
}

// Initialize from URL parameter
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('type');
    if (type === 'admin' || type === 'student') {
        switchTab(type);
    }
});
</script>
</body>
</html>
