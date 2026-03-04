<div class="page-header">
    <h1>Quản lý thanh toán - Payments chưa khớp</h1>
    <div>
        <a href="index.php?controller=payment-matching&action=index" class="btn btn-secondary">Tất cả</a>
        <a href="index.php?controller=payment-matching&action=matched" class="btn btn-secondary">Đã khớp</a>
        <a href="index.php?controller=payment-matching&action=unmatched" class="btn btn-primary">Chưa khớp (<?= $total ?>)</a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (empty($payments)): ?>
    <div class="empty-state">Không có payment nào chưa khớp.</div>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Mã giao dịch</th>
                <th>Số tiền</th>
                <th>Nội dung</th>
                <th>Người chuyển</th>
                <th>Ngày giao dịch</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $index => $p): ?>
            <tr>
                <td><?= ($page - 1) * $limit + $index + 1 ?></td>
                <td><?= htmlspecialchars($p['trans_id']) ?></td>
                <td><?= number_format($p['amount'], 0, ',', '.') ?> đ</td>
                <td><?= htmlspecialchars($p['content'] ?? '') ?></td>
                <td>
                    <?= htmlspecialchars($p['account_name'] ?? '') ?>
                    <?php if (!empty($p['account_no'])): ?>
                        <br><small><?= htmlspecialchars($p['account_no']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= $p['bank_time'] ? date('d/m/Y H:i', strtotime($p['bank_time'])) : date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                <td>
                    <?php if ($p['match_status'] === 'MATCHED'): ?>
                        <span class="badge badge-success">Đã khớp</span>
                    <?php elseif ($p['match_status'] === 'PENDING'): ?>
                        <span class="badge badge-warning">Chờ xử lý</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Chưa khớp</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($p['match_status'] !== 'MATCHED'): ?>
                        <a href="index.php?controller=payment-matching&action=selectHoaDon&payment_id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Khớp</a>
                    <?php else: ?>
                        <a href="index.php?controller=hoadon&action=view&id=<?= $p['matched_hoadon_id'] ?>" class="btn btn-sm btn-secondary">Xem hóa đơn</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="index.php?controller=payment-matching&action=unmatched&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
