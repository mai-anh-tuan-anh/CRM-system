<?php
/**
 * Fix password using PHP
 */
require_once 'backend/config/database.php';

try {
    $db = getDB();
    
    // New password hash for 'admin123'
    $newHash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "New hash generated: " . $newHash . "\n\n";
    
    // Update all users
    $users = [
        ['admin', 'admin123'],
        ['sales01', 'sales123'],
        ['manager01', 'manager123']
    ];
    
    foreach ($users as $user) {
        $username = $user[0];
        $password = $user[1];
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
        $result = $stmt->execute([$hash, $username]);
        
        echo "Updated {$username}: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        echo "  - Affected rows: " . $stmt->rowCount() . "\n";
        echo "  - New hash: " . substr($hash, 0, 50) . "...\n\n";
    }
    
    // Verify
    echo "=== Verification ===\n";
    $stmt = $db->prepare("SELECT username, password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        $verify = password_verify('admin123', $user['password']);
        echo "Verify admin/admin123: " . ($verify ? 'SUCCESS ✓' : 'FAILED ✗') . "\n";
    }
    
    echo "\nDone! You can now login with:\n";
    echo "  admin / admin123\n";
    echo "  sales01 / sales123\n";
    echo "  manager01 / manager123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
