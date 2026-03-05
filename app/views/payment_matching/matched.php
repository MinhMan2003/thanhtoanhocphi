<div class="page-header">
    <h1>Quản lý thanh toán - Payments đã khớp</h1>
    <div>
        <a href="index.php?controller=payment-matching&action=index" class="btn btn-secondary">Tất cả</a>
        <a href="index.php?controller=payment-matching&action=matched" class="btn btn-primary">Đã khớp (<?= $total ?>)</a>
        <a href="index.php?controller=payment-matching&action=unmatched" class="btn btn-secondary">Chưa khớp</a>
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
    <div class="empty-state">Không có payment nào đã khớp.</div>
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
                <th>Hóa đơn</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $index => $p): ?>
            <tr>
                <td><?= ($page - 1) * $limit + $index + 1 ?></td>
                <td><?= htmlspecialchars($p['trans_id']) ?></td>
                <td><?= number_format((int)$p['amount'], 0, ',', '.') ?> đ</td>
                <td><?= htmlspecialchars($p['content'] ?? '') ?></td>
                <td>
                    <?= htmlspecialchars($p['account_name'] ?? '') ?>
                    <?php if (!empty($p['account_no'])): ?>
                        <br><small><?= htmlspecialchars($p['account_no']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= !empty($p['bank_time']) ? date('d/m/Y H:i', strtotime($p['bank_time'])) : date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                <td>
                    <?php if (!empty($p['matched_hoadon_id'])): ?>
                        <a href="index.php?controller=hoadon&action=view&id=<?= (int)$p['matched_hoadon_id'] ?>">
                            <?= htmlspecialchars($p['invoice_code'] ?? ('#' . (int)$p['matched_hoadon_id'])) ?>
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($p['matched_hoadon_id'])): ?>
                        <a href="index.php?controller=hoadon&action=view&id=<?= (int)$p['matched_hoadon_id'] ?>" class="btn btn-sm btn-secondary">Xem hóa đơn</a>
                        <form method="POST" action="index.php?controller=payment-matching&action=unmatch" style="display:inline;">
                            <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bỏ khớp payment này?')">Bỏ khớp</button>
                        </form>
                    <?php else: ?>
                        <a href="index.php?controller=payment-matching&action=selectHoaDon&payment_id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-primary">Khớp</a>
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
                <a href="index.php?controller=payment-matching&action=matched&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

