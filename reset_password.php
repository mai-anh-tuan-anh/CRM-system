<?php
/**
 * Reset password cho tài khoản admin
 * Chạy file này để reset password về mặc định
 */

require_once 'backend/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Hash mật khẩu mới
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $salesHash = password_hash('sales123', PASSWORD_DEFAULT);
    $managerHash = password_hash('manager123', PASSWORD_DEFAULT);
    
    // Cập nhật password
    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE username = :username");
    
    // Admin
    $stmt->execute([':password' => $adminHash, ':username' => 'admin']);
    echo "✅ Admin password reset: admin123<br>";
    
    // Sales
    $stmt->execute([':password' => $salesHash, ':username' => 'sales01']);
    echo "✅ Sales password reset: sales123<br>";
    
    // Manager
    $stmt->execute([':password' => $managerHash, ':username' => 'manager01']);
    echo "✅ Manager password reset: manager123<br>";
    
    echo "<br>🎉 Hoàn tất! Bạn có thể đăng nhập ngay.";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
