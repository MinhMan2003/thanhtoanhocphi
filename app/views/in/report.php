<?php
/** @var int $month */
/** @var int $year */
/** @var array $stats */
/** @var array $classStats */

function formatVnd(int $amount): string
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

$percentPaid = $stats['total_invoices'] > 0 ? round(($stats['paid_count'] / $stats['total_invoices']) * 100) : 0;
$percentCollected = $stats['total_amount'] > 0 ? round(($stats['collected'] / $stats['total_amount']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tháng <?= $month ?>/<?= $year ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.4; padding: 20px; }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
        .no-print { text-align: center; padding: 10px; background: #f5f5f5; margin-bottom: 20px; }
        .print-header { text-align: center; margin-bottom: 20px; }
        .print-header h1 { font-size: 18pt; margin-bottom: 5px; }
        .print-header h2 { font-size: 14pt; font-weight: normal; }
        .print-header p { font-size: 11pt; color: #666; }
        
        .summary-section { margin-bottom: 25px; }
        .summary-section h3 { font-size: 14pt; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px; }
        
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .summary-item { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .summary-item .label { font-size: 10pt; color: #666; margin-bottom: 5px; }
        .summary-item .value { font-size: 16pt; font-weight: bold; }
        .summary-item.highlight { background: #f9f9f9; }
        
        .progress-bar { height: 20px; background: #e0e0e0; border-radius: 3px; overflow: hidden; margin: 10px 0; }
        .progress-bar .fill { height: 100%; }
        .progress-bar .fill.green { background: #4caf50; }
        .progress-bar .fill.red { background: #f44336; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 11pt; }
        th { background: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .status-paid { color: green; }
        .status-pending { color: red; }
        .status-partial { color: orange; }
        .status-cancelled { color: gray; text-decoration: line-through; }
        
        .footer { margin-top: 30px; text-align: center; font-style: italic; font-size: 10pt; color: #666; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ In báo cáo</button>
        <button onclick="window.close()">← Quay lại</button>
    </div>
    
    <div class="print-header">
        <h1>BÁO CÁO THU HỌC PHÍ</h1>
        <h2>Tháng <?= $month ?>/<?= $year ?></h2>
        <p>Ngày in: <?= date('d/m/Y H:i') ?></p>
    </div>
    
    <?php if ($stats['total_invoices'] > 0): ?>
    
    <div class="summary-section">
        <h3>TỔNG QUAN</h3>
        
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Tổng số phiếu</div>
                <div class="value"><?= $stats['total_invoices'] ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Tổng tiền</div>
                <div class="value"><?= formatVnd($stats['total_amount']) ?></div>
            </div>
            <div class="summary-item highlight">
                <div class="label">Đã thu</div>
                <div class="value" style="color: green;"><?= formatVnd($stats['collected']) ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Còn nợ</div>
                <div class="value" style="color: red;"><?= formatVnd($stats['uncollected']) ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Đã thanh toán</div>
                <div class="value"><?= $stats['paid_count'] ?> phiếu</div>
            </div>
            <div class="summary-item">
                <div class="label">Chưa/Còn nợ</div>
                <div class="value"><?= $stats['pending_count'] + $stats['partial_count'] ?> phiếu</div>
            </div>
        </div>
        
        <table style="width: 60%; margin: 0 auto;">
            <tr>
                <td style="width: 50%;">Tỷ lệ phiếu đã thanh toán:</td>
                <td class="text-right"><strong><?= $percentPaid ?>%</strong></td>
            </tr>
            <tr>
                <td>Tỷ lệ tiền đã thu:</td>
                <td class="text-right"><strong><?= $percentCollected ?>%</strong></td>
            </tr>
        </table>
    </div>
    
    <?php if (!empty($classStats)): ?>
    <div class="summary-section">
        <h3>THỐNG KÊ THEO LỚP</h3>
        
        <table>
            <thead>
                <tr>
                    <th class="text-center">STT</th>
                    <th>Lớp</th>
                    <th class="text-center">Số HS</th>
                    <th class="text-center">Số phiếu</th>
                    <th class="text-right">Tổng tiền</th>
                    <th class="text-right">Đã thu</th>
                    <th class="text-right">Còn nợ</th>
                    <th class="text-center">Tỷ lệ</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalStudents = 0;
                $totalInvoices = 0;
                $totalAmount = 0;
                $totalCollected = 0;
                ?>
                <?php foreach ($classStats as $index => $cs): 
                    $percent = $cs['total_amount'] > 0 ? round(($cs['collected'] / $cs['total_amount']) * 100) : 0;
                    $remaining = max(0, $cs['total_amount'] - $cs['collected']);
                    $totalStudents += $cs['student_count'];
                    $totalInvoices += $cs['invoice_count'];
                    $totalAmount += $cs['total_amount'];
                    $totalCollected += $cs['collected'];
                ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($cs['class']) ?></td>
                    <td class="text-center"><?= $cs['student_count'] ?></td>
                    <td class="text-center"><?= $cs['invoice_count'] ?></td>
                    <td class="text-right"><?= formatVnd($cs['total_amount']) ?></td>
                    <td class="text-right" style="color: green;"><?= formatVnd($cs['collected']) ?></td>
                    <td class="text-right" style="color: red;"><?= formatVnd($remaining) ?></td>
                    <td class="text-center"><?= $percent ?>%</td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background: #f5f5f5;">
                    <td colspan="2" class="text-center">TỔNG CỘNG</td>
                    <td class="text-center"><?= $totalStudents ?></td>
                    <td class="text-center"><?= $totalInvoices ?></td>
                    <td class="text-right"><?= formatVnd($totalAmount) ?></td>
                    <td class="text-right" style="color: green;"><?= formatVnd($totalCollected) ?></td>
                    <td class="text-right" style="color: red;"><?= formatVnd(max(0, $totalAmount - $totalCollected)) ?></td>
                    <td class="text-center"><?= $totalAmount > 0 ? round(($totalCollected / $totalAmount) * 100) : 0 ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <p style="text-align: center; padding: 40px; color: #666;">Chưa có dữ liệu cho tháng <?= $month ?>/<?= $year ?></p>
    <?php endif; ?>
    
    <div class="footer">
        <p>Báo cáo được tạo tự động từ Hệ thống Quản lý Học phí - Trường Thực Hành Sư Phạm</p>
    </div>
</body>
</html>
