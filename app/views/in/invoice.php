<?php
/** @var array $invoice */
/** @var array $items */
/** @var array $student */
/** @var array $qrInfo */

// Load helper if not autoloaded
if (!function_exists('numberToVietnameseWords')) {
    require_once __DIR__ . '/../../helpers/number_to_words.php';
}

function formatVnd(int $amount): string
{
    return number_format($amount, 0, ',', '.');
}

// Calculate total
$total = 0;
foreach ($items as $item) {
    $total += (int)$item['amount'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giấy Báo Thu Và Thanh Toán</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12px;
            line-height: 1.4;
            background: #ccc;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }

        /* Khổ A4, căn giữa */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 20mm 18mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            position: relative;
        }

        /* Viền khung nội dung */
        .border-box {
            border: 1px solid #333;
            padding: 15px;
            min-height: calc(297mm - 40mm);
        }

        /* ==================== HEADER GÓC TRÁI ==================== */
        .header-left {
            margin-bottom: 15px;
        }

        .school-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .school-address, .school-phone {
            font-size: 11px;
            color: #333;
        }

        /* ==================== TIÊU ĐỀ ==================== */
        .title-section {
            text-align: center;
            margin-bottom: 15px;
        }

        .title-main {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title-period {
            font-size: 12px;
            font-style: italic;
        }

        /* ==================== THÔNG TIN HỌC SINH ==================== */
        .student-info {
            margin-bottom: 10px;
        }

        .student-info div {
            font-size: 12px;
            margin-bottom: 2px;
        }

        /* ==================== DÒNG NGÀY ĂN ==================== */
        .meal-info {
            font-size: 12px;
            font-style: italic;
            text-align: center;
            margin-bottom: 10px;
        }

        /* ==================== BẢNG ==================== */
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .fee-table th,
        .fee-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 11px;
        }

        .fee-table th {
            background: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }

        .fee-table .stt {
            width: 35px;
            text-align: center;
        }

        .fee-table .content {
            width: auto;
        }

        .fee-table .note-col {
            width: 80px;
        }

        .fee-table .amount-col {
            width: 90px;
            text-align: right;
        }

        .fee-table .amount-col.right {
            text-align: right;
        }

        /* Dòng tổng kết trong bảng */
        .fee-table .total-row {
            font-weight: bold;
            background: #f9f9f9;
        }

        /* ==================== VIẾT BẰNG CHỮ ==================== */
        .amount-text {
            font-size: 11px;
            margin-bottom: 10px;
        }

        /* ==================== DÒNG ĐỎ CẢNH BÁO ==================== */
        .warning-red {
            font-size: 10px;
            color: #ff0000;
            font-style: italic;
            text-align: center;
            margin-bottom: 15px;
        }

        /* ==================== FOOTER ==================== */
        .footer {
            font-size: 10px;
            text-align: right;
            position: absolute;
            bottom: 15px;
            right: 18mm;
            left: 18mm;
        }

        /* ==================== PRINT ==================== */
        @media print {
            body {
                padding: 0;
                background: white;
            }

            .page {
                box-shadow: none;
                margin: 0;
                padding: 15mm;
            }

            .border-box {
                border: 1px solid #333;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Nút in */
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        .no-print button {
            padding: 10px 25px;
            font-size: 14px;
            cursor: pointer;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            margin: 0 5px;
        }

        .no-print button:hover {
            background: #0b5ed7;
        }
    </style>
</head>
<body>
    <!-- Nút in -->
    <div class="no-print">
        <button onclick="window.print()">🖨️ In phiếu</button>
        <button onclick="window.history.back()">← Quay lại</button>
    </div>

    <div class="page">
        <div class="border-box">
            <!-- Header góc trái -->
            <div class="header-left">
                <div class="school-name">Trường Thực Hành Sư Phạm</div>
                <div class="school-address">Địa chỉ: Thành phố Trà Vinh</div>
                <div class="school-phone">Điện thoại: Chưa có thông tin</div>
            </div>

            <!-- Tiêu đề giữa -->
            <div class="title-section">
                <div class="title-main">GIẤY BÁO THU VÀ THANH TOÁN</div>
                <div class="title-period">Cả Năm, Niên học <?= $invoice['year'] ?> - <?= ((int)$invoice['year'] + 1) ?></div>
            </div>

            <!-- Thông tin học sinh -->
            <div class="student-info">
                <div>- Học sinh: <?= htmlspecialchars($invoice['student_name'] ?? $student['full_name'] ?? '') ?></div>
                <div>- Lớp học: <?= htmlspecialchars($invoice['class'] ?? $student['class'] ?? '') ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ngày sinh: <?= htmlspecialchars($invoice['dob'] ?? $student['dob'] ?? '') ?></div>
                <div>- Mã học sinh: <?= htmlspecialchars($invoice['student_code'] ?? $student['student_code'] ?? '') ?></div>
            </div>

            <!-- Dòng số ngày ăn -->
            <div class="meal-info">(Số báo ngày ăn: <?= htmlspecialchars($invoice['meal_days'] ?? '0') ?> ngày)</div>

            <!-- Bảng phí -->
            <table class="fee-table">
                <thead>
                    <tr>
                        <th class="stt">STT</th>
                        <th class="content">Nội dung</th>
                        <th class="note-col">Ghi chú</th>
                        <th class="amount-col">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td class="stt"><?= $index + 1 ?></td>
                        <td class="content"><?= htmlspecialchars($item['description']) ?></td>
                        <td class="note-col"><?= htmlspecialchars($item['note'] ?? '') ?></td>
                        <td class="amount-col right"><?= formatVnd((int)$item['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Dòng tổng kết -->
                    <tr>
                        <td colspan="3" class="content">- Công nợ kỳ này:</td>
                        <td class="amount-col right"><?= formatVnd($total) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="content">- Nợ kỳ trước:</td>
                        <td class="amount-col right">0</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="content">- Khấu trừ kỳ trước:</td>
                        <td class="amount-col right">0</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="content">- Khấu trừ kỳ này:</td>
                        <td class="amount-col right">0</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" class="content"><strong>- Tổng cộng:</strong></td>
                        <td class="amount-col right"><strong><?= formatVnd($total) ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <!-- Viết bằng chữ -->
            <div class="amount-text">
                - Viết bằng chữ: <?= numberToVietnameseWords($total) ?>
            </div>

            <!-- Dòng đỏ cảnh báo -->
            <div class="warning-red">
                * Vui lòng nhập đúng số tiền khi thanh toán liên ngân hàng qua QRCode
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            In: <?= date('d/m/Y') ?>&nbsp;&nbsp;:&nbsp;Người lập: Admin trường Trường Thực Hành Sư Phạm
        </div>
    </div>
</body>
</html>
