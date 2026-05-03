<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Start session if not started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user info
 */
function getCurrentUser() {
    startSession();
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'avatar' => $_SESSION['avatar'] ?? null
        ];
    }
    return null;
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        jsonError('Unauthorized. Please login.', 401);
    }
}

/**
 * Require specific role
 */
function requireRole($roles) {
    requireAuth();
    $user = getCurrentUser();
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($user['role'], $roles)) {
        jsonError('Forbidden. Insufficient permissions.', 403);
    }
}

/**
 * Check if user owns resource or is admin/manager
 */
function canAccessResource($assignedTo, $createdBy = null) {
    requireAuth();
    $user = getCurrentUser();
    
    // Admin and manager can access all
    if (in_array($user['role'], ['admin', 'manager'])) {
        return true;
    }
    
    // Check if assigned to current user
    if ($assignedTo == $user['id']) {
        return true;
    }
    
    // Check if created by current user
    if ($createdBy == $user['id']) {
        return true;
    }
    
    return false;
}

/**
 * Upload file helper
 */
function uploadFile($file, $directory = 'files') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Validate file size (max 10MB)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds 10MB limit'];
    }
    
    // Create upload directory if not exists
    $uploadDir = UPLOAD_PATH . $directory . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'original_name' => $file['name'],
            'path' => $directory . '/' . $filename,
            'size' => $file['size'],
            'mime_type' => $file['type']
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Delete file helper
 */
function deleteFile($filepath) {
    $fullPath = UPLOAD_PATH . $filepath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Send email helper
 */
function sendEmail($to, $subject, $body, $from = null, $cc = null, $bcc = null) {
    // Get settings
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'email_%'");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $from = $from ?: $settings['email_from_address'] ?? 'noreply@crm.local';
    $fromName = $settings['email_from_name'] ?? 'CRM System';
    
    // Headers
    $headers = "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    if ($cc) {
        $headers .= "Cc: {$cc}\r\n";
    }
    if ($bcc) {
        $headers .= "Bcc: {$bcc}\r\n";
    }
    
    // Send email
    $result = mail($to, $subject, $body, $headers);
    
    return [
        'success' => $result,
        'message' => $result ? 'Email sent successfully' : 'Failed to send email'
    ];
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'VND') {
    if ($currency === 'VND') {
        return number_format($amount, 0, ',', '.') . ' ₫';
    }
    return number_format($amount, 2) . ' ' . $currency;
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Time ago helper
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime);
    }
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get enum values from table
 */
function getEnumValues($table, $column) {
    $db = getDB();
    $stmt = $db->query("SHOW COLUMNS FROM {$table} WHERE Field = '{$column}'");
    $row = $stmt->fetch();
    
    if ($row && preg_match('/^enum\((.*)\)$/', $row['Type'], $matches)) {
        $values = explode(',', $matches[1]);
        return array_map(function($v) {
            return trim($v, "'\"");
        }, $values);
    }
    
    return [];
}

/**
 * Create notification
 */
function createNotification($userId, $title, $message, $type = 'info', $relatedType = null, $relatedId = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_to_type, related_to_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $message, $type, $relatedType, $relatedId]);
        return $db->lastInsertId();
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename, $headers = null) {
    if (empty($data)) {
        return ['success' => false, 'message' => 'No data to export'];
    }
    
    // Create temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'csv');
    $fp = fopen($tempFile, 'w');
    
    // Add BOM for UTF-8
    fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // Write headers
    if ($headers) {
        fputcsv($fp, $headers);
    } else {
        fputcsv($fp, array_keys($data[0]));
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($fp, $row);
    }
    
    fclose($fp);
    
    // Read file content
    $content = file_get_contents($tempFile);
    unlink($tempFile);
    
    return [
        'success' => true,
        'content' => $content,
        'filename' => $filename
    ];
}

/**
 * Import data from CSV
 */
function importFromCSV($file, $requiredColumns = []) {
    if (!file_exists($file)) {
        return ['success' => false, 'message' => 'File not found'];
    }
    
    $handle = fopen($file, 'r');
    if (!$handle) {
        return ['success' => false, 'message' => 'Cannot open file'];
    }
    
    // Read headers
    $headers = fgetcsv($handle);
    
    // Check required columns
    $missing = array_diff($requiredColumns, $headers);
    if (!empty($missing)) {
        fclose($handle);
        return ['success' => false, 'message' => 'Missing required columns: ' . implode(', ', $missing)];
    }
    
    // Read data
    $data = [];
    while (($row = fgetcsv($handle)) !== false) {
        $data[] = array_combine($headers, $row);
    }
    
    fclose($handle);
    
    return ['success' => true, 'data' => $data, 'count' => count($data)];
}
