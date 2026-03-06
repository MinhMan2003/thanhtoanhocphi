<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điểm - <?= htmlspecialchars($pageTitle ?? 'Portal') ?></title>
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
            padding: 30px 40px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
        }
        
        .tab {
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
        
        .tab:hover {
            background: #edf2f7;
        }
        
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        /* Student Info */
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
        
        /* Filter */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            padding: 16px;
            background: #f7fafc;
            border-radius: 8px;
            align-items: center;
        }
        
        .filter-bar label {
            font-weight: 600;
            color: #2d3748;
        }
        
        .filter-bar select {
            padding: 8px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .filter-bar select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .back-link {
            display: inline-block;
            margin-left: auto;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Score Table */
        .score-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        
        .score-table th {
            background: #1a365d;
            color: white;
            padding: 14px;
            text-align: center;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .score-table th:first-child {
            text-align: left;
        }
        
        .score-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            font-size: 14px;
        }
        
        .score-table td:first-child {
            text-align: left;
            font-weight: 500;
            color: #2d3748;
        }
        
        .score-table tr:hover {
            background: #f7fafc;
        }
        
        .score-table .score-value {
            font-family: 'Consolas', monospace;
            font-weight: 600;
        }
        
        .score-table .score-high {
            color: #22543d;
        }
        
        .score-table .score-medium {
            color: #744210;
        }
        
        .score-table .score-low {
            color: #c53030;
        }
        
        /* Xếp loại học lực */
        .rank-excellent {
            color: #22543d;
            font-weight: 600;
        }
        
        .rank-good {
            color: #2f855a;
            font-weight: 600;
        }
        
        .rank-average {
            color: #744210;
            font-weight: 600;
        }
        
        .rank-poor {
            color: #c53030;
            font-weight: 600;
        }
        
        .average-row {
            background: #edf2f7;
            font-weight: 600;
        }
        
        .average-row td {
            font-size: 15px;
        }
        
        /* Overall Average */
        .overall-average {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 24px 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }
        
        .average-item {
            text-align: center;
        }
        
        .average-item .label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        
        .average-item .value {
            font-size: 32px;
            font-weight: 700;
        }
        
        /* No scores message */
        .no-scores {
            text-align: center;
            padding: 40px;
            color: #718096;
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
        
        /* Score Guide */
        .score-guide {
            background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            border-left: 4px solid #3182ce;
        }
        
        .score-guide h3 {
            color: #2c5282;
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .guide-formula {
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-family: 'Consolas', monospace;
            color: #2d3748;
        }
        
        .guide-note {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 13px;
            color: #4a5568;
        }
        
        .guide-note span {
            background: white;
            padding: 6px 12px;
            border-radius: 6px;
        }
        
        .guide-note strong {
            color: #2b6cb0;
        }
        
        .guide-rank {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #90cdf4;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        
        .guide-rank p {
            width: 100%;
            margin: 0 0 8px 0;
            color: #2c5282;
            font-size: 13px;
        }
        
        .rank-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 4px;
        }
        
        .rank-badge.excellent {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .rank-badge.good {
            background: #bee3f8;
            color: #2c5282;
        }
        
        .rank-badge.average {
            background: #fefcbf;
            color: #744210;
        }
        
        .rank-badge.poor {
            background: #fed7d7;
            color: #c53030;
        }
        
        /* Table wrapper for scroll */
        .table-wrapper {
            margin: 0;
            padding: 0;
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 5px;
            }
            
            .container {
                border-radius: 10px;
            }
            
            .content {
                padding: 12px;
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
            
            .tabs {
                flex-direction: row;
            }
            
            .tab {
                padding: 10px 8px;
                font-size: 13px;
            }
            
            .student-info {
                padding: 12px;
            }
            
            .student-info h2 {
                font-size: 14px;
            }
            
            .student-info .info-item {
                min-width: 100%;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
                padding: 10px;
            }
            
            .filter-bar label {
                font-size: 12px;
            }
            
            .filter-bar select {
                width: 100%;
                font-size: 13px;
                padding: 8px;
            }
            
            .back-link {
                margin-left: 0;
                text-align: center;
                display: block;
                margin-top: 8px;
                font-size: 13px;
            }
            
            .overall-average {
                flex-direction: column;
                gap: 10px;
                padding: 12px;
                margin: 15px 0;
            }
            
            .average-item .label {
                font-size: 12px;
            }
            
            .average-item .value {
                font-size: 20px;
            }
            
            /* Table responsive */
            .table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -12px;
                padding: 0 12px;
            }
            
            .score-table {
                font-size: 10px;
                min-width: 550px;
            }
            
            .score-table th,
            .score-table td {
                padding: 6px 3px;
                white-space: nowrap;
            }
            
            .score-table th:first-child,
            .score-table td:first-child {
                position: sticky;
                left: 0;
                background: #f7fafc;
                z-index: 1;
                border-right: 1px solid #e2e8f0;
            }
            
            .score-table thead th:first-child {
                background: #edf2f7;
            }
            
            .score-table tbody tr:nth-child(even) td:first-child {
                background: #f7fafc;
            }
            
            /* Guide responsive */
            .score-guide {
                padding: 12px;
            }
            
            .score-guide h3 {
                font-size: 12px;
            }
            
            .guide-formula {
                font-size: 10px;
                padding: 8px;
            }
            
            .guide-note {
                flex-direction: column;
                gap: 6px;
            }
            
            .guide-note span {
                font-size: 10px;
                padding: 4px 8px;
            }
            
            .guide-rank p {
                font-size: 11px;
            }
            
            .rank-badge {
                font-size: 9px;
                padding: 3px 6px;
            }
            
            .no-scores {
                padding: 15px;
                font-size: 13px;
            }
            
            .logout-link {
                font-size: 13px;
                padding: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 14px;
            }
            
            .tabs {
                gap: 5px;
            }
            
            .tab {
                padding: 8px 5px;
                font-size: 12px;
            }
            
            .score-table {
                min-width: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars(\App\Core\Config::SCHOOL_NAME ?? 'Trường Thực Hành Sư Phạm') ?></h1>
            <p>Tra cứu thông tin học tập</p>
        </div>
        
        <div class="content">
            <!-- Tabs -->
            <div class="tabs">
                <a href="index.php?controller=portal&action=index" class="tab">Học phí</a>
                <a href="index.php?controller=portal&action=scores" class="tab active">Bảng điểm</a>
            </div>
            
            <!-- Thông tin học sinh -->
            <div class="student-info">
                <h2>Thông tin học sinh</h2>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Họ và tên</div>
                        <div class="info-value"><?= htmlspecialchars($_SESSION['portal_student_name'] ?? '') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Mã học sinh</div>
                        <div class="info-value"><?= htmlspecialchars($_SESSION['portal_student_code'] ?? '') ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($semesters)): ?>
                <div class="no-scores">
                    <p>Chưa có dữ liệu điểm.</p>
                </div>
            <?php else: ?>
                <!-- Hướng dẫn cách tính điểm -->
                <div class="score-guide">
                    <h3>Hướng dẫn tính điểm & xếp loại (Thang 10)</h3>
                    <div class="guide-formula">
                        <p><strong>Điểm TB môn =</strong> (Điểm miệng × 1 + Điểm 15p × 2 + Điểm 45p × 3 + Điểm HK × 4) ÷ 10</p>
                    </div>
                    <div class="guide-note">
                        <span><strong>Miệng:</strong> 10%</span>
                        <span><strong>15 phút:</strong> 20%</span>
                        <span><strong>45 phút:</strong> 30%</span>
                        <span><strong>Học kỳ:</strong> 40%</span>
                    </div>
                    <div class="guide-rank">
                        <p><strong>Xếp loại học lực:</strong></p>
                        <span class="rank-badge excellent">Xuất sắc: ≥ 9.0</span>
                        <span class="rank-badge excellent">Giỏi: 8.0 - 8.9</span>
                        <span class="rank-badge good">Khá: 7.0 - 7.9</span>
                        <span class="rank-badge average">Trung bình: 5.0 - 6.9</span>
                        <span class="rank-badge poor">Yếu: 3.5 - 4.9</span>
                        <span class="rank-badge poor">Kém: &lt; 3.5</span>
                    </div>
                </div>
                
                <!-- Filter -->
                <form method="GET" class="filter-bar">
                    <input type="hidden" name="controller" value="portal">
                    <input type="hidden" name="action" value="scores">
                    
                    <label for="semester">Học kỳ:</label>
                    <select name="semester" id="semester" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <?php 
                        $uniqueSem = [];
                        foreach ($semesters as $s): 
                            if (!in_array($s['semester'], $uniqueSem)) {
                                $uniqueSem[] = $s['semester'];
                        ?>
                            <option value="<?= htmlspecialchars($s['semester']) ?>" <?= $semester === $s['semester'] ? 'selected' : '' ?>>
                                Học kỳ <?= htmlspecialchars($s['semester']) ?>
                            </option>
                        <?php 
                            }
                        endforeach; 
                        ?>
                    </select>
                    
                    <label for="school_year">Năm học:</label>
                    <select name="school_year" id="school_year" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <?php 
                        $uniqueYear = [];
                        foreach ($semesters as $s): 
                            if (!empty($s['school_year']) && !in_array($s['school_year'], $uniqueYear)) {
                                $uniqueYear[] = $s['school_year'];
                        ?>
                            <option value="<?= htmlspecialchars($s['school_year']) ?>" <?= $schoolYear === $s['school_year'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['school_year']) ?>
                            </option>
                        <?php 
                            }
                        endforeach; 
                        ?>
                    </select>
                    
                    <a href="index.php?controller=portal&action=index" class="back-link">← Quay lại</a>
                </form>
                
                <?php if (!empty($scoreData['subjects'])): ?>
                    <?php 
                    // Hàm xếp loại học lực
                    function getRank($diemTB) {
                        if ($diemTB === null) return '-';
                        if ($diemTB >= 9.0) return 'Xuất sắc';
                        if ($diemTB >= 8.0) return 'Giỏi';
                        if ($diemTB >= 7.0) return 'Khá';
                        if ($diemTB >= 5.0) return 'Trung bình';
                        if ($diemTB >= 3.5) return 'Yếu';
                        return 'Kém';
                    }
                    
                    // Hàm màu xếp loại
                    function getRankClass($diemTB) {
                        if ($diemTB === null) return '';
                        if ($diemTB >= 8.0) return 'rank-excellent';
                        if ($diemTB >= 7.0) return 'rank-good';
                        if ($diemTB >= 5.0) return 'rank-average';
                        return 'rank-poor';
                    }
                    ?>
                    <div class="table-wrapper">
                    <!-- Bảng điểm -->
                    <table class="score-table">
                        <thead>
                            <tr>
                                <th>Môn học</th>
                                <th>Miệng</th>
                                <th>15p</th>
                                <th>45p</th>
                                <th>HK</th>
                                <th>TB</th>
                                <th>Xếp loại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $scoreByType = [];
                            foreach ($scoreDetails as $detail) {
                                $key = $detail['subject'];
                                if (!isset($scoreByType[$key])) {
                                    $scoreByType[$key] = [
                                        'mieng' => [],
                                        '15p' => [],
                                        '45p' => [],
                                        'hk' => []
                                    ];
                                }
                                $scoreByType[$key][$detail['score_type']][] = $detail['score_value'];
                            }
                            
                            foreach ($scoreData['subjects'] as $subject): 
                                $subjectCode = $subject['subject'];
                                $scores = $scoreByType[$subjectCode] ?? ['mieng' => [], '15p' => [], '45p' => [], 'hk' => []];
                                
                                $avgMieng = !empty($scores['mieng']) ? array_sum($scores['mieng']) / count($scores['mieng']) : null;
                                $avg15p = !empty($scores['15p']) ? array_sum($scores['15p']) / count($scores['15p']) : null;
                                $avg45p = !empty($scores['45p']) ? array_sum($scores['45p']) / count($scores['45p']) : null;
                                $avgHk = !empty($scores['hk']) ? array_sum($scores['hk']) / count($scores['hk']) : null;
                                $average = $subject['average_score'];
                                
                                $avgClass = '';
                                if ($average !== null) {
                                    if ($average >= 8) $avgClass = 'score-high';
                                    elseif ($average >= 5) $avgClass = 'score-medium';
                                    else $avgClass = 'score-low';
                                }
                                
                                $xepLoai = getRank($average);
                                $rankClass = getRankClass($average);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($subject['subject_name'] ?? $subject['subject']) ?></td>
                                    <td class="score-value">
                                        <?= $avgMieng !== null ? number_format($avgMieng, 1) : '-' ?>
                                    </td>
                                    <td class="score-value">
                                        <?= $avg15p !== null ? number_format($avg15p, 1) : '-' ?>
                                    </td>
                                    <td class="score-value">
                                        <?= $avg45p !== null ? number_format($avg45p, 1) : '-' ?>
                                    </td>
                                    <td class="score-value">
                                        <?= $avgHk !== null ? number_format($avgHk, 1) : '-' ?>
                                    </td>
                                    <td class="score-value <?= $avgClass ?>">
                                        <?= $average !== null ? number_format($average, 1) : '-' ?>
                                    </td>
                                    <td class="score-value <?= $rankClass ?>"><?= $xepLoai ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if ($scoreData['overall_average'] !== null): ?>
                                <tr class="average-row">
                                    <td><strong>Điểm TB chung</strong></td>
                                    <td colspan="4"></td>
                                    <td class="score-value">
                                        <strong><?= number_format($scoreData['overall_average'], 2) ?></strong>
                                    </td>
                                    <td class="score-value <?= getRankClass($scoreData['overall_average']) ?>">
                                        <strong><?= getRank($scoreData['overall_average']) ?></strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                    
                    <!-- Điểm TB chung -->
                    <div class="overall-average">
                        <div class="average-item">
                            <div class="label">Điểm TB học kỳ</div>
                            <div class="value"><?= $scoreData['overall_average'] !== null ? number_format($scoreData['overall_average'], 2) : '-' ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-scores">
                        <p>Không có điểm cho học kỳ/năm học đã chọn.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <a href="index.php?controller=portal&action=logout" class="logout-link">🔄 Tra cứu học sinh khác</a>
            </div>
        </div>
    </div>
</body>
</html>
