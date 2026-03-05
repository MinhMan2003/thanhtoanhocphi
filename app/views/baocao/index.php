<?php
/** @var int $month */
/** @var int $year */
/** @var array $stats */
/** @var array $topStudents */
/** @var array $classStats */

function formatVnd(int $amount): string
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

$percentPaid = $stats['total_invoices'] > 0 ? round(($stats['paid_count'] / $stats['total_invoices']) * 100) : 0;
$percentCollected = $stats['total_amount'] > 0 ? round(($stats['collected'] / $stats['total_amount']) * 100) : 0;
$percentPartial = $stats['total_invoices'] > 0 ? round(($stats['partial_count'] / $stats['total_invoices']) * 100) : 0;
?>

<div class="page-header">
    <h1>Báo cáo & Thống kê</h1>
    <div style="display:flex; gap:8px;">
        <form method="GET" style="display:flex; gap:8px;">
            <input type="hidden" name="controller" value="baocao">
            <input type="hidden" name="action" value="index">
            <select name="month" class="form__input" style="min-width:60px;" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>><?= $m ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form__input" style="min-width:90px;" onchange="this.form.submit()">
                <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </form>
        <a href="index.php?controller=baocao&action=export&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-secondary" target="_blank">Xuất CSV</a>
        <a href="index.php?controller=in&action=report&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-secondary" target="_blank">In báo cáo</a>
    </div>
</div>

<?php if ($stats['total_invoices'] > 0): ?>

<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-label">Tổng phiếu</div>
        <div class="stat-value"><?= $stats['total_invoices'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Tổng tiền</div>
        <div class="stat-value"><?= formatVnd($stats['total_amount']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Đã thu</div>
        <div class="stat-value" style="color:#16a34a;"><?= formatVnd($stats['collected']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Chưa thu</div>
        <div class="stat-value" style="color:#dc2626;"><?= formatVnd($stats['uncollected']) ?></div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem;">
    <div class="card">
        <h3 style="margin-top:0;">Trạng thái phiếu (<?= $percentPaid ?>%)</h3>
        <div style="display:flex; height:24px; border-radius:4px; overflow:hidden; background:#e5e7eb;">
            <?php if ($stats['paid_count'] > 0): ?>
            <div style="width:<?= $percentPaid ?>%; background:#16a34a;" title="Đã thanh toán: <?= $stats['paid_count'] ?>"></div>
            <?php endif; ?>
            <?php if ($stats['partial_count'] > 0): ?>
            <div style="width:<?= $percentPartial ?>%; background:#f59e0b;" title="Thanh toán một phần: <?= $stats['partial_count'] ?>"></div>
            <?php endif; ?>
            <?php if ($stats['pending_count'] > 0): ?>
            <?php $percentPending = max(0, 100 - $percentPaid - $percentPartial); ?>
            <div style="width:<?= $percentPending ?>%; background:#dc2626;" title="Chưa thanh toán: <?= $stats['pending_count'] ?>"></div>
            <?php endif; ?>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:8px; font-size:0.875rem;">
            <span style="color:#16a34a;">✓ <?= $stats['paid_count'] ?> đã TT</span>
            <span style="color:#f59e0b;">◐ <?= $stats['partial_count'] ?> còn nợ</span>
            <span style="color:#dc2626;">✗ <?= $stats['pending_count'] ?> chưa TT</span>
        </div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Tỷ lệ thu (<?= $percentCollected ?>%)</h3>
        <div style="display:flex; height:24px; border-radius:4px; overflow:hidden; background:#e5e7eb;">
            <div style="width:<?= $percentCollected ?>%; background:#16a34a;"></div>
            <div style="width:<?= 100 - $percentCollected ?>%; background:#dc2626;"></div>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:8px; font-size:0.875rem;">
            <span style="color:#16a34a;"><?= formatVnd($stats['collected']) ?></span>
            <span style="color:#dc2626;"><?= formatVnd($stats['uncollected']) ?></span>
        </div>
    </div>
</div>

<?php if (!empty($classStats)): ?>
<div class="card" style="margin-top:1rem;">
    <h3 style="margin-top:0;">Thống kê theo lớp (Năm <?= $year ?>)</h3>
    <div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Khối</th>
                <th>Lớp</th>
                <th>Số HS</th>
                <th>Số phiếu</th>
                <th>Tổng tiền</th>
                <th>Đã thu</th>
                <th>Còn nợ</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classStats as $cs): 
                $percent = $cs['total_amount'] > 0 ? round(($cs['collected'] / $cs['total_amount']) * 100) : 0;
                $remaining = max(0, $cs['total_amount'] - $cs['collected']);
            ?>
            <tr>
                <td><?= htmlspecialchars($cs['khoi'] ?? '') ?></td>
                <td><?= htmlspecialchars($cs['class']) ?></td>
                <td><?= $cs['student_count'] ?></td>
                <td><?= $cs['invoice_count'] ?></td>
                <td><?= formatVnd($cs['total_amount']) ?></td>
                <td style="color:#16a34a;"><?= formatVnd($cs['collected']) ?></td>
                <td style="color:#dc2626;"><?= formatVnd($remaining) ?></td>
                <td><?= $percent ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($topStudents)): ?>
<div class="card" style="margin-top:1rem;">
    <h3 style="margin-top:0;">Học sinh thanh toán nhiều nhất (Năm <?= $year ?>)</h3>
    <div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Mã HS</th>
                <th>Họ tên</th>
                <th>Khối</th>
                <th>Lớp</th>
                <th>Tổng phiếu</th>
                <th>Đã thanh toán</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topStudents as $index => $s): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($s['student_code']) ?></td>
                <td><?= htmlspecialchars($s['full_name']) ?></td>
                <td><?= htmlspecialchars($s['grade'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['class']) ?></td>
                <td><?= formatVnd((int)$s['total_invoice']) ?></td>
                <td style="color:#16a34a;"><?= formatVnd((int)$s['total_paid']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card" style="margin-top:1rem;">
    <p class="text-muted" style="text-align:center; padding:2rem;">Chưa có dữ liệu cho tháng <?= $month ?>/<?= $year ?></p>
</div>
<?php endif; ?>
