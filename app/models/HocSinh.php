<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class HocSinh
{
    public static function paginate(string $q = '', int $page = 1, int $limit = 20, array $filters = []): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        // Search query
        if ($q !== '') {
            $where[] = "(student_code LIKE :q1 OR full_name LIKE :q2 OR class LIKE :q3)";
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
            $params['q3'] = '%' . $q . '%';
        }

        // Filter by class
        if (!empty($filters['class'])) {
            $where[] = "class = :class";
            $params['class'] = $filters['class'];
        }

        // Filter by status
        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students $whereClause");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT * FROM students $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM students WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO students (student_code, full_name, grade, class, dob, address, parent_name, parent_phone, parent_email, status)
             VALUES (:student_code, :full_name, :grade, :class, :dob, :address, :parent_name, :parent_phone, :parent_email, :status)'
        );
        $stmt->execute([
            'student_code' => $data['student_code'],
            'full_name' => $data['full_name'],
            'grade' => $data['grade'] ?: null,
            'class' => $data['class'],
            'dob' => $data['dob'] ?: null,
            'address' => $data['address'] ?: null,
            'parent_name' => $data['parent_name'] ?: null,
            'parent_phone' => $data['parent_phone'] ?: null,
            'parent_email' => $data['parent_email'] ?: null,
            'status' => $data['status'] ?? 'active',
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE students
             SET student_code = :student_code,
                 full_name = :full_name,
                 grade = :grade,
                 class = :class,
                 dob = :dob,
                 address = :address,
                 parent_name = :parent_name,
                 parent_phone = :parent_phone,
                 parent_email = :parent_email,
                 status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'student_code' => $data['student_code'],
            'full_name' => $data['full_name'],
            'grade' => $data['grade'] ?: null,
            'class' => $data['class'],
            'dob' => $data['dob'] ?: null,
            'address' => $data['address'] ?: null,
            'parent_name' => $data['parent_name'] ?: null,
            'parent_phone' => $data['parent_phone'] ?: null,
            'parent_email' => $data['parent_email'] ?: null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function getAllForSelect(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, student_code, full_name, class FROM students WHERE status = 'active' ORDER BY class, full_name");
        return $stmt->fetchAll();
    }

    public static function getInvoices(int $studentId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT i.*, 
            (SELECT SUM(amount) FROM payments WHERE invoice_id = i.id) as paid_amount
            FROM invoices i 
            WHERE i.student_id = :student_id 
            ORDER BY i.year DESC, i.month DESC, i.created_at DESC");
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    public static function getClasses(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT DISTINCT class FROM students WHERE class != '' ORDER BY class");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getByClass(string $class): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM students WHERE class = :class AND status = 'active' ORDER BY full_name");
        $stmt->execute(['class' => $class]);
        return $stmt->fetchAll();
    }

    /**
     * Import students from CSV data
     * @param array $students Array of student data
     * @return array ['success' => int, 'errors' => array]
     */
    public static function import(array $students): array
    {
        $pdo = Database::getConnection();
        $success = 0;
        $errors = [];

        foreach ($students as $index => $data) {
            $rowNum = $index + 2; // +2 because index starts at 0 and row 1 is header

            // Validate required fields
            if (empty($data['student_code']) || empty($data['full_name']) || empty($data['class'])) {
                $errors[] = "Dòng $rowNum: Thiếu mã học sinh, họ tên hoặc lớp.";
                continue;
            }

            // Check if student_code already exists
            $stmt = $pdo->prepare('SELECT id FROM students WHERE student_code = :student_code');
            $stmt->execute(['student_code' => $data['student_code']]);
            if ($stmt->fetch()) {
                $errors[] = "Dòng $rowNum: Mã học sinh '{$data['student_code']}' đã tồn tại.";
                continue;
            }

            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO students (student_code, full_name, grade, class, dob, address, parent_name, parent_phone, parent_email, status)
                     VALUES (:student_code, :full_name, :grade, :class, :dob, :address, :parent_name, :parent_phone, :parent_email, :status)'
                );
                $stmt->execute([
                    'student_code' => $data['student_code'],
                    'full_name' => $data['full_name'],
                    'grade' => $data['grade'] ?? null,
                    'class' => $data['class'],
                    'dob' => $data['dob'] ?? null,
                    'address' => $data['address'] ?? null,
                    'parent_name' => $data['parent_name'] ?? null,
                    'parent_phone' => $data['parent_phone'] ?? null,
                    'parent_email' => $data['parent_email'] ?? null,
                    'status' => $data['status'] ?? 'active',
                ]);
                $success++;
            } catch (\Exception $e) {
                $errors[] = "Dòng $rowNum: Lỗi khi lưu - " . $e->getMessage();
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }
}

