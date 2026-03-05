<?php
/** @var array $invoice */

$totalAmount = (int)($invoice['total_amount'] ?? 0);
$amountInWords = numberToVietnameseWords($totalAmount);

// Thông tin thanh toán QR
$qrPayment = getVietQRPaymentInfo($totalAmount, $invoice['invoice_code'] ?? '');

$monthNames = [
    1 => 'Tháng Một', 2 => 'Tháng Hai', 3 => 'Tháng Ba', 4 => 'Tháng Tư',
    5 => 'Tháng Năm', 6 => 'Tháng Sáu', 7 => 'Tháng Bảy', 8 => 'Tháng Tám',
    9 => 'Tháng Chín', 10 => 'Tháng Mười', 11 => 'Tháng Mười Một', 12 => 'Tháng Mười Hai'
];
$monthName = $monthNames[(int)$invoice['month']] ?? $invoice['month'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu báo thu học phí - <?= htmlspecialchars($invoice['invoice_code']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            line-height: 1.5;
            color: #000;
            background: #fff;
            padding: 20px;
        }
        .invoice-print {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .school-info {
            font-size: 13px;
            margin-top: 5px;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 10px;
            text-align: center;
        }
        .invoice-code-row {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .invoice-info {
            margin-bottom: 20px;
        }
        .invoice-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-info td {
            padding: 4px 8px;
        }
        .invoice-info .label {
            width: 120px;
            font-weight: normal;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .invoice-table th,
        .invoice-table td {
            border: 1px solid #000;
            padding: 10px 8px;
            text-align: left;
        }
        .invoice-table th {
            background: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .invoice-table .col-stt {
            width: 50px;
            text-align: center;
        }
        .invoice-table .col-content {
            width: auto;
        }
        .invoice-table .col-note {
            width: 180px;
        }
        .invoice-table .col-amount {
            width: 130px;
            text-align: right;
        }
        .invoice-table .amount {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background: #f5f5f5;
        }
        .total-row td {
            border-top: 2px solid #000;
        }
        .amount-in-words {
            margin: 15px 0;
            font-style: italic;
        }
        .amount-in-words strong {
            font-weight: bold;
        }
        .qr-payment {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border: 2px dashed #198754;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .qr-payment .qr-image {
            flex-shrink: 0;
        }
        .qr-payment .qr-image img {
            width: 220px;
            height: 220px;
            border: 1px solid #ddd;
        }
        .qr-payment .qr-info {
            flex: 1;
        }
        .qr-payment .qr-title {
            font-weight: bold;
            color: #198754;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .qr-payment .qr-detail {
            font-size: 13px;
            line-height: 1.8;
        }
        .qr-payment .qr-detail strong {
            color: #198754;
        }
        .qr-payment .scan-hint {
            margin-top: 8px;
            font-size: 12px;
            color: #6c757d;
            font-style: italic;
        }
        .invoice-note {
            margin: 15px 0;
            padding: 10px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            font-size: 13px;
        }
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 60px;
        }
        .signature-name {
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 150px;
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .btn-print:hover {
            background: #0b5ed7;
        }
        .btn-back {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 12px 24px;
            background: #6c757d;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        @media print {
            body {
                padding: 0;
            }
            .btn-print,
            .btn-back {
                display: none;
            }
            .invoice-print {
                padding: 0;
            }
            .qr-payment {
                display: none;
            }
        }
    </style>
</head>
<body>
    <a href="index.php?controller=hoadon&action=index" class="btn-back">&larr; Quay lại</a>
    <a href="index.php?controller=hoadon&action=pdf&id=<?= (int)($invoice['id'] ?? 0) ?>" class="btn-print" target="_blank">PDF Phiếu thu</a>
    <a href="index.php?controller=hoadon&action=giayBaoThuPdf&id=<?= (int)($invoice['id'] ?? 0) ?>" class="btn-print" target="_blank">PDF Giấy báo thu</a>
    <button onclick="window.print()" class="btn-print">In phiếu</button>

    <div class="invoice-print">
        <div class="invoice-header">
            <div class="school-name"><?= \App\Core\Config::SCHOOL_NAME ?></div>
            <div class="school-info">
                Địa chỉ: <?= \App\Core\Config::SCHOOL_ADDRESS ?> | Điện thoại: <?= \App\Core\Config::SCHOOL_PHONE ?>
            </div>
        </div>

        <div class="invoice-title">PHIẾU BÁO THU HỌC PHÍ</div>
        <div class="invoice-code-row">
            <strong>Mã phiếu: <?= htmlspecialchars($invoice['invoice_code']) ?></strong> | Ngày lập: <?= date('d/m/Y', strtotime($invoice['issue_date'])) ?>
        </div>

        <div class="invoice-info">
            <table>
                <tr>
                    <td class="label">Họ và tên học sinh:</td>
                    <td><strong><?= htmlspecialchars($invoice['student_name']) ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Mã học sinh:</td>
                    <td><?= htmlspecialchars($invoice['student_code']) ?></td>
                </tr>
                <tr>
                    <td class="label">Khối/Lớp:</td>
                    <td><?= htmlspecialchars($invoice['grade'] ?? '') ?> / <?= htmlspecialchars($invoice['class']) ?></td>
                </tr>
                <tr>
                    <td class="label">Tháng:</td>
                    <td><?= $monthName ?> năm <?= $invoice['year'] ?></td>
                </tr>
                <tr>
                    <td class="label">Hạn thanh toán:</td>
                    <td><?= $invoice['due_date'] ? date('d/m/Y', strtotime($invoice['due_date'])) : '' ?></td>
                </tr>
            </table>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th class="col-content">Nội dung thu</th>
                    <th class="col-note">Ghi chú</th>
                    <th class="col-amount">Số tiền (đ)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoice['items'])): ?>
                    <?php foreach ($invoice['items'] as $index => $item): ?>
                    <tr>
                        <td class="col-stt"><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($item['description'] ?: ($item['fee_category_name'] ?? '-')) ?></td>
                        <td></td>
                        <td class="amount"><?= number_format($item['amount'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;"><strong>CỘNG:</strong></td>
                        <td class="amount"><strong><?= number_format($totalAmount, 0, ',', '.') ?></strong></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">Không có chi tiết</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="amount-in-words">
            <strong>Số tiền bằng chữ:</strong> <?= $amountInWords ?>
        </div>

        <!-- QR Code thanh toán -->
        <div class="qr-payment">
            <div class="qr-image">
                <img src="<?= htmlspecialchars($qrPayment['qr_image_url']) ?>" alt="QR thanh toán">
            </div>
            <div class="qr-info">
                <div class="qr-title">QUÉT MÃ QR ĐỂ THANH TOÁN</div>
                <div class="qr-detail">
                    <div><strong>Ngân hàng:</strong> <?= htmlspecialchars($qrPayment['bank_id']) ?></div>
                    <div><strong>Số tài khoản:</strong> <?= htmlspecialchars($qrPayment['account_number']) ?></div>
                    <div><strong>Tên tài khoản:</strong> <?= htmlspecialchars($qrPayment['account_name']) ?></div>
                    <div><strong>Số tiền:</strong> <span style="color: #198754; font-weight: bold;"><?= number_format($qrPayment['amount'], 0, ',', '.') ?> đ</span></div>
                    <div><strong>Nội dung:</strong> <?= htmlspecialchars($qrPayment['invoice_code']) ?></div>
                </div>
                <div class="scan-hint">Quét mã QR bằng ứng dụng ngân hàng để thanh toán nhanh</div>
            </div>
        </div>

        <?php if (!empty($invoice['note'])): ?>
        <div class="invoice-note">
            <strong>Ghi chú:</strong> <?= htmlspecialchars($invoice['note']) ?>
        </div>
        <?php endif; ?>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-title">NGƯỜI LẬP PHIẾU</div>
                <?php $creatorName = $_SESSION['user_full_name'] ?? ''; $creatorName = ($creatorName === 'Quản trị viên') ? '' : $creatorName; ?>
                <div class="signature-name"><?= htmlspecialchars($creatorName) ?></div>
            </div>
            <div class="signature-box">
                <div class="signature-title">THỦ QUỸ</div>
                <div class="signature-name"></div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <em>Phiếu này có giá trị khi có đủ chữ ký và đóng dấu</em>
        </div>
    </div>
</body>
</html>
