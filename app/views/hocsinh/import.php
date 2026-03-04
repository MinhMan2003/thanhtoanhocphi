<?php
/** @var string $error */
/** @var int $success */
/** @var array $errors */
?>

<div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
    <h1 class="page-title" style="margin:0;">Import học sinh</h1>
    <a class="btn btn-secondary" href="index.php?controller=hocsinh&action=index">&larr; Quay lại</a>
</div>

<div class="card" style="margin-top:1rem;">
    <h3 style="margin-top:0;">Hướng dẫn</h3>
    <p>Tải lên file CSV chứa danh sách học sinh. File cần có các cột:</p>
    <ul style="margin:10px 0; padding-left:20px;">
        <li><strong>student_code</strong> - Mã học sinh (bắt buộc)</li>
        <li><strong>full_name</strong> - Họ và tên (bắt buộc)</li>
        <li><strong>class</strong> - Lớp (bắt buộc)</li>
        <li><strong>dob</strong> - Ngày sinh (YYYY-MM-DD, tùy chọn)</li>
        <li><strong>address</strong> - Địa chỉ (tùy chọn)</li>
        <li><strong>parent_name</strong> - Tên phụ huynh (tùy chọn)</li>
        <li><strong>parent_phone</strong> - SĐT phụ huynh (tùy chọn)</li>
        <li><strong>parent_email</strong> - Email phụ huynh (tùy chọn)</li>
    </ul>
    
    <p><a href="data:text/csv;charset=utf-8,student_code,full_name,class,dob,address,parent_name,parent_phone,parent_email%0AHS001,Nguyen Van A,6A1,2014-01-15,123 Nguyen Trai,Nguyen Van B,0912345678,a@example.com%0AHS002,Tran Thi B,6A2,2014-02-20,456 Le Loi,Tran Van C,0987654321,b@example.com" download="import_hocsinh_mau.csv" class="btn btn-secondary">Tải file mẫu CSV</a></p>
</div>

<div class="card" style="margin-top:1rem;">
    <h3 style="margin-top:0;">Tải lên file</h3>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors) && $success === 0): ?>
        <div class="alert alert-error">
            <strong>Import thất bại:</strong>
            <ul style="margin:10px 0 0 20px;">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div style="margin:15px 0;">
            <input type="file" name="file" accept=".csv,.txt" required>
        </div>
        <button type="submit" class="btn btn-primary">Import</button>
    </form>
</div>
