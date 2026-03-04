<?php
require_once 'C:\xampp\htdocs\Thanhtoanhocphi\app\core\Config.php';
require_once 'C:\xampp\htdocs\Thanhtoanhocphi\app\core\Database.php';

use App\Core\Database;

try {
    $pdo = Database::getConnection();
    
    // Generate new hash for 'admin123'
    $newHash = password_hash('admin123', PASSWORD_BCRYPT);
    echo "New hash: $newHash\n";
    
    // Update password in database
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $result = $stmt->execute([$newHash]);
    
    if ($result) {
        echo "Password updated successfully!\n";
        
        // Verify
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch();
        
        $verify = password_verify('admin123', $user['password_hash']);
        echo "Verification: " . ($verify ? "SUCCESS" : "FAILED") . "\n";
    } else {
        echo "Failed to update password\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
