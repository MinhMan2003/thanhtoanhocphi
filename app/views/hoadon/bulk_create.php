<?php
/** @var string|null $error */
/** @var int $success */
/** @var array $data */
/** @var array $classes */
/** @var array $feeCategories */
?>
<div class="page-header">
    <h1>Tạo phiếu hàng loạt</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($success > 0): ?>
    <div class="alert alert--success">Đã tạo <?= $success ?> phiếu báo thu thành công!</div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="index.php?controller=hoadon&action=bulkCreate" class="form">
        <div class="form__row">
            <div class="form__group">
                <label class="form__label">Lớp <span class="required">*</span></label>
                <select name="class" class="form__input" required>
                    <option value="">-- Chọn lớp --</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= ($data['class'] === $c) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form__group">
                <label class="form__label">Tháng <span class="required">*</span></label>
                <select name="month" class="form__input" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($data['month'] == $m) ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form__group">
                <label class="form__label">Năm <span class="required">*</span></label>
                <select name="year" class="form__input" required>
                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= ($data['year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="form__group">
            <label class="form__label">Khoản thu áp dụng <span class="required">*</span></label>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($feeCategories as $fc): ?>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="fee_category_ids[]" value="<?= $fc['id'] ?>" 
                        <?= in_array($fc['id'], $data['fee_category_ids'] ?? []) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($fc['name'], ENT_QUOTES, 'UTF-8') ?> 
                    (<?= number_format($fc['default_amount'], 0, ',', '.') ?> đ)
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form__row">
            <div class="form__group">
                <label class="form__label">Ngày lập</label>
                <input type="date" name="issue_date" value="<?= htmlspecialchars($data['issue_date'], ENT_QUOTES, 'UTF-8') ?>" class="form__input">
            </div>
            <div class="form__group">
                <label class="form__label">Hạn thanh toán</label>
                <input type="date" name="due_date" value="<?= htmlspecialchars($data['due_date'], ENT_QUOTES, 'UTF-8') ?>" class="form__input">
            </div>
        </div>

        <div class="form__group">
            <label class="form__label">Ghi chú (áp dụng cho tất cả phiếu)</label>
            <textarea name="note" class="form__input" rows="2"><?= htmlspecialchars($data['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form__actions">
            <button type="submit" class="btn btn-primary">Tạo phiếu cho lớp</button>
            <a href="index.php?controller=hoadon&action=index" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>

<div class="card" style="margin-top:1rem; background:#f9fafb;">
    <h3 style="margin-top:0;">Hướng dẫn</h3>
    <ul style="margin:0; padding-left:1.2rem;">
        <li>Chọn lớp cần tạo phiếu</li>
        <li>Chọn tháng và năm cho phiếu</li>
        <li>Chọn các khoản thu muốn áp dụng (sử dụng số tiền mặc định)</li>
        <li>Hệ thống sẽ tự động tạo phiếu cho tất cả học sinh trong lớp</li>
    </ul>
</div>
