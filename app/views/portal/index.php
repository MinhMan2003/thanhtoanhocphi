<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu thông tin học sinh - <?= htmlspecialchars($pageTitle ?? 'Portal') ?></title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .header {
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        /* Form Styles */
        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
        }
        
        .tabs .tab {
            flex: 1;
            padding: 14px 20px;
            text-align: center;
            background: #f7fafc;
            color: #4a5568;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .tabs .tab:hover {
            background: #edf2f7;
        }
        
        .tabs .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .lookup-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #a0aec0;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        /* Student Info & Invoices */
        .student-info {
            background: #f7fafc;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .student-info h2 {
            color: #1a365d;
            font-size: 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .student-info .info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 12px;
        }
        
        .student-info .info-item {
            flex: 1;
            min-width: 150px;
        }
        
        .student-info .info-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .student-info .info-value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 500;
        }
        
        .invoices-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        
        .invoices-table th {
            background: #1a365d;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .invoices-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        
        .invoices-table tr:hover {
            background: #f7fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-paid {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-pending {
            background: #fed7d7;
            color: #c53030;
        }
        
        .status-partial {
            background: #fefcbf;
            color: #744210;
        }
        
        .btn-view {
            display: inline-block;
            padding: 6px 16px;
            background: #667eea;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-view:hover {
            background: #5a67d8;
        }
        
        .logout-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-link:hover {
            text-decoration: underline;
        }
        
        .no-invoices {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        .amount {
            font-family: 'Consolas', monospace;
            font-weight: 600;
        }
        
        /* Table wrapper for scroll */
        .table-wrapper {
            margin: 0;
            padding: 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 5px;
                align-items: flex-start;
            }
            
            .container {
                border-radius: 10px;
            }
            
            .content {
                padding: 15px;
            }
            
            .header {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 16px;
            }
            
            .header p {
                font-size: 11px;
            }
            
            .lookup-form {
                padding: 0;
            }
            
            .form-group {
                margin-bottom: 16px;
            }
            
            .form-group label {
                font-size: 13px;
            }
            
            .form-group input {
                padding: 12px;
                font-size: 14px;
            }
            
            .btn {
                padding: 12px;
                font-size: 14px;
            }
            
            .tabs {
                flex-direction: row;
                margin-bottom: 16px;
            }
            
            .tabs .tab {
                padding: 10px 8px;
                font-size: 13px;
            }
            
            .student-info {
                padding: 15px;
            }
            
            .student-info h2 {
                font-size: 16px;
            }
            
            .student-info .info-item {
                min-width: 100%;
            }
            
            .student-info .info-row {
                gap: 12px;
            }
            
            h3 {
                font-size: 15px !important;
            }
            
            /* Table responsive */
            .table-wrapper {
                margin: 0 -15px;
                padding: 0 15px;
            }
            
            .invoices-table {
                font-size: 11px;
                min-width: 700px;
            }
            
            .invoices-table th,
            .invoices-table td {
                padding: 8px 6px;
                white-space: nowrap;
            }
            
            .status-badge {
                font-size: 10px;
                padding: 3px 8px;
            }
            
            .btn-view {
                padding: 5px 10px;
                font-size: 11px;
            }
            
            .logout-link {
                font-size: 13px;
                margin-top: 15px;
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars(\App\Core\Config::SCHOOL_NAME ?? 'Trường Thực Hành Sư Phạm') ?></h1>
            <p>Tra cứu thông tin học phí và thanh toán</p>
        </div>
        
        <div class="content">
            <?php if (!$student): ?>
                <!-- Form tra cứu - chưa đăng nhập -->
                <form method="POST" action="" class="lookup-form">
                    <?php if ($error): ?>
                        <div class="error-message"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="student_code">Mã học sinh</label>
                        <input type="text" id="student_code" name="student_code" placeholder="Nhập mã học sinh" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Ngày sinh</label>
                        <input type="text" id="dob" name="dob" placeholder="DD/MM/YYYY (ví dụ: 20/03/2019)" required>
                    </div>
                    
                    <button type="submit" class="btn">Tra cứu</button>
                </form>
                
            <?php else: ?>
                <!-- Thông tin học sinh -->
                <div class="student-info">
                    <h2>
                        <span>👤</span>
                        Thông tin học sinh
                    </h2>
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">Họ và tên</div>
                            <div class="info-value"><?= htmlspecialchars($student['full_name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Mã học sinh</div>
                            <div class="info-value"><?= htmlspecialchars($student['student_code']) ?></div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">Lớp</div>
                            <div class="info-value"><?= htmlspecialchars($student['class']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Ngày sinh</div>
                            <div class="info-value"><?= htmlspecialchars(date('d/m/Y', strtotime($student['dob']))) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs chuyển đổi -->
                <div class="tabs">
                    <a href="index.php?controller=portal&action=index" class="tab active">Học phí</a>
                    <a href="index.php?controller=portal&action=scores" class="tab">Bảng điểm</a>
                </div>
                
                <!-- Danh sách hóa đơn -->
                <h3 style="color: #1a365d; margin-bottom: 16px;">Danh sách phiếu báo thu</h3>
                
                <?php if (empty($invoices)): ?>
                    <div class="no-invoices">
                        <p>Chưa có phiếu báo thu nào.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                    <table class="invoices-table">
                        <thead>
                            <tr>
                                <th>Tháng/Năm</th>
                                <th>Tổng tiền</th>
                                <th>Đã TT</th>
                                <th>Còn nợ</th>
                                <th>Trạng thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><?= $inv['month'] ?>/<?= $inv['year'] ?></td>
                                    <td class="amount"><?= number_format($inv['total_amount'], 0, ',', '.') ?></td>
                                    <td class="amount"><?= number_format($inv['paid_amount'], 0, ',', '.') ?></td>
                                    <td class="amount" style="color: <?= $inv['remaining_amount'] > 0 ? '#c53030' : '#22543d' ?>">
                                        <?= number_format($inv['remaining_amount'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <?php if ($inv['status'] === 'paid'): ?>
                                            <span class="status-badge status-paid">Đã TT</span>
                                        <?php elseif ($inv['status'] === 'partial'): ?>
                                            <span class="status-badge status-partial">T. một phần</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Chưa TT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?controller=portal&action=invoice&id=<?= $inv['id'] ?>" class="btn-view">Xem</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center;">
                    <a href="index.php?controller=portal&action=logout" class="logout-link">🔄 Tra cứu học sinh khác</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
