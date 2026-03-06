<?php
/**
 * Script thêm dữ liệu điểm mẫu vào database
 * Chạy: php create_sample_scores.php
 */

require_once __DIR__ . '/public/index.php';

use App\Core\Database;

// Lấy danh sách học sinh
$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT id, student_code, full_name, class FROM students LIMIT 10");
$students = $stmt->fetchAll();

if (empty($students)) {
    echo "Không có học sinh nào trong database!\n";
    exit;
}

echo "Tìm thấy " . count($students) . " học sinh\n";

// Các môn học
$subjects = ['TOAN', 'TV', 'TNXH', 'KH'];

// Học kỳ và năm học
$semesters = [
    ['semester' => '1', 'school_year' => '2024-2025'],
    ['semester' => '2', 'school_year' => '2024-2025'],
    ['semester' => '1', 'school_year' => '2025-2026'],
];

// Loại điểm
$scoreTypes = ['mieng', '15p', '45p', 'hk'];

$insertedCount = 0;

foreach ($students as $student) {
    echo "Đang thêm điểm cho học sinh: {$student['full_name']} ({$student['student_code']})\n";
    
    foreach ($semesters as $sem) {
        foreach ($subjects as $subject) {
            // Mỗi môn có 1-3 điểm miệng
            $miengCount = rand(1, 3);
            for ($i = 0; $i < $miengCount; $i++) {
                $score = rand(60, 100) / 10; // Điểm từ 6.0 đến 10.0
                $stmt = $pdo->prepare("INSERT INTO scores (student_id, subject, semester, school_year, score_type, score_value) VALUES (?, ?, ?, ?, 'mieng', ?)");
                $stmt->execute([$student['id'], $subject, $sem['semester'], $sem['school_year'], $score]);
                $insertedCount++;
            }
            
            // Mỗi môn có 2-4 điểm 15 phút
            $count15p = rand(2, 4);
            for ($i = 0; $i < $count15p; $i++) {
                $score = rand(50, 100) / 10;
                $stmt = $pdo->prepare("INSERT INTO scores (student_id, subject, semester, school_year, score_type, score_value) VALUES (?, ?, ?, ?, '15p', ?)");
                $stmt->execute([$student['id'], $subject, $sem['semester'], $sem['school_year'], $score]);
                $insertedCount++;
            }
            
            // Mỗi môn có 2-3 điểm 45 phút
            $count45p = rand(2, 3);
            for ($i = 0; $i < $count45p; $i++) {
                $score = rand(50, 100) / 10;
                $stmt = $pdo->prepare("INSERT INTO scores (student_id, subject, semester, school_year, score_type, score_value) VALUES (?, ?, ?, ?, '45p', ?)");
                $stmt->execute([$student['id'], $subject, $sem['semester'], $sem['school_year'], $score]);
                $insertedCount++;
            }
            
            // Mỗi môn có 1 điểm học kỳ
            $score = rand(55, 100) / 10;
            $stmt = $pdo->prepare("INSERT INTO scores (student_id, subject, semester, school_year, score_type, score_value) VALUES (?, ?, ?, ?, 'hk', ?)");
            $stmt->execute([$student['id'], $subject, $sem['semester'], $sem['school_year'], $score]);
            $insertedCount++;
        }
    }
}

echo "\nĐã thêm thành công $insertedCount bản ghi điểm!\n";
echo "Bây giờ bạn có thể truy cập portal để xem bảng điểm.\n";
