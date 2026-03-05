<?php
/** @var array $invoices */

function formatVnd(int $amount): string
{
    return number_format($amount, 0, ',', '.') . ' đ';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In phiếu báo thu hàng loạt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 12pt; 
            line-height: 1.3; 
            background: #ccc;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }
        
        .invoice-item {
            width: 210mm;
            min-height: 297mm;
            background: white;
            padding: 15mm;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        
        @media print {
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            .invoice-item { 
                box-shadow: none; 
                page-break-after: always; 
                width: 100%;
                min-height: auto;
                margin: 0;
            }
            .invoice-item:last-child { page-break-after: auto; }
        }
        
        .no-print { text-align: center; padding: 10px; background: #f5f5f5; margin-bottom: 20px; }
        .print-header { text-align: center; margin-bottom: 15px; }
        .print-header h1 { font-size: 16pt; margin-bottom: 3px; }
        .print-header h2 { font-size: 12pt; font-weight: normal; }
        .invoice-info { margin-bottom: 10px; }
        .invoice-info p { margin-bottom: 3px; font-size: 11pt; }
        .invoice-info strong { display: inline-block; width: 100px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 11pt; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background: #f0f0f0; text-align: center; font-size: 10pt; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; }
        .signature { margin-top: 20px; display: flex; justify-content: space-between; }
        .signature div { width: 40%; text-align: center; }
        .signature .label { margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ In tất cả</button>
        <button onclick="window.history.back()">← Quay lại</button>
    </div>
    
    <?php foreach ($invoices as $invoice): ?>
    <?php $student = $invoice['student']; ?>
    <?php $items = $invoice['items']; ?>
    <div class="invoice-item">
        <div class="print-header">
            <h1>TRƯỜNG THỰC HÀNH SƯ PHẠM</h1>
            <h2>PHIẾU BÁO THU HỌC PHÍ - Tháng <?= $invoice['month'] ?>/<?= $invoice['year'] ?></h2>
        </div>
        
        <div class="invoice-info">
            <p><strong>Mã phiếu:</strong> <?= htmlspecialchars($invoice['invoice_code']) ?></p>
            <p><strong>Học sinh:</strong> <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['student_code']) ?>)</p>
            <p><strong>Lớp:</strong> <?= htmlspecialchars($student['class']) ?></p>
            <p><strong>Ngày lập:</strong> <?= date('d/m/Y', strtotime($invoice['issue_date'])) ?> | <strong>Hạn TT:</strong> <?= date('d/m/Y', strtotime($invoice['due_date'])) ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-center">STT</th>
                    <th>Khoản thu</th>
                    <th class="text-right">Số tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <td class="text-right"><?= number_format($item['amount'], 0, ',', '.') ?> đ</td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2" class="text-center">TỔNG CỘNG</td>
                    <td class="text-right"><?= number_format($invoice['total_amount'], 0, ',', '.') ?> đ</td>
                </tr>
            </tbody>
        </table>
        
        <p style="text-align: center; font-weight: bold; margin: 10px 0;">
            <?php
            $status = $invoice['status'];
            if ($status === 'paid') echo '<span style="color:green;">ĐÃ THANH TOÁN</span>';
            elseif ($status === 'partial') echo '<span style="color:orange;">THANH TOÁN MỘT PHẦN</span>';
            elseif ($status === 'cancelled') echo '<span style="color:gray;text-decoration:line-through;">ĐÃ HỦY</span>';
            else echo '<span style="color:red;">CHƯA THANH TOÁN</span>';
            ?>
        </p>
        
        <div class="signature">
            <div>
                <div class="label">Người lập phiếu</div>
                <p>(Ký và ghi rõ họ tên)</p>
            </div>
            <div>
                <div class="label">Phụ huynh</div>
                <p>(Ký và ghi rõ họ tên)</p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>
