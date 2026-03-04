<?php
/** @var string|null $error */
/** @var array $data */
/** @var array $items */
/** @var array $students */
/** @var array $feeCategories */
/** @var int $id */
?>
<div class="page-header">
    <h1>Sửa phiếu báo thu</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="index.php?controller=hoadon&action=edit&id=<?= $id ?>" class="form" id="invoiceForm">
        <div class="form__group">
            <label class="form__label">Học sinh <span class="required">*</span></label>
            <select name="student_id" id="student_id" class="form__input" required>
                <option value="">-- Chọn học sinh --</option>
                <?php foreach ($students as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($data['student_id'] == $s['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['student_code']) ?> - <?= htmlspecialchars($s['full_name']) ?> - Lớp <?= htmlspecialchars($s['class']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form__row">
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

        <div class="form__row">
            <div class="form__group">
                <label class="form__label">Ngày lập</label>
                <input type="date" name="issue_date" value="<?= htmlspecialchars($data['issue_date'], ENT_QUOTES, 'UTF-8') ?>" class="form__input">
            </div>
            <div class="form__group">
                <label class="form__label">Hạn thanh toán</label>
                <input type="date" name="due_date" value="<?= htmlspecialchars($data['due_date'], ENT_QUOTES, 'UTF-8') ?>" class="form__input">
            </div>
            <div class="form__group">
                <label class="form__label">Trạng thái</label>
                <select name="status" class="form__input">
                    <option value="pending" <?= ($data['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                    <option value="paid" <?= ($data['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                    <option value="partial" <?= ($data['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Thanh toán một phần</option>
                    <option value="cancelled" <?= ($data['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
        </div>

        <div class="form__group">
            <label class="form__label">Khoản thu <span class="required">*</span></label>
            <div id="itemsContainer">
                <?php foreach ($items as $item): ?>
                <div class="invoice-item">
                    <select class="form__input item-category">
                        <option value="">-- Chọn khoản thu --</option>
                        <?php foreach ($feeCategories as $fc): ?>
                        <option value="<?= $fc['id'] ?>" data-amount="<?= $fc['default_amount'] ?>" data-name="<?= htmlspecialchars($fc['name'], ENT_QUOTES, 'UTF-8') ?>" <?= ($item['fee_category_id'] == $fc['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fc['name']) ?> - <?= number_format($fc['default_amount'], 0, ',', '.') ?> đ
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" class="form__input item-amount" placeholder="Số tiền" min="0" value="<?= htmlspecialchars($item['amount'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" class="btn btn-secondary btn-remove-item">Xóa</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary" id="addItemBtn">+ Thêm khoản thu</button>
        </div>

        <div class="form__group">
            <label class="form__label">Ghi chú</label>
            <textarea name="note" class="form__input" rows="3"><?= htmlspecialchars($data['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form__summary">
            <strong>Tổng tiền: </strong>
            <span id="totalAmount">0</span> đ
        </div>

        <div class="form__actions">
            <button type="submit" class="btn btn-primary">Lưu</button>
            <a href="index.php?controller=hoadon&action=index" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    const totalAmountSpan = document.getElementById('totalAmount');
    const form = document.getElementById('invoiceForm');

    function formatNumber(num) {
        return new Intl.NumberFormat('vi-VN').format(num);
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.invoice-item').forEach(function(item) {
            const amount = parseInt(item.querySelector('.item-amount').value) || 0;
            total += amount;
        });
        totalAmountSpan.textContent = formatNumber(total);
    }

    function createHiddenInput(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        input.className = 'invoice-item-hidden';
        return input;
    }

    // Add item
    addItemBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'invoice-item';
        div.innerHTML = `
            <select class="form__input item-category">
                <option value="">-- Chọn khoản thu --</option>
                <?php foreach ($feeCategories as $fc): ?>
                <option value="<?= $fc['id'] ?>" data-amount="<?= $fc['default_amount'] ?>" data-name="<?= htmlspecialchars($fc['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($fc['name']) ?> - <?= number_format($fc['default_amount'], 0, ',', '.') ?> đ
                </option>
                <?php endforeach; ?>
            </select>
            <input type="number" class="form__input item-amount" placeholder="Số tiền" min="0">
            <button type="button" class="btn btn-secondary btn-remove-item">Xóa</button>
        `;
        itemsContainer.appendChild(div);
    });

    // Remove item & category change
    itemsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-item')) {
            e.target.closest('.invoice-item').remove();
            calculateTotal();
        }
    });

    itemsContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-category')) {
            const option = e.target.selectedOptions[0];
            const amountInput = e.target.closest('.invoice-item').querySelector('.item-amount');
            if (option && option.dataset.amount) {
                amountInput.value = option.dataset.amount;
            }
            calculateTotal();
        }
        if (e.target.classList.contains('item-amount')) {
            calculateTotal();
        }
    });

    // Trước khi submit: build mảng items[*] gửi lên server
    form.addEventListener('submit', function(e) {
        document.querySelectorAll('.invoice-item-hidden').forEach(function(el) {
            el.remove();
        });

        let hasItem = false;
        const items = document.querySelectorAll('.invoice-item');

        items.forEach(function(item, index) {
            const categorySelect = item.querySelector('.item-category');
            const amountInput = item.querySelector('.item-amount');
            const feeCategoryId = categorySelect.value;
            const amount = parseInt(amountInput.value) || 0;
            const option = categorySelect.selectedOptions[0];
            const description = option && option.dataset.name ? option.dataset.name : '';

            if (feeCategoryId && amount > 0) {
                hasItem = true;
                const base = 'items[' + index + ']';
                form.appendChild(createHiddenInput(base + '[fee_category_id]', feeCategoryId));
                form.appendChild(createHiddenInput(base + '[description]', description));
                form.appendChild(createHiddenInput(base + '[amount]', String(amount)));
            }
        });

        if (!hasItem) {
            e.preventDefault();
            alert('Vui lòng thêm ít nhất một khoản thu hợp lệ.');
        }
    });

    // Initial calculation
    calculateTotal();
});
</script>
