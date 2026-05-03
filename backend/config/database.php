<?php
/**
 * Database Configuration
 * XAMPP Environment - Port 8082
 */

// Error reporting - tắt hiển thị lỗi để tránh làm hỏng JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Database credentials for XAMPP
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'customer_management');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has no password for root

// Application settings
define('APP_NAME', 'Customer Management CRM');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost:8082/customer_management');
define('BACKEND_URL', BASE_URL . '/backend');
define('FRONTEND_URL', BASE_URL . '/frontend');
define('UPLOAD_PATH', __DIR__ . '/../../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Database Connection Class
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

/**
 * Get database connection
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Helper function to send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Helper function to send error response
 */
function jsonError($message, $statusCode = 400, $errors = null) {
    $response = ['success' => false, 'message' => $message];
    if ($errors) {
        $response['errors'] = $errors;
    }
    jsonResponse($response, $statusCode);
}

/**
 * Helper function to send success response
 */
function jsonSuccess($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}

/**
 * Generate unique code
 */
function generateCode($prefix, $table, $column) {
    $db = getDB();
    $year = date('Y');
    
    // Get the last code for this year
    $stmt = $db->prepare("SELECT {$column} FROM {$table} WHERE {$column} LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '-' . $year . '-%']);
    $last = $stmt->fetch();
    
    if ($last) {
        $parts = explode('-', $last[$column]);
        $lastNumber = intval(end($parts));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
}

/**
 * Sanitize input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields
 */
function validateRequired($data, $required) {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Log activity
 */
function logActivity($type, $description, $relatedType, $relatedId, $userId, $metadata = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO activities (activity_type, description, related_to_type, related_to_id, performed_by, metadata)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$type, $description, $relatedType, $relatedId, $userId, $metadata ? json_encode($metadata) : null]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Pagination helper
 */
function paginate($query, $params = [], $page = 1, $perPage = 20) {
    $db = getDB();
    
    // Get total count
    $countQuery = preg_replace('/SELECT.*?FROM/i', 'SELECT COUNT(*) as total FROM', $query, 1);
    $countQuery = preg_replace('/ORDER BY.*$/i', '', $countQuery);
    $countQuery = preg_replace('/LIMIT.*$/i', '', $countQuery);
    
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Calculate offset
    $page = max(1, intval($page));
    $perPage = max(1, min(100, intval($perPage)));
    $offset = ($page - 1) * $perPage;
    
    // Get data
    $stmt = $db->prepare($query . " LIMIT {$offset}, {$perPage}");
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    return [
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ]
    ];
}
