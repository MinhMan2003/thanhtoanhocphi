<?php
/** @var string|null $error */
/** @var string|null $success */
?>
<div class="page-header">
    <h1>Đổi mật khẩu</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert--success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card" style="max-width: 500px;">
    <form method="POST" action="index.php?controller=auth&action=changePassword" class="form">
        <div class="form__group">
            <label for="current_password" class="form__label">Mật khẩu hiện tại</label>
            <input type="password" id="current_password" name="current_password" class="form__input" required>
        </div>

        <div class="form__group">
            <label for="new_password" class="form__label">Mật khẩu mới</label>
            <input type="password" id="new_password" name="new_password" class="form__input" required minlength="6">
            <small class="form__help">Tối thiểu 6 ký tự</small>
        </div>

        <div class="form__group">
            <label for="confirm_password" class="form__label">Xác nhận mật khẩu mới</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form__input" required>
        </div>

        <div class="form__actions">
            <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
            <a href="index.php?controller=bangdieukhien&action=index" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
