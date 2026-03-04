<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByApiKey(string $apiKey): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE api_key = :api_key LIMIT 1');
        $stmt->execute(['api_key' => $apiKey]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function updateApiKey(int $userId, string $apiKey): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET api_key = :api_key WHERE id = :id');
        return $stmt->execute(['api_key' => $apiKey, 'id' => $userId]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function updateLastLogin(int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public static function getAll(int $page = 1, int $limit = 20): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;
        $stmt = $pdo->prepare('SELECT id, username, full_name, role, last_login, created_at FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $pdo = Database::getConnection();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');

        return $stmt->execute(['hash' => $hash, 'id' => $userId]);
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) 
            VALUES (:username, :hash, :full_name, :role)");
        $stmt->execute([
            'username' => $data['username'],
            'hash' => $hash,
            'full_name' => $data['full_name'] ?? '',
            'role' => $data['role'] ?? 'staff',
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        
        if (!empty($data['password'])) {
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET username = :username, password_hash = :hash, full_name = :full_name, role = :role WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'username' => $data['username'],
                'hash' => $hash,
                'full_name' => $data['full_name'] ?? '',
                'role' => $data['role'] ?? 'staff',
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = :username, full_name = :full_name, role = :role WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'username' => $data['username'],
                'full_name' => $data['full_name'] ?? '',
                'role' => $data['role'] ?? 'staff',
            ]);
        }

        return true;
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function getTotal(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        return (int)$stmt->fetchColumn();
    }
}
