<div class="page-header">
    <h1>Chọn hóa đơn để khớp</h1>
    <div>
        <a href="index.php?controller=payment-matching&action=unmatched" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<div class="card">
    <h3>Thông tin Payment</h3>
    <table class="info-table">
        <tr>
            <th>Mã giao dịch:</th>
            <td><?= htmlspecialchars($payment['trans_id']) ?></td>
        </tr>
        <tr>
            <th>Số tiền:</th>
            <td><strong><?= number_format($payment['amount'], 0, ',', '.') ?> đ</strong></td>
        </tr>
        <tr>
            <th>Nội dung:</th>
            <td><?= htmlspecialchars($payment['content'] ?? '') ?></td>
        </tr>
        <tr>
            <th>Người chuyển:</th>
            <td><?= htmlspecialchars($payment['account_name'] ?? '') ?> (<?= htmlspecialchars($payment['account_no'] ?? '') ?>)</td>
        </tr>
    </table>
</div>

<div class="card" style="margin-top: 1rem;">
    <h3>Danh sách hóa đơn chưa thanh toán</h3>
    <input type="text" id="searchHoaDon" placeholder="Tìm theo mã phiếu, tên học sinh..." style="width: 100%; padding: 8px; margin-bottom: 1rem;">
    
    <table class="data-table" id="hoadonTable">
        <thead>
            <tr>
                <th>Mã phiếu</th>
                <th>Học sinh</th>
                <th>Lớp</th>
                <th>Tháng/Năm</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody id="hoadonTableBody">
            <?php foreach ($hoadons as $hd): ?>
            <tr>
                <td><?= htmlspecialchars($hd['invoice_code']) ?></td>
                <td><?= htmlspecialchars($hd['student_name']) ?> (<?= htmlspecialchars($hd['student_code']) ?>)</td>
                <td><?= htmlspecialchars($hd['class']) ?></td>
                <td><?= $hd['month'] ?>/<?= $hd['year'] ?></td>
                <td><?= number_format($hd['total_amount'], 0, ',', '.') ?> đ</td>
                <td>
                    <?php if ($hd['status'] === 'paid'): ?>
                        <span class="badge badge-success">Đã thanh toán</span>
                    <?php elseif ($hd['status'] === 'partial'): ?>
                        <span class="badge badge-warning">Còn nợ</span>
                    <?php elseif ($hd['status'] === 'cancelled'): ?>
                        <span class="badge badge-danger">Đã hủy</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Chưa thanh toán</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($hd['status'] !== 'paid'): ?>
                    <form method="POST" action="index.php?controller=payment-matching&action=match" style="display:inline;">
                        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                        <input type="hidden" name="hoadon_id" value="<?= $hd['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Khớp payment với hóa đơn này?')">Khớp</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('searchHoaDon').addEventListener('input', async function() {
    const q = this.value;
    const tbody = document.getElementById('hoadonTableBody');
    
    try {
        const response = await fetch(`index.php?controller=payment-matching&action=searchHoaDon&q=${encodeURIComponent(q)}`);
        const result = await response.json();
        
        if (result.success) {
            tbody.innerHTML = result.data.map(hd => `
                <tr>
                    <td>${hd.invoice_code}</td>
                    <td>${hd.student_name} (${hd.student_code})</td>
                    <td>${hd.class}</td>
                    <td>${hd.month}/${hd.year}</td>
                    <td>${new Intl.NumberFormat('vi-VN').format(hd.total_amount)} đ</td>
                    <td>${hd.status === 'paid' ? '<span class="badge badge-success">Đã thanh toán</span>' : (hd.status === 'partial' ? '<span class="badge badge-warning">Còn nợ</span>' : (hd.status === 'cancelled' ? '<span class="badge badge-danger">Đã hủy</span>' : '<span class="badge badge-secondary">Chưa thanh toán</span>'))}</td>
                    <td>
                        ${hd.status !== 'paid' ? `<form method="POST" action="index.php?controller=payment-matching&action=match" style="display:inline;">
                            <input type="hidden" name="payment_id" value="${<?php echo $payment['id']; ?>}">
                            <input type="hidden" name="hoadon_id" value="${hd.id}">
                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Khớp payment với hóa đơn này?')">Khớp</button>
                        </form>` : ''}
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {
        console.error(e);
    }
});
</script>
