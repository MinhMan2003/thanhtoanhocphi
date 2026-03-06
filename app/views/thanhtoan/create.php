<?php
/** @var string|null $error */
/** @var array $data */
/** @var array $invoices */
?>
<div class="page-header">
    <h1>Thanh toán</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="index.php?controller=thanhtoan&action=create" class="form">
        <div class="form__group">
            <label class="form__label">Phiếu báo thu <span class="required">*</span></label>
            <select name="invoice_id" class="form__input" required>
                <option value="">-- Chọn phiếu báo thu --</option>
                <?php foreach ($invoices as $inv): ?>
                <option value="<?= $inv['id'] ?>" data-amount="<?= $inv['total_amount'] ?>" <?= ($data['invoice_id'] == $inv['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($inv['invoice_code']) ?> - <?= htmlspecialchars($inv['student_name']) ?> (<?= htmlspecialchars($inv['student_code']) ?>) - <?= number_format($inv['total_amount'], 0, ',', '.') ?> đ
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form__group">
            <label class="form__label">Số tiền <span class="required">*</span></label>
            <input type="number" name="amount" value="<?= htmlspecialchars($data['amount'], ENT_QUOTES, 'UTF-8') ?>" class="form__input" required min="1">
        </div>

        <div class="form__group">
            <label class="form__label">Phương thức thanh toán</label>
            <select name="payment_method" class="form__input">
                <option value="cash" <?= ($data['payment_method'] ?? 'cash') === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                <option value="bank_transfer" <?= ($data['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
                <option value="vietqr" <?= ($data['payment_method'] ?? '') === 'vietqr' ? 'selected' : '' ?>>VietQR</option>
                <option value="other" <?= ($data['payment_method'] ?? '') === 'other' ? 'selected' : '' ?>>Khác</option>
            </select>
        </div>

        <div class="form__group">
            <label class="form__label">Ngày thanh toán</label>
            <input type="datetime-local" name="paid_at" value="<?= htmlspecialchars($data['paid_at'], ENT_QUOTES, 'UTF-8') ?>" class="form__input">
        </div>

        <div class="form__group">
            <label class="form__label">Mã tham chiếu ngân hàng</label>
            <input type="text" name="bank_ref" value="<?= htmlspecialchars($data['bank_ref'], ENT_QUOTES, 'UTF-8') ?>" class="form__input" placeholder="Mã giao dịch ngân hàng">
        </div>

        <div class="form__group">
            <label class="form__label">Ghi chú</label>
            <textarea name="note" class="form__input" rows="2"><?= htmlspecialchars($data['note'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form__actions">
            <button type="submit" class="btn btn-primary">Lưu thanh toán</button>
            <a href="index.php?controller=thanhtoan&action=index" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
