<div class="page-header">
    <h1>Quản lý thanh toán - Tất cả</h1>
    <div>
        <a href="index.php?controller=payment-matching&action=index" class="btn btn-primary">Tất cả</a>
        <a href="index.php?controller=payment-matching&action=matched" class="btn btn-secondary">Đã khớp</a>
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

<div class="card" style="margin-bottom: 1rem;">
    <form method="GET" action="index.php" style="display:flex; gap: 12px; align-items: end; flex-wrap: wrap;">
        <input type="hidden" name="controller" value="payment-matching">
        <input type="hidden" name="action" value="index">

        <div>
            <label for="status" style="display:block; margin-bottom: 4px;">Trạng thái</label>
            <select id="status" name="status" style="padding: 8px; min-width: 220px;">
                <option value="" <?= empty($filterStatus) ? 'selected' : '' ?>>Tất cả</option>
                <option value="MATCHED" <?= ($filterStatus ?? '') === 'MATCHED' ? 'selected' : '' ?>>Đã khớp</option>
                <option value="UNMATCHED" <?= ($filterStatus ?? '') === 'UNMATCHED' ? 'selected' : '' ?>>Chưa khớp</option>
                <option value="PENDING" <?= ($filterStatus ?? '') === 'PENDING' ? 'selected' : '' ?>>Chờ xử lý</option>
            </select>
        </div>

        <div>
            <button class="btn btn-primary" type="submit">Lọc</button>
            <a class="btn btn-secondary" href="index.php?controller=payment-matching&action=index">Xóa lọc</a>
        </div>
    </form>
</div>

<?php if (empty($payments)): ?>
    <div class="empty-state">Không có payment nào.</div>
<?php else: ?>
    <div class="table-container">
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
                    <?php if (($p['match_status'] ?? '') === 'MATCHED'): ?>
                        <span class="badge badge-success">Đã khớp</span>
                    <?php elseif (($p['match_status'] ?? '') === 'PENDING'): ?>
                        <span class="badge badge-warning">Chờ xử lý</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Chưa khớp</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (($p['match_status'] ?? '') !== 'MATCHED'): ?>
                        <a href="index.php?controller=payment-matching&action=selectHoaDon&payment_id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-primary">Khớp</a>
                    <?php else: ?>
                        <a href="index.php?controller=hoadon&action=view&id=<?= (int)$p['matched_hoadon_id'] ?>" class="btn btn-sm btn-secondary">Xem hóa đơn</a>
                        <form method="POST" action="index.php?controller=payment-matching&action=unmatch" style="display:inline;">
                            <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bỏ khớp payment này?')">Bỏ khớp</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="index.php?controller=payment-matching&action=index&page=<?= $i ?>&status=<?= urlencode((string)($filterStatus ?? '')) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

