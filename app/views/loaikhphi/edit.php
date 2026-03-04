<?php
/** @var string|null $error */
/** @var array $data */
/** @var int $id */
?>

<h1 class="page-title">Sửa khoản thu</h1>

<?php if (!empty($error)): ?>
    <div class="card" style="background:#fee2e2; color:#991b1b;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card">
    <form method="post">
        <?php require __DIR__ . '/_form.php'; ?>
        <div style="display:flex; gap:8px; margin-top:12px;">
            <button class="btn btn-primary" type="submit">Lưu</button>
            <a class="btn btn-secondary" href="index.php?controller=loaikhphi&action=index">Hủy</a>
        </div>
    </form>
</div>

