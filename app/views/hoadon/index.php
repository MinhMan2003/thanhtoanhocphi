<?php
/** @var string $q */
/** @var array $result */
?>
<div class="page-header">
    <h1>Phiếu báo thu</h1>
    <div>
        <button type="button" class="btn btn-secondary" id="printSelectedBtn" style="display:none;">In chọn lọc</button>
        <a href="index.php?controller=hoadon&action=bulkCreate" class="btn btn-secondary">Tạo hàng loạt</a>
        <a href="index.php?controller=hoadon&action=create" class="btn btn-primary">+ Tạo phiếu mới</a>
    </div>
</div>

<form method="GET" action="index.php" class="search-form" id="searchForm">
    <input type="hidden" name="controller" value="hoadon">
    <input type="hidden" name="action" value="index">
    <input type="text" name="q" id="searchInput" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm theo mã phiếu, tên học sinh..." class="search-input">
    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
</form>
<div id="searchResults" class="search-autocomplete"></div>

<?php if (empty($result['items'])): ?>
    <div class="empty-state">Chưa có phiếu báo thu nào.</div>
<?php else: ?>
    <div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:40px;"><input type="checkbox" id="checkAll"></th>
                <th>Mã phiếu</th>
                <th>Học sinh</th>
                <th>Khối</th>
                <th>Lớp</th>
                <th>Tháng/Năm</th>
                <th>Số tiền</th>
                <th>Ngày lập</th>
                <th>Hạn thanh toán</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result['items'] as $row): ?>
            <tr>
                <td><input type="checkbox" class="invoice-check" value="<?= $row['id'] ?>"></td>
                <td><?= htmlspecialchars($row['invoice_code'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($row['student_code'], ENT_QUOTES, 'UTF-8') ?>)</td>
                <td><?= htmlspecialchars($row['khoi'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $row['month'] ?>/<?= $row['year'] ?></td>
                <td><?= number_format($row['total_amount'], 0, ',', '.') ?> đ</td>
                <td><?= htmlspecialchars($row['issue_date'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['due_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php
                    $statusClass = [
                        'pending' => 'status-pending',
                        'paid' => 'status-paid',
                        'partial' => 'status-partial',
                        'cancelled' => 'status-cancelled',
                    ];
                    $statusText = [
                        'pending' => 'Chưa thanh toán',
                        'paid' => 'Đã thanh toán',
                        'partial' => 'Thanh toán một phần',
                        'cancelled' => 'Đã hủy',
                    ];
                    ?>
                    <span class="status-badge <?= $statusClass[$row['status']] ?? '' ?>">
                        <?= $statusText[$row['status']] ?? $row['status'] ?>
                    </span>
                </td>
                <td>
                    <a href="index.php?controller=hoadon&action=view&id=<?= $row['id'] ?>" class="btn-link">Xem</a>
                    | <a href="index.php?controller=hoadon&action=pdf&id=<?= $row['id'] ?>" class="btn-link" target="_blank">PDF</a>
                    | <a href="index.php?controller=hoadon&action=giayBaoThuPdf&id=<?= $row['id'] ?>" class="btn-link" target="_blank">Giấy báo thu</a>
                    | <a href="index.php?controller=hoadon&action=edit&id=<?= $row['id'] ?>" class="btn-link">Sửa</a>
                    <?php if ($row['status'] !== 'paid'): ?>
                    | <a href="index.php?controller=hoadon&action=markPaid&id=<?= $row['id'] ?>" class="btn-link" onclick="return confirm('Đánh dấu đã thanh toán?')">ĐTT</a>
                    <?php endif; ?>
                    | <a href="index.php?controller=hoadon&action=delete&id=<?= $row['id'] ?>" class="btn-link btn-link-danger" onclick="return confirm('Xóa phiếu này?')">Xóa</a>
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
                <a href="index.php?controller=hoadon&action=index&page=<?= $i ?>&q=<?= urlencode($q) ?>" class="page-link"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.invoice-check');
    const printBtn = document.getElementById('printSelectedBtn');
    
    function updatePrintButton() {
        const checked = document.querySelectorAll('.invoice-check:checked');
        if (checked.length > 0) {
            printBtn.style.display = 'inline-block';
            printBtn.textContent = 'In (' + checked.length + ') phiếu';
        } else {
            printBtn.style.display = 'none';
        }
    }
    
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = checkAll.checked;
            });
            updatePrintButton();
        });
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updatePrintButton);
    });
    
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            const checked = document.querySelectorAll('.invoice-check:checked');
            if (checked.length > 0) {
                const ids = Array.from(checked).map(cb => cb.value).join(',');
                window.open('index.php?controller=in&action=invoiceBulk&ids=' + ids, '_blank');
            }
        });
    }
});

// Autocomplete search
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
            const response = await fetch(`index.php?controller=hoadon&action=searchAutocomplete&q=${encodeURIComponent(q)}`);
            const result = await response.json();
            
            if (result.success && result.data.length > 0) {
                const statusMap = {
                    'pending': ['Chưa thanh toán', '#f59e0b'],
                    'paid': ['Đã thanh toán', '#10b981'],
                    'partial': ['Thanh toán một phần', '#3b82f6'],
                    'cancelled': ['Đã hủy', '#ef4444']
                };
                const [statusText, statusColor] = statusMap[result.data[0].status] || [result.data[0].status, '#6b7280'];
                
                searchResults.innerHTML = result.data.map(item => `
                    <div class="search-autocomplete-item" onclick="window.location.href='index.php?controller=hoadon&action=view&id=${item.id}'">
                        <div>
                            <div class="name">${item.student_name} (${item.student_code})</div>
                            <div class="code">${item.invoice_code} - ${item.month}/${item.year} - ${new Intl.NumberFormat('vi-VN').format(item.total_amount)} đ</div>
                        </div>
                        <span class="badge" style="background:${statusColor};color:#fff;">${statusText}</span>
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
