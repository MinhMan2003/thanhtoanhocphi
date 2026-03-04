<?php
/** @var array $result */
/** @var string $q */

$data = $result['data'] ?? [];
$total = (int)($result['total'] ?? 0);
$page = (int)($result['page'] ?? 1);
$limit = (int)($result['limit'] ?? 20);
$pages = max(1, (int)ceil($total / max(1, $limit)));
?>

<div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
    <h1 class="page-title" style="margin:0;">Học sinh</h1>
    <div style="display:flex; gap:8px;">
        <a class="btn btn-secondary" href="index.php?controller=hocsinh&action=import">Import</a>
        <a class="btn btn-primary" href="index.php?controller=hocsinh&action=create">Thêm học sinh</a>
    </div>
</div>

<?php
$imported = (int)($_GET['imported'] ?? 0);
if ($imported > 0): ?>
    <div class="alert" style="background:#d4edda; color:#155724; padding:12px; border-radius:4px; margin-top:1rem;">
        Đã import thành công <?= $imported ?> học sinh.
    </div>
<?php endif; ?>

<div class="card" style="margin-top:1rem;">
    <form method="get" style="display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="controller" value="student">
        <input type="hidden" name="action" value="index">
        <input class="form-control" name="q" placeholder="Tìm theo mã, tên, lớp..." value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn btn-secondary" type="submit">Tìm</button>
    </form>
</div>

<div class="card" style="margin-top:1rem; padding:0;">
    <div style="padding:12px 16px; border-bottom:1px solid #e5e7eb;">
        <strong>Tổng:</strong> <?= $total ?> học sinh
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Mã học sinh</th>
                <th>Họ tên</th>
                <th>Khối</th>
                <th>Lớp</th>
                <th>Ngày sinh</th>
                <th>Địa chỉ</th>
                <th>Phụ huynh</th>
                <th>SĐT</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="11" class="text-muted">Chưa có dữ liệu.</td></tr>
            <?php else: ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= htmlspecialchars($row['student_code'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($row['grade'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($row['dob'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($row['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($row['parent_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($row['parent_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $row['status'] === 'inactive' ? 'Nghỉ' : 'Đang học' ?></td>
                        <td style="white-space:nowrap; text-align:right;">
                            <a class="btn btn-secondary" href="index.php?controller=hocsinh&action=view&id=<?= (int)$row['id'] ?>">Xem</a>
                            <a class="btn btn-secondary" href="index.php?controller=hocsinh&action=edit&id=<?= (int)$row['id'] ?>">Sửa</a>
                            <a class="btn btn-secondary" href="index.php?controller=hocsinh&action=delete&id=<?= (int)$row['id'] ?>" onclick="return confirm('Xóa học sinh này?');">Xóa</a>
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
               href="index.php?controller=hocsinh&action=index&q=<?= urlencode($q) ?>&page=<?= $p ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

