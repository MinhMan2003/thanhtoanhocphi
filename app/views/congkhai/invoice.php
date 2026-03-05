<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết phiếu báo thu - <?= \App\Core\Config::SCHOOL_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .content {
            padding: 25px;
        }
        .invoice-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .invoice-info table {
            width: 100%;
        }
        .invoice-info td {
            padding: 5px 0;
            font-size: 14px;
        }
        .invoice-info td:first-child {
            color: #666;
            width: 120px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table th,
        .items-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .items-table td:last-child {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background: #f8f9fa;
        }
        .status-box {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #f8d7da;
            color: #721c24;
        }
        .status-partial {
            background: #fff3cd;
            color: #856404;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            text-decoration: line-through;
        }
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn-back:hover {
            background: #5568d3;
        }
        .footer {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        .notification.show {
            transform: translateX(0);
        }
        .notification.success {
            background: #28a745;
        }
        .notification.info {
            background: #17a2b8;
        }
        .refresh-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .refresh-indicator .dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .payment-info {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .payment-info .amount {
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
        }
        .qr-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .qr-section h3 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .qr-section .qr-code {
            display: inline-block;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .qr-section .qr-code img {
            max-width: 280px;
            height: auto;
        }
        .qr-section .payment-details {
            margin-top: 15px;
            text-align: left;
            display: inline-block;
            background: white;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
        }
        .qr-section .payment-details div {
            margin: 8px 0;
        }
        .qr-section .payment-details .amount {
            font-size: 22px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin: 15px 0;
        }
        .qr-section .hint {
            margin-top: 15px;
            color: #666;
            font-size: 13px;
            font-style: italic;
        }
        .qr-section.paid {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PHIẾU BÁO THU HỌC PHÍ</h1>
            <p><?= \App\Core\Config::SCHOOL_NAME ?></p>
        </div>
        
        <div class="content">
            <div class="invoice-info">
                <table>
                    <tr>
                        <td>Mã phiếu:</td>
                        <td><strong><?= htmlspecialchars($invoice['invoice_code']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Học sinh:</td>
                        <td><strong><?= htmlspecialchars($student['full_name']) ?></strong> (<?= htmlspecialchars($student['student_code']) ?>)</td>
                    </tr>
                    <tr>
                        <td>Lớp:</td>
                        <td><?= htmlspecialchars($student['class']) ?></td>
                    </tr>
                    <tr>
                        <td>Tháng:</td>
                        <td><?= $invoice['month'] ?>/<?= $invoice['year'] ?></td>
                    </tr>
                    <tr>
                        <td>Ngày lập:</td>
                        <td><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></td>
                    </tr>
                    <tr>
                        <td>Hạn thanh toán:</td>
                        <td><?= $invoice['due_date'] ? date('d/m/Y', strtotime($invoice['due_date'])) : '-' ?></td>
                    </tr>
                </table>
            </div>
            
            <h3 style="margin-bottom: 10px;">Chi tiết các khoản thu</h3>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Nội dung</th>
                        <th style="text-align:right;">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($item['description'] ?? ($item['fee_category_name'] ?? '-')) ?></td>
                        <td><?= number_format($item['amount'], 0, ',', '.') ?> đ</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2" style="text-align:right;"><strong>TỔNG CỘNG:</strong></td>
                        <td><strong><?= number_format($invoice['total_amount'], 0, ',', '.') ?> đ</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <?php
            $paid = (int)($invoice['paid_amount'] ?? 0);
            $total = (int)$invoice['total_amount'];
            $status = $invoice['status'];
            // Only override status if not already cancelled and if there's payment info
            if ($status !== 'cancelled') {
                if ($paid >= $total && $total > 0) $status = 'paid';
                elseif ($paid > 0) $status = 'partial';
            }
            ?>
            
            <div class="status-box status-<?= $status ?>">
                <strong>
                    <?php if ($status === 'cancelled'): ?>
                    ✕ ĐÃ HỦY
                    <?php elseif ($status === 'paid'): ?>
                    ✓ ĐÃ THANH TOÁN ĐỦ
                    <?php elseif ($status === 'partial'): ?>
                    ◐ CÒN NỢ: <?= number_format($total - $paid, 0, ',', '.') ?> đ
                    <?php else: ?>
                    ✗ CHƯA THANH TOÁN
                    <?php endif; ?>
                </strong>
            </div>
            
            <?php if ($paid > 0 && $paid < $total): ?>
            <p style="text-align:center; color:#666; font-size:14px;">
                Đã thanh toán: <?= number_format($paid, 0, ',', '.') ?> đ
            </p>
            <?php endif; ?>
            
            <!-- QR Code Section - Only show when not fully paid -->
            <?php if ($status !== 'paid' && !empty($qrPayment)): ?>
            <div class="qr-section" id="qrSection">
                <h3>📱 QUÉT MÃ QR ĐỂ THANH TOÁN</h3>
                <div class="qr-code">
                    <img src="<?= htmlspecialchars($qrPayment['qr_image_url']) ?>" alt="QR thanh toán">
                </div>
                <div class="payment-details">
                    <div><strong>Ngân hàng:</strong> <?= htmlspecialchars($qrPayment['bank_id']) ?></div>
                    <div><strong>Số tài khoản:</strong> <?= htmlspecialchars($qrPayment['account_number']) ?></div>
                    <div><strong>Tên tài khoản:</strong> <?= htmlspecialchars($qrPayment['account_name']) ?></div>
                    <div class="amount"><?= number_format($qrPayment['amount'], 0, ',', '.') ?> đ</div>
                    <div><strong>Nội dung chuyển khoản:</strong> <span style="color: #dc3545;"><?= htmlspecialchars($qrPayment['invoice_code']) ?></span></div>
                </div>
                <div class="hint">💡 Mở ứng dụng ngân hàng và quét mã QR để thanh toán nhanh</div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($invoice['note'])): ?>
            <p style="margin-top:15px; font-size:13px; color:#666;">
                <strong>Ghi chú:</strong> <?= htmlspecialchars($invoice['note']) ?>
            </p>
            <?php endif; ?>
            
            <a href="index.php?controller=congkhai&action=pdf&id=<?= (int)$invoice['id'] ?>" class="btn-back" target="_blank">📥 Tải PDF</a>
            <a href="index.php?controller=congkhai&action=lookup" class="btn-back">← Tra cứu khác</a>
        </div>
        
        <div class="footer">
            <p>Hệ thống quản lý học phí - <?= \App\Core\Config::SCHOOL_NAME ?></p>
        </div>
    </div>
    
    <div id="notification" class="notification"></div>
    <div class="refresh-indicator">
        <span class="dot"></span>
        <span>Đang theo dõi thanh toán...</span>
    </div>
    
    <script>
    (function() {
        const invoiceId = <?= (int)$invoice['id'] ?>;
        const totalAmount = <?= (int)$invoice['total_amount'] ?>;
        let lastPaymentCount = <?= (int)($invoice['paid_amount'] > 0 ? 1 : 0) ?>;
        let lastPaidAmount = <?= (int)($invoice['paid_amount'] ?? 0) ?>;
        let isPaid = false;
        
        // Kiểm tra trạng thái thanh toán
        async function checkPaymentStatus() {
            try {
                const response = await fetch(`index.php?controller=congkhai&action=checkPaymentStatus&id=${invoiceId}`);
                const data = await response.json();
                
                if (data.success) {
                    // Cập nhật giao diện nếu có thay đổi
                    if (data.payment_count > lastPaymentCount) {
                        // Có thanh toán mới
                        showNotification(`Đã phát hiện thanh toán mới! +${formatNumber(data.paid_amount - lastPaidAmount)} đ`, 'success');
                        lastPaymentCount = data.payment_count;
                        lastPaidAmount = data.paid_amount;
                        updatePaymentDisplay(data);
                    } else if (data.paid_amount !== lastPaidAmount) {
                        // Số tiền thay đổi (có thể do cập nhật)
                        lastPaidAmount = data.paid_amount;
                        updatePaymentDisplay(data);
                    }
                    
                    // Kiểm tra nếu đã thanh toán đủ
                    if (data.status === 'paid' && !isPaid) {
                        isPaid = true;
                        showNotification('✓ Đã thanh toán đủ! Cảm ơn quý phụ huynh.', 'success');
                    }
                }
            } catch (error) {
                console.error('Lỗi kiểm tra thanh toán:', error);
            }
        }
        
        // Cập nhật hiển thị thanh toán
        function updatePaymentDisplay(data) {
            const statusBox = document.querySelector('.status-box');
            if (!statusBox) return;
            
            let statusClass = 'status-pending';
            let statusText = '✗ CHƯA THANH TOÁN';
            
            if (data.status === 'cancelled') {
                statusClass = 'status-cancelled';
                statusText = '✕ ĐÃ HỦY';
            } else if (data.status === 'paid') {
                statusClass = 'status-paid';
                statusText = '✓ ĐÃ THANH TOÁN ĐỦ';
            } else if (data.status === 'partial') {
                statusClass = 'status-partial';
                statusText = '◐ CÒN NỢ: ' + formatNumber(data.remaining_amount) + ' đ';
            }
            
            statusBox.className = 'status-box ' + statusClass;
            statusBox.innerHTML = '<strong>' + statusText + '</strong>';
            
            // Cập nhật thông tin đã thanh toán
            const existingPaidInfo = document.querySelector('.paid-info');
            if (data.paid_amount > 0) {
                if (existingPaidInfo) {
                    existingPaidInfo.textContent = 'Đã thanh toán: ' + formatNumber(data.paid_amount) + ' đ';
                } else {
                    const paidInfo = document.createElement('p');
                    paidInfo.className = 'paid-info';
                    paidInfo.style.cssText = 'text-align:center; color:#666; font-size:14px; margin-top:10px;';
                    paidInfo.textContent = 'Đã thanh toán: ' + formatNumber(data.paid_amount) + ' đ';
                    statusBox.after(paidInfo);
                }
            }
            
            // Ẩn indicator và QR nếu đã thanh toán đủ
            if (data.status === 'paid') {
                document.querySelector('.refresh-indicator').style.display = 'none';
                const qrSection = document.getElementById('qrSection');
                if (qrSection) {
                    qrSection.style.display = 'none';
                }
            }
        }
        
        // Hiển thị thông báo
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type + ' show';
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }
        
        // Định dạng số
        function formatNumber(num) {
            return new Intl.NumberFormat('vi-VN').format(num);
        }
        
        // Bắt đầu theo dõi - kiểm tra mỗi 3 giây
        if (!isPaid) {
            setInterval(checkPaymentStatus, 3000);
        }
    })();
    </script>
</body>
</html>
