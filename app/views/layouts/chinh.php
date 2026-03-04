<?php
use App\Core\Config;

/** @var string $contentView */
/** @var string $pageTitle */

$pageTitle = $pageTitle ?? 'Hệ thống thanh toán học phí';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/css/style.css">
</head>
<body>
<?php if (!isset($_GET['public'])): ?>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar__header">
            <div class="sidebar__title"><?= htmlspecialchars(Config::SCHOOL_NAME, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="sidebar__subtitle">Quản lý học phí</div>
        </div>
        <nav class="sidebar__nav">
            <a href="index.php?controller=bangdieukhien&action=index">Bảng điều khiển</a>
            <a href="index.php?controller=hocsinh&action=index">Học sinh</a>
            <a href="index.php?controller=loaikhphi&action=index">Khoản thu</a>
            <a href="index.php?controller=hoadon&action=index">Phiếu báo thu</a>
            <a href="index.php?controller=thanhtoan&action=index">Thanh toán</a>
            <a href="index.php?controller=baocao&action=index">Báo cáo</a>
            <a href="index.php?controller=payment-matching&action=index">Đối soát thanh toán</a>
        </nav>
        <div class="sidebar__footer">
            <?php if (!empty($_SESSION['user_full_name'])): ?>
                <div class="sidebar__user">
                    <span>👤 <?= htmlspecialchars($_SESSION['user_full_name'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <a class="sidebar__link" href="index.php?controller=auth&action=changePassword">Đổi mật khẩu</a>
                <a class="sidebar__logout" href="index.php?controller=auth&action=logout">Đăng xuất</a>
            <?php else: ?>
                <a class="sidebar__logout" href="index.php?controller=auth&action=login">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </aside>
    <main class="main">
        <?php require $contentView; ?>
    </main>
</div>
<?php else: ?>
    <?php require $contentView; ?>
<?php endif; ?>
</body>
</html>

