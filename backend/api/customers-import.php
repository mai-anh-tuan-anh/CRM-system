<?php
/**
 * Customers Import API - Excel Format
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/excel.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Customer.php';

$user = authenticate();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonError('Method not allowed', 405);
}

if (empty($_FILES['file'])) {
    jsonError('Chưa chọn file');
}

// Upload and parse file
$uploadResult = uploadFile($_FILES['file'], 'imports');

if (!$uploadResult['success']) {
    jsonError($uploadResult['message']);
}

$csvFile = UPLOAD_PATH . $uploadResult['path'];

// Parse Excel/CSV
$parseResult = parseExcelFile($csvFile);

// Clean up uploaded file
unlink($csvFile);

if (!$parseResult['headers'] || empty($parseResult['data'])) {
    jsonError('File không có dữ liệu hoặc định dạng không đúng');
}

// Validate required columns
$requiredColumns = ['full_name'];
$headers = $parseResult['headers'];
$missingColumns = array_diff($requiredColumns, $headers);

if (!empty($missingColumns)) {
    jsonError('Thiếu các cột bắt buộc: ' . implode(', ', $missingColumns));
}

// Process import
$imported = 0;
$failed = 0;
$errors = [];

$customerModel = new Customer();

foreach ($parseResult['data'] as $index => $row) {
    // Skip empty rows
    if (empty($row['full_name'])) {
        continue;
    }
    
    // Clean and validate data
    $data = [];
    $allowedColumns = ['full_name', 'email', 'phone', 'company_name', 'address', 'city', 'country', 'industry', 'source'];
    
    foreach ($allowedColumns as $column) {
        if (isset($row[$column])) {
            $data[$column] = sanitize($row[$column]);
        }
    }
    
    // Set required fields
    $data['created_by'] = $user['id'];
    $data['assigned_to'] = $_POST['assigned_to'] ?? $user['id'];
    $data['status'] = $_POST['default_status'] ?? 'active';
    $data['source'] = $data['source'] ?? 'Other';
    
    // Generate customer code
    $data['customer_code'] = generateCustomerCode();
    
    try {
        $id = $customerModel->create($data);
        if ($id) {
            $imported++;
            // Log activity
            logActivity('import', 'Imported customer via Excel', 'customer', $id, $user['id'], $data);
        } else {
            $failed++;
            $errors[] = "Row " . ($index + 2) . ": Failed to create customer";
        }
    } catch (Exception $e) {
        $failed++;
        $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
    }
}

jsonSuccess([
    'total_rows' => count($parseResult['data']),
    'imported' => $imported,
    'failed' => $failed,
    'errors' => $errors
], "Import hoàn tất. {$imported} customers được nhập, {$failed} thất bại.");

function generateCustomerCode() {
    $db = getDB();
    $year = date('Y');
    $prefix = 'CUST';
    
    // Get the last code for this year
    $stmt = $db->prepare("SELECT customer_code FROM customers WHERE customer_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '-' . $year . '-%']);
    $last = $stmt->fetch();
    
    if ($last) {
        $parts = explode('-', $last['customer_code']);
        $lastNumber = intval(end($parts));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
}
?>
