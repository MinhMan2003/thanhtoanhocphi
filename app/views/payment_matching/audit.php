<div class="page-header">
    <h1>Lịch sử thay đổi hóa đơn</h1>
    <div>
        <a href="index.php?controller=hoadon&action=view&id=<?= $hoadon_id ?>" class="btn btn-secondary">Quay lại hóa đơn</a>
    </div>
</div>

<?php if (empty($logs)): ?>
    <div class="empty-state">Chưa có lịch sử thay đổi.</div>
<?php else: ?>
    <div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Thời gian</th>
                <th>Người thực hiện</th>
                <th>Hành động</th>
                <th>Trường thay đổi</th>
                <th>Giá trị cũ</th>
                <th>Giá trị mới</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                <td><?= htmlspecialchars($log['user_name']) ?></td>
                <td>
                    <?php
                    $actionLabels = [
                        'created' => 'Tạo mới',
                        'updated' => 'Cập nhật',
                        'deleted' => 'Xóa',
                        'status_changed' => 'Đổi trạng thái',
                    ];
                    echo $actionLabels[$log['action']] ?? $log['action'];
                    ?>
                </td>
                <td><?= htmlspecialchars($log['field_changed'] ?? '-') ?></td>
                <td><?= htmlspecialchars($log['old_value'] ?? '-') ?></td>
                <td><?= htmlspecialchars($log['new_value'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
