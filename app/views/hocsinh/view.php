<?php
/** @var array $student */
/** @var array $invoices */
?>
<div class="page-header">
    <h1>Chi tiết học sinh</h1>
    <div>
        <a href="index.php?controller=hocsinh&action=edit&id=<?= $student['id'] ?>" class="btn btn-secondary">Sửa</a>
        <a href="index.php?controller=hocsinh&action=index" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<div class="card">
    <h2 style="margin-top:0;">Thông tin học sinh</h2>
    <table class="info-table">
        <tr>
            <th>Mã học sinh:</th>
            <td><?= htmlspecialchars($student['student_code'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Họ tên:</th>
            <td><?= htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Khối:</th>
            <td><?= htmlspecialchars($student['grade'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Lớp:</th>
            <td><?= htmlspecialchars($student['class'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Ngày sinh:</th>
            <td><?= htmlspecialchars($student['dob'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Địa chỉ:</th>
            <td><?= htmlspecialchars($student['address'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Tên phụ huynh:</th>
            <td><?= htmlspecialchars($student['parent_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Điện thoại:</th>
            <td><?= htmlspecialchars($student['parent_phone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Email:</th>
            <td><?= htmlspecialchars($student['parent_email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Trạng thái:</th>
            <td>
                <?php if (($student['status'] ?? 'active') === 'active'): ?>
                    <span class="status-badge status-active">Đang học</span>
                <?php else: ?>
                    <span class="status-badge status-inactive">Nghỉ</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<div class="card" style="margin-top:1rem;">
    <h2 style="margin-top:0;">Lịch sử phiếu báo thu</h2>
    <?php if (empty($invoices)): ?>
        <div class="empty-state">Chưa có phiếu báo thu nào.</div>
    <?php else: ?>
        <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Tháng/Năm</th>
                    <th>Ngày lập</th>
                    <th>Hạn thanh toán</th>
                    <th>Tổng tiền</th>
                    <th>Đã thanh toán</th>
                    <th>Còn nợ</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): 
                    $paidAmount = (int)($inv['paid_amount'] ?? 0);
                    $totalAmount = (int)$inv['total_amount'];
                    $remaining = $totalAmount - $paidAmount;
                ?>
                <tr>
                    <td><?= htmlspecialchars($inv['invoice_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $inv['month'] ?>/<?= $inv['year'] ?></td>
                    <td><?= htmlspecialchars($inv['issue_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($inv['due_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format($totalAmount, 0, ',', '.') ?> đ</td>
                    <td><?= number_format($paidAmount, 0, ',', '.') ?> đ</td>
                    <td><?= number_format(max(0, $remaining), 0, ',', '.') ?> đ</td>
                    <td>
                        <?php
                        $statusClass = [
                            'pending' => 'status-pending',
                            'paid' => 'status-paid',
                            'partial' => 'status-partial',
                            'cancelled' => 'status-cancelled',
                        ];
                        $statusText = [
                            'pending' => 'Chờ',
                            'paid' => 'Đã TT',
                            'partial' => 'Còn nợ',
                            'cancelled' => 'Hủy',
                        ];
                        ?>
                        <span class="status-badge <?= $statusClass[$inv['status']] ?? '' ?>">
                            <?= $statusText[$inv['status']] ?? $inv['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="index.php?controller=hoadon&action=view&id=<?= $inv['id'] ?>" class="btn-link">Xem</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
