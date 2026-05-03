<?php
/**
 * Test authentication
 */
require_once 'backend/config/database.php';
require_once 'backend/models/User.php';

$userModel = new User();

// Test 1: Get user
$user = $userModel->getByUsername('admin');
echo "=== Test 1: Get user 'admin' ===\n";
echo "Found: " . ($user ? 'YES' : 'NO') . "\n";

if ($user) {
    echo "Username: '" . $user['username'] . "' (len=" . strlen($user['username']) . ")\n";
    echo "Has password: " . (isset($user['password']) ? 'YES' : 'NO') . "\n";
    echo "Password hash FULL: " . $user['password'] . "\n";
    echo "Hash length: " . strlen($user['password']) . "\n";
    
    // Test 2: Verify password
    $verify = password_verify('admin123', $user['password']);
    echo "\n=== Test 2: Verify password 'admin123' ===\n";
    echo "Result: " . ($verify ? 'TRUE (CORRECT)' : 'FALSE (WRONG)') . "\n";
    
    // Show expected hash
    $expectedHash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "\nExpected hash for 'admin123': " . $expectedHash . "\n";
}

// Test 3: Database connection
echo "\n=== Test 3: Database connection ===\n";
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "Total users in DB: " . $result['total'] . "\n";
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===";
