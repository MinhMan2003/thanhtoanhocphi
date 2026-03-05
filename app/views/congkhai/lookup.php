<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu học phí - <?= \App\Core\Config::SCHOOL_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .lookup-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #004080;
        }
        .or-divider {
            text-align: center;
            margin: 15px 0;
            color: #666;
            font-size: 13px;
            position: relative;
        }
        .or-divider::before, .or-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #ddd;
        }
        .or-divider::before { left: 0; }
        .or-divider::after { right: 0; }
        .search-form {
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: #5568d3;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .student-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .student-info h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .student-info table {
            width: 100%;
        }
        .student-info td {
            padding: 5px 0;
        }
        .student-info td:first-child {
            font-weight: 500;
            color: #666;
            width: 120px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .invoice-table th,
        .invoice-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .invoice-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .invoice-table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
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
            background: #e2e3e5;
            color: #383d41;
            text-decoration: line-through;
        }
        .amount {
            text-align: right;
            font-weight: 500;
        }
        .view-link {
            color: #667eea;
            text-decoration: none;
        }
        .view-link:hover {
            text-decoration: underline;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tra cứu học phí</h1>
            <p><?= \App\Core\Config::SCHOOL_NAME ?></p>
        </div>
        
        <div class="content">
            <div class="lookup-info">
                <strong>Hướng dẫn:</strong> Nhập mã phiếu thu (VD: PT2026030001) kèm theo 
                ngày sinh học sinh HOẶC số điện thoại phụ huynh đã đăng ký.
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (!$student): ?>
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label for="receipt_code">Mã phiếu thu *</label>
                    <input type="text" id="receipt_code" name="receipt_code" required
                           placeholder="Nhập mã phiếu thu (VD: PT2026030001)"
                           value="<?= htmlspecialchars($_POST['receipt_code'] ?? '') ?>">
                </div>
                
                <div class="or-divider">HOẶC</div>
                
                <div class="form-group">
                    <label for="dob">Ngày sinh học sinh</label>
                    <input type="date" id="dob" name="dob" 
                           value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Số điện thoại phụ huynh</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="Số điện thoại đã đăng ký"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn">Tra cứu</button>
            </form>
            <?php else: ?>
            <div class="student-info">
                <h3>Thông tin học sinh</h3>
                <table>
                    <tr>
                        <td>Họ và tên:</td>
                        <td><strong><?= htmlspecialchars($student['full_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Mã học sinh:</td>
                        <td><?= htmlspecialchars($student['student_code']) ?></td>
                    </tr>
                    <tr>
                        <td>Lớp:</td>
                        <td><?= htmlspecialchars($student['class']) ?></td>
                    </tr>
                    <tr>
                        <td>Phụ huynh:</td>
                        <td><?= htmlspecialchars($student['parent_name'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
            
            <h3 style="margin-bottom: 15px; color: #333;">Danh sách phiếu báo thu</h3>
            
            <?php if (empty($invoices)): ?>
            <p style="color: #666; text-align: center; padding: 20px;">Chưa có phiếu báo thu nào.</p>
            <?php else: ?>
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Tháng/Năm</th>
                        <th>Mã phiếu</th>
                        <th class="amount">Tổng tiền</th>
                        <th class="amount">Đã thanh toán</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): 
                        $paid = (int)($inv['paid_amount'] ?? 0);
                        $total = (int)$inv['total_amount'];
                        $status = $inv['status'];
                        if ($paid >= $total && $total > 0) $status = 'paid';
                        elseif ($paid > 0) $status = 'partial';
                    ?>
                    <tr>
                        <td><?= $inv['month'] ?>/<?= $inv['year'] ?></td>
                        <td><?= htmlspecialchars($inv['invoice_code']) ?></td>
                        <td class="amount"><?= number_format($total, 0, ',', '.') ?> đ</td>
                        <td class="amount"><?= number_format($paid, 0, ',', '.') ?> đ</td>
                        <td>
                            <?php
                            $statusClass = [
                                'paid' => 'status-paid',
                                'partial' => 'status-partial',
                                'pending' => 'status-pending',
                                'cancelled' => 'status-cancelled',
                            ];
                            $statusText = [
                                'paid' => 'Đã thanh toán',
                                'partial' => 'Còn nợ',
                                'pending' => 'Chưa thanh toán',
                                'cancelled' => 'Đã hủy',
                            ];
                            ?>
                            <span class="status-badge <?= $statusClass[$status] ?? '' ?>">
                                <?= $statusText[$status] ?? $status ?>
                            </span>
                        </td>
                        <td>
                            <a href="index.php?controller=congkhai&action=invoice&id=<?= $inv['id'] ?>" class="view-link">Xem</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="index.php?controller=congkhai&action=lookup" class="btn" style="background: #666;">Tra cứu khác</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>Hệ thống quản lý học phí - <?= \App\Core\Config::SCHOOL_NAME ?></p>
            <p>Liên hệ: <?= \App\Core\Config::SCHOOL_PHONE ?></p>
        </div>
    </div>
</body>
</html>
