<?php
/** @var array $data */
?>

<div class="form-group">
    <label class="form-label">Tên khoản thu *</label>
    <input class="form-control" name="name" required
           value="<?= htmlspecialchars((string)$data['name'], ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="form-group">
    <label class="form-label">Mô tả</label>
    <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars((string)$data['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

<div class="form-group">
    <label class="form-label">Số tiền mặc định (đ)</label>
    <input class="form-control" type="number" min="0" step="1000" name="default_amount"
           value="<?= htmlspecialchars((string)$data['default_amount'], ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="form-group">
    <label class="form-label">Đơn vị tính</label>
    <select class="form-control" name="unit">
        <?php
        $unit = $data['unit'] ?? 'month';
        $options = [
            'month' => 'Theo tháng',
            'day'   => 'Theo ngày',
            'term'  => 'Theo học kỳ',
            'once'  => 'Thu một lần',
        ];
        foreach ($options as $value => $label): ?>
            <option value="<?= $value ?>" <?= $unit === $value ? 'selected' : '' ?>>
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label class="form-label">Trạng thái</label>
    <select class="form-control" name="is_active">
        <?php $isActive = (string)($data['is_active'] ?? '1'); ?>
        <option value="1" <?= $isActive === '1' ? 'selected' : '' ?>>Đang dùng</option>
        <option value="0" <?= $isActive === '0' ? 'selected' : '' ?>>Ngưng dùng</option>
    </select>
</div>

