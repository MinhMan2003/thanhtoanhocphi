<?php
/** @var array $result */
/** @var string $q */

$data = $result['data'] ?? [];
$total = (int)($result['total'] ?? 0);
$page = (int)($result['page'] ?? 1);
$limit = (int)($result['limit'] ?? 20);
$pages = max(1, (int)ceil($total / max(1, $limit)));

function format_vnd(int $amount): string
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

function translate_unit(string $unit): string
{
    $map = [
        'month' => 'theo tháng',
        'once'  => 'một lần',
        'year'  => 'theo năm',
    ];

    return $map[$unit] ?? $unit;
}
?>

<div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
    <h1 class="page-title" style="margin:0;">Khoản thu</h1>
    <a class="btn btn-primary" href="index.php?controller=loaikhphi&action=create">Tạo khoản thu</a>
</div>

<div class="card" style="margin-top:1rem;">
    <form method="get" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="controller" value="feecategory">
        <input type="hidden" name="action" value="index">
        <input class="form-control" id="searchInput" name="q" placeholder="Tìm theo tên khoản thu..." value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-secondary" type="submit">Tìm</button>
    </form>
</div>
<div id="searchResults" class="search-autocomplete"></div>

<div class="card" style="margin-top:1rem; padding:0;">
    <div style="padding:12px 16px; border-bottom:1px solid #e5e7eb;">
        <strong>Tổng:</strong> <?= $total ?> khoản thu
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Tên khoản</th>
                <th>Số tiền mặc định</th>
                <th>Đơn vị</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="6" class="text-muted">Chưa có dữ liệu.</td></tr>
            <?php else: ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($row['description'])): ?>
                                <div class="text-muted" style="font-size:0.85rem;"><?= htmlspecialchars((string)$row['description'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= format_vnd((int)$row['default_amount']) ?></td>
                        <td><?= htmlspecialchars(translate_unit((string)$row['unit']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= ((int)$row['is_active'] === 1) ? 'Đang dùng' : 'Ngưng dùng' ?></td>
                        <td style="white-space:nowrap; text-align:right;">
                            <a class="btn btn-secondary" href="index.php?controller=loaikhphi&action=edit&id=<?= (int)$row['id'] ?>">Sửa</a>
                            <a class="btn btn-secondary" href="index.php?controller=loaikhphi&action=delete&id=<?= (int)$row['id'] ?>" onclick="return confirm('Xóa khoản thu này?');">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pages > 1): ?>
    <div style="margin-top:1rem; display:flex; gap:6px; flex-wrap:wrap;">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a class="btn <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>"
               href="index.php?controller=loaikhphi&action=index&q=<?= urlencode($q) ?>&page=<?= $p ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
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
            const response = await fetch(`index.php?controller=loaikhphi&action=searchAutocomplete&q=${encodeURIComponent(q)}`);
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                searchResults.innerHTML = result.data.map(item => `
                    <div class="search-autocomplete-item" onclick="window.location.href='index.php?controller=loaikhphi&action=edit&id=${item.id}'">
                        <div>
                            <div class="name">${item.name}</div>
                            <div class="code">${new Intl.NumberFormat('vi-VN').format(item.default_amount)} đ - ${item.unit === 'month' ? 'theo tháng' : (item.unit === 'once' ? 'một lần' : item.unit)}</div>
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
});document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.remove('show');
    }
});
</script>
