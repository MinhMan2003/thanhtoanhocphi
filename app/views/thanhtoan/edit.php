<?php
/** @var string|null $error */
/** @var array $data */
/** @var array $invoices */
/** @var int $id */
?>
<div class="page-header">
    <h1>Sửa thanh toán</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="index.php?controller=thanhtoan&action=edit&id=<?= $id ?>" class="form">
        <div class="form__group">
            <label class="form__label">Phiếu báo thu <span class="required">*</span></label>
            <select name="invoice_id" class="form__input" required>
                <option value="">-- Chọn phiếu báo thu --</option>
                <?php foreach ($invoices as $inv): ?>
                <option value="<?= $inv['id'] ?>" <?= ($data['invoice_id'] == $inv['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($inv['invoice_code']) ?> - <?= htmlspecialchars($inv['student_name']) ?> - <?= number_format($inv['total_amount'], 0, ',', '.') ?> đ
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form__row">
            <div class="form__group">
                <label class="form__label">Số tiền <span class="required">*</span></label>
                <input type="number" name="amount" class="form__input" min="1000" step="1000" value="<?= htmlspecialchars($data['amount'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form__group">
                <label class="form__label">Phương thức</label>
                <select name="payment_method" class="form__input">
                    <option value="cash" <?= ($data['payment_method'] ?? 'cash') === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                    <option value="vietqr" <?= ($data['payment_method'] ?? '') === 'vietqr' ? 'selected' : '' ?>>VietQR</option>
                    <option value="bank_transfer" <?= ($data['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
                    <option value="other" <?= ($data['payment_method'] ?? '') === 'other' ? 'selected' : '' ?>>Khác</option>
                </select>
            </div>
        </div>

        <div class="form__row">
            <div class="form__group">
                <label class="form__label">Ngày thanh toán</label>
                <input type="datetime-local" name="paid_at" class="form__input" value="<?= htmlspecialchars($data['paid_at'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form__group">
                <label class="form__label">Mã tham chiếu ngân hàng</label>
                <input type="text" name="bank_ref" class="form__input" value="<?= htmlspecialchars($data['bank_ref'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form__group">
            <label class="form__label">Ghi chú</label>
            <textarea name="note" class="form__input" rows="2"><?= htmlspecialchars($data['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form__actions">
            <button type="submit" class="btn btn-primary">Lưu</button>
            <a href="index.php?controller=thanhtoan&action=index" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
