<?php
/** @var array $data */
?>

<div class="form-group">
    <label class="form-label">Mã học sinh *</label>
    <input class="form-control" name="hocsinh_code" required value="<?= htmlspecialchars((string)($data['hocsinh_code'] ?? $data['student_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label class="form-label">Họ tên *</label>
    <input class="form-control" name="full_name" required value="<?= htmlspecialchars((string)$data['full_name'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label class="form-label">Khối</label>
    <select class="form-control" name="grade">
        <option value="">-- Chọn khối --</option>
        <option value="1" <?= ($data['grade'] ?? '') === '1' ? 'selected' : '' ?>>Khối 1</option>
        <option value="2" <?= ($data['grade'] ?? '') === '2' ? 'selected' : '' ?>>Khối 2</option>
        <option value="3" <?= ($data['grade'] ?? '') === '3' ? 'selected' : '' ?>>Khối 3</option>
        <option value="4" <?= ($data['grade'] ?? '') === '4' ? 'selected' : '' ?>>Khối 4</option>
        <option value="5" <?= ($data['grade'] ?? '') === '5' ? 'selected' : '' ?>>Khối 5</option>
    </select>
</div>
<div class="form-group">
    <label class="form-label">Lớp *</label>
    <input class="form-control" name="class" required value="<?= htmlspecialchars((string)$data['class'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label class="form-label">Ngày sinh</label>
    <input class="form-control" type="date" name="dob" value="<?= htmlspecialchars((string)$data['dob'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label class="form-label">Địa chỉ</label>
    <input class="form-control" name="address" value="<?= htmlspecialchars((string)($data['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label class="form-label">Tên phụ huynh</label>
    <input class="form-control" name="parent_name" value="<?= htmlspecialchars((string)$data['parent_name'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label class="form-label">SĐT phụ huynh</label>
    <input class="form-control" name="parent_phone" value="<?= htmlspecialchars((string)$data['parent_phone'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label class="form-label">Email phụ huynh</label>
    <input class="form-control" type="email" name="parent_email" value="<?= htmlspecialchars((string)$data['parent_email'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="form-group">
    <label class="form-label">Trạng thái</label>
    <select class="form-control" name="status">
        <option value="active" <?= ($data['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang học</option>
        <option value="inactive" <?= ($data['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Nghỉ</option>
    </select>
</div>

