<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Score
{
    /**
     * Lấy điểm của học sinh theo học kỳ
     */
    public static function getByStudent(int $studentId, string $semester = '', string $schoolYear = ''): array
    {
        $pdo = Database::getConnection();
        
        $sql = "SELECT s.*, sub.name as subject_name, sub.code as subject_code
                FROM scores s
                LEFT JOIN subjects sub ON s.subject = sub.code
                WHERE s.student_id = :student_id";
        
        $params = ['student_id' => $studentId];
        
        if ($semester) {
            $sql .= " AND s.semester = :semester";
            $params['semester'] = $semester;
        }
        
        if ($schoolYear) {
            $sql .= " AND s.school_year = :school_year";
            $params['school_year'] = $schoolYear;
        }
        
        $sql .= " ORDER BY s.semester, s.school_year, s.subject, s.score_type";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy điểm trung bình theo môn học
     */
    public static function getAverageBySubject(int $studentId, string $semester = '', string $schoolYear = ''): array
    {
        $pdo = Database::getConnection();
        
        // Lấy tất cả điểm của học sinh
        $sql = "SELECT s.subject, sub.name as subject_name, s.score_type, s.score_value
                FROM scores s
                LEFT JOIN subjects sub ON s.subject = sub.code
                WHERE s.student_id = :student_id";
        
        $params = ['student_id' => $studentId];
        
        if ($semester) {
            $sql .= " AND s.semester = :semester";
            $params['semester'] = $semester;
        }
        
        if ($schoolYear) {
            $sql .= " AND s.school_year = :school_year";
            $params['school_year'] = $schoolYear;
        }
        
        $sql .= " ORDER BY s.subject, s.score_type";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $allScores = $stmt->fetchAll();
        
        // Tính điểm TB theo môn
        $subjectScores = [];
        foreach ($allScores as $score) {
            $subject = $score['subject'];
            if (!isset($subjectScores[$subject])) {
                $subjectScores[$subject] = [
                    'subject' => $subject,
                    'subject_name' => $score['subject_name'],
                    'mieng' => [],
                    '15p' => [],
                    '45p' => [],
                    'hk' => []
                ];
            }
            $subjectScores[$subject][$score['score_type']][] = (float)$score['score_value'];
        }
        
        $results = [];
        foreach ($subjectScores as $subject => $data) {
            $avgMieng = !empty($data['mieng']) ? array_sum($data['mieng']) / count($data['mieng']) : 0;
            $avg15p = !empty($data['15p']) ? array_sum($data['15p']) / count($data['15p']) : 0;
            $avg45p = !empty($data['45p']) ? array_sum($data['45p']) / count($data['45p']) : 0;
            $avgHk = !empty($data['hk']) ? array_sum($data['hk']) / count($data['hk']) : 0;
            
            // Công thức: (Điểm miệng × 1 + Điểm 15p × 2 + Điểm 45p × 3 + Điểm HK × 4) ÷ 10
            $averageScore = ($avgMieng * 1 + $avg15p * 2 + $avg45p * 3 + $avgHk * 4) / 10;
            
            $results[] = [
                'subject' => $subject,
                'subject_name' => $data['subject_name'],
                'average_score' => round($averageScore, 2),
                'score_count' => count($data['mieng']) + count($data['15p']) + count($data['45p']) + count($data['hk'])
            ];
        }
        
        // Tính điểm TB chung
        $totalScore = 0;
        $count = 0;
        foreach ($results as &$row) {
            if ($row['average_score'] > 0) {
                $totalScore += $row['average_score'];
                $count++;
            }
        }
        
        return [
            'subjects' => $results,
            'overall_average' => $count > 0 ? round((float)($totalScore / $count), 2) : null
        ];
    }
    
    /**
     * Lấy danh sách học kỳ và năm học của học sinh
     */
    public static function getSemesters(int $studentId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT DISTINCT semester, school_year FROM scores WHERE student_id = :student_id ORDER BY school_year DESC, semester DESC");
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Thêm điểm mới
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO scores (student_id, subject, semester, school_year, score_type, score_value) VALUES (:student_id, :subject, :semester, :school_year, :score_type, :score_value)");
        $stmt->execute([
            'student_id' => $data['student_id'],
            'subject' => $data['subject'],
            'semester' => $data['semester'],
            'school_year' => $data['school_year'],
            'score_type' => $data['score_type'],
            'score_value' => $data['score_value']
        ]);
        return (int)$pdo->lastInsertId();
    }
    
    /**
     * Xóa điểm
     */
    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM scores WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
