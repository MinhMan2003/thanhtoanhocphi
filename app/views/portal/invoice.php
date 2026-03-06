<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết phiếu báo thu - <?= htmlspecialchars($pageTitle ?? 'Portal') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/css/portal.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 10px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: 600;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            font-size: 14px;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        
        .back-link:hover {
            opacity: 1;
        }
        
        .content {
            padding: 30px;
        }
        
        /* Status Banner */
        .status-banner {
            padding: 16px 24px;
            border-radius: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        
        .status-banner.paid {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-banner.pending {
            background: #fed7d7;
            color: #c53030;
        }
        
        .status-banner.partial {
            background: #fefcbf;
            color: #744210;
        }
        
        /* Invoice Info */
        .invoice-info {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .invoice-info h2 {
            color: #1a365d;
            font-size: 16px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 15px;
            color: #2d3748;
            font-weight: 500;
        }
        
        /* Items Table */
        .items-section {
            margin-bottom: 24px;
        }
        
        .items-section h3 {
            color: #1a365d;
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .items-table th {
            background: #1a365d;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .items-table .amount {
            text-align: right;
            font-family: 'Consolas', monospace;
            font-weight: 600;
        }
        
        /* Summary */
        .summary {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .summary-row.total {
            border-top: 2px solid #e2e8f0;
            margin-top: 8px;
            padding-top: 16px;
            font-size: 18px;
            font-weight: 700;
            color: #1a365d;
        }
        
        .summary-row .amount {
            font-family: 'Consolas', monospace;
            font-weight: 600;
        }
        
        /* QR Payment */
        .qr-payment {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border: 2px solid #48bb78;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
        }
        
        .qr-payment h3 {
            color: #22543d;
            font-size: 16px;
            margin-bottom: 16px;
        }
        
        .qr-image {
            max-width: 200px;
            margin: 0 auto 16px;
            display: block;
            border-radius: 8px;
        }
        
        .qr-amount {
            font-size: 24px;
            font-weight: 700;
            color: #22543d;
            margin-bottom: 8px;
        }
        
        .qr-note {
            font-size: 13px;
            color: #48bb78;
            font-weight: 500;
        }
        
        /* Action Buttons */
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 5px;
            }
            
            .container {
                border-radius: 10px;
            }
            
            .content {
                padding: 15px;
            }
            
            .header {
                padding: 12px 15px;
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 16px;
            }
            
            .header .back-link {
                font-size: 13px;
            }
            
            .status-banner {
                padding: 12px;
                font-size: 14px;
            }
            
            .invoice-info {
                padding: 15px;
            }
            
            .invoice-info h2 {
                font-size: 14px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .info-label {
                font-size: 11px;
            }
            
            .info-value {
                font-size: 14px;
            }
            
            .items-section h3 {
                font-size: 14px;
            }
            
            /* Table wrapper for scroll */
            .table-wrapper {
                margin: 0 -15px;
                padding: 0 15px;
                overflow-x: auto;
            }
            
            .items-table {
                font-size: 12px;
                min-width: 250px;
            }
            
            .items-table th,
            .items-table td {
                padding: 8px 6px;
            }
            
            .summary {
                padding: 15px;
            }
            
            .summary-row {
                font-size: 13px;
            }
            
            .summary-row.total {
                font-size: 16px;
            }
            
            .qr-payment {
                padding: 15px;
            }
            
            .qr-payment h3 {
                font-size: 14px;
            }
            
            .qr-image {
                max-width: 150px;
            }
            
            .qr-amount {
                font-size: 20px;
            }
            
            .qr-note {
                font-size: 11px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Chi tiết phiếu báo thu</h1>
            <a href="index.php?controller=portal&action=index" class="back-link">← Quay lại</a>
        </div>
        
        <div class="content">
            <!-- Trạng thái -->
            <?php if ($invoice['status'] === 'paid'): ?>
                <div class="status-banner paid">
                    ✓ Đã thanh toán đầy đủ
                </div>
            <?php elseif ($invoice['status'] === 'partial'): ?>
                <div class="status-banner partial">
                    ⚠ Đã thanh toán một phần
                </div>
            <?php else: ?>
                <div class="status-banner pending">
                    ⚠ Chưa thanh toán
                </div>
            <?php endif; ?>
            
            <!-- Thông tin hóa đơn -->
            <div class="invoice-info">
                <h2>Thông tin phiếu báo thu</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Mã phiếu</span>
                        <span class="info-value"><?= htmlspecialchars($invoice['invoice_code']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Học sinh</span>
                        <span class="info-value"><?= htmlspecialchars($invoice['student_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Lớp</span>
                        <span class="info-value"><?= htmlspecialchars($invoice['class']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tháng/Năm</span>
                        <span class="info-value">Tháng <?= $invoice['month'] ?>/<?= $invoice['year'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ngày lập</span>
                        <span class="info-value"><?= htmlspecialchars(date('d/m/Y', strtotime($invoice['issue_date']))) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Đã thanh toán</span>
                        <span class="info-value" style="color: #22543d; font-weight: 700;">
                            <?= number_format($invoice['paid_amount'], 0, ',', '.') ?> ₫
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Chi tiết các khoản thu -->
            <div class="items-section">
                <h3>Chi tiết các khoản thu</h3>
                <div class="table-wrapper">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Nội dung</th>
                            <th style="text-align: right;">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoice['items'] as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($item['description'] ?? $item['fee_category_name'] ?? '') ?></td>
                                <td class="amount"><?= number_format($item['amount'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            
            <!-- Tổng cộng -->
            <div class="summary">
                <div class="summary-row">
                    <span>Tổng cộng:</span>
                    <span class="amount"><?= number_format($invoice['total_amount'], 0, ',', '.') ?> ₫</span>
                </div>
                <?php if ($invoice['paid_amount'] > 0): ?>
                <div class="summary-row">
                    <span>Đã thanh toán:</span>
                    <span class="amount" style="color: #22543d;">- <?= number_format($invoice['paid_amount'], 0, ',', '.') ?> ₫</span>
                </div>
                <div class="summary-row total">
                    <span>Còn nợ:</span>
                    <span class="amount" style="color: #c53030;"><?= number_format($invoice['remaining_amount'], 0, ',', '.') ?> ₫</span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- QR Code thanh toán (chỉ hiển thị khi chưa thanh toán đủ) -->
            <?php if ($invoice['status'] !== 'paid' && !empty($qrPayment['qr_image_url'])): ?>
                <div class="qr-payment">
                    <h3>📱 Quét mã QR để thanh toán</h3>
                    <img src="<?= htmlspecialchars($qrPayment['qr_image_url']) ?>" alt="QR Code" class="qr-image">
                    <div class="qr-amount"><?= number_format($qrPayment['amount'], 0, ',', '.') ?> ₫</div>
                    <div class="qr-note">Vui lòng nhập đúng số tiền khi thanh toán</div>
                </div>
            <?php endif; ?>
            
            <!-- Nút hành động -->
            <div class="actions">
                <a href="index.php?controller=portal&action=index" class="btn btn-outline">Tra cứu khác</a>
            </div>
        </div>
    </div>
</body>
</html>
