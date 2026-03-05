<?php
/** @var string $q */
/** @var array $result */
/** @var array $stats */
?>
<div class="page-header">
    <h1>Thanh toán</h1>
    <a href="index.php?controller=thanhtoan&action=create" class="btn btn-primary">+ Thanh toán mới</a>
</div>

<form method="GET" action="index.php" class="search-form" style="margin-top:12px; margin-bottom:20px;" id="searchForm">
    <input type="hidden" name="controller" value="payment">
    <input type="hidden" name="action" value="index">
    <input type="text" name="q" id="searchInput" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm theo mã phiếu, tên học sinh..." class="search-input">
    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
</form>
<div id="searchResults" class="search-autocomplete"></div>

<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-label">Tổng thu</div>
        <div class="stat-value"><?= number_format($stats['total'], 0, ',', '.') ?> đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Hôm nay</div>
        <div class="stat-value"><?= number_format($stats['today'], 0, ',', '.') ?> đ</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tháng này</div>
        <div class="stat-value"><?= number_format($stats['month'], 0, ',', '.') ?> đ</div>
    </div>
</div>

<?php if (empty($result['items'])): ?>
    <div class="empty-state">Chưa có thanh toán nào.</div>
<?php else: ?>
    <div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Ngày thanh toán</th>
                <th>Mã phiếu</th>
                <th>Học sinh</th>
                <th>Số tiền phiếu</th>
                <th>Số tiền thanh toán</th>
                <th>Phương thức</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result['items'] as $index => $row): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($row['paid_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['invoice_code'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($row['student_code'], ENT_QUOTES, 'UTF-8') ?>)</td>
                <td><?= number_format($row['invoice_total'], 0, ',', '.') ?> đ</td>
                <td><?= number_format($row['amount'], 0, ',', '.') ?> đ</td>
                <td>
                    <?php
                    $methodText = [
                        'cash' => 'Tiền mặt',
                        'bank_transfer' => 'Chuyển khoản',
                        'vietqr' => 'VietQR',
                        'other' => 'Khác',
                    ];
                    ?>
                    <?= $methodText[$row['payment_method']] ?? $row['payment_method'] ?>
                </td>
                <td>
                    <a href="index.php?controller=thanhtoan&action=view&id=<?= $row['id'] ?>" class="btn-link">Xem</a>
                    | <a href="index.php?controller=thanhtoan&action=edit&id=<?= $row['id'] ?>" class="btn-link">Sửa</a>
                    | <a href="index.php?controller=thanhtoan&action=delete&id=<?= $row['id'] ?>" class="btn-link btn-link-danger" onclick="return confirm('Xóa thanh toán này?')">Xóa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if ($result['totalPages'] > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
            <?php if ($i == $result['page']): ?>
                <span class="page-current"><?= $i ?></span>
            <?php else: ?>
                <a href="index.php?controller=thanhtoan&action=index&page=<?= $i ?>&q=<?= urlencode($q) ?>" class="page-link"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<script>
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
let debounceTimer;

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const q = this.value.trim();
    
    if (q.length < 1) {
        searchResults.classList.remove('show');
        return;
    }
    
    debounceTimer = setTimeout(async function() {
        try {
            const response = await fetch(`index.php?controller=thanhtoan&action=searchAutocomplete&q=${encodeURIComponent(q)}`);
            const result = await response.json();
            
            if (result.success && result.data.length > 0) {
                const methodMap = {
                    'cash': 'Tiền mặt',
                    'bank_transfer': 'Chuyển khoản',
                    'vietqr': 'VietQR',
                    'other': 'Khác'
                };
                
                searchResults.innerHTML = result.data.map(item => `
                    <div class="search-autocomplete-item" onclick="window.location.href='index.php?controller=thanhtoan&action=view&id=${item.id}'">
                        <div>
                            <div class="name">${item.student_name || 'N/A'} (${item.student_code || 'N/A'})</div>
                            <div class="code">${item.invoice_code || 'N/A'} - ${new Intl.NumberFormat('vi-VN').format(item.amount)} đ - ${methodMap[item.payment_method] || item.payment_method}</div>
                        </div>
                    </div>
                `).join('');
                searchResults.classList.add('show');
            } else {
                searchResults.classList.remove('show');
            }
        } catch (e) {
            console.error(e);
        }
    }, 200);
});

document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.remove('show');
    }
});
</script>
