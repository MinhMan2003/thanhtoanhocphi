<?php
/** @var string|null $error */
/** @var string $pageTitle */

$pageTitle = $pageTitle ?? 'Đăng nhập';
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
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <h1 class="login-title">Đăng nhập quản trị</h1>
        <p class="login-sub">Hệ thống thanh toán học phí</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?controller=auth&action=login" autocomplete="off">
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
</div>
</body>
</html>

