<?php
/**
 * Deals Import API - Excel Format
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/excel.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Deal.php';

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
$requiredColumns = ['title', 'customer_id'];
$headers = $parseResult['headers'];
$missingColumns = array_diff($requiredColumns, $headers);

if (!empty($missingColumns)) {
    jsonError('Thiếu các cột bắt buộc: ' . implode(', ', $missingColumns));
}

// Process import
$imported = 0;
$failed = 0;
$errors = [];

$dealModel = new Deal();

foreach ($parseResult['data'] as $index => $row) {
    // Skip empty rows
    if (empty($row['title']) || empty($row['customer_id'])) {
        continue;
    }
    
    // Clean and validate data
    $data = [];
    $allowedColumns = ['title', 'customer_id', 'value', 'currency', 'stage', 'probability', 'expected_close_date', 'source'];
    
    foreach ($allowedColumns as $column) {
        if (isset($row[$column])) {
            $data[$column] = sanitize($row[$column]);
        }
    }
    
    // Set required fields
    $data['created_by'] = $user['id'];
    $data['assigned_to'] = $_POST['assigned_to'] ?? $user['id'];
    $data['value'] = $data['value'] ?? 0;
    $data['currency'] = $data['currency'] ?? 'VND';
    $data['stage'] = $data['stage'] ?? 'prospect';
    $data['probability'] = $data['probability'] ?? 20;
    $data['source'] = $data['source'] ?? 'Other';
    
    // Validate customer exists and is active
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM customers WHERE id = ? AND status = 'active'");
        $stmt->execute([$data['customer_id']]);
        if (!$stmt->fetch()) {
            $failed++;
            $errors[] = "Row " . ($index + 2) . ": Customer ID " . $data['customer_id'] . " không tồn tại hoặc không hoạt động";
            continue;
        }
    } catch (Exception $e) {
        $failed++;
        $errors[] = "Row " . ($index + 2) . ": Error validating customer: " . $e->getMessage();
        continue;
    }
    
    // Generate deal code
    $data['deal_code'] = generateDealCode();
    
    try {
        $id = $dealModel->create($data);
        if ($id) {
            $imported++;
            // Log activity
            logActivity('import', 'Imported deal via Excel', 'deal', $id, $user['id'], $data);
        } else {
            $failed++;
            $errors[] = "Row " . ($index + 2) . ": Failed to create deal";
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
], "Import hoàn tất. {$imported} deals được nhập, {$failed} thất bại.");

function generateDealCode() {
    $db = getDB();
    $year = date('Y');
    $prefix = 'DEAL';
    
    // Get the last code for this year
    $stmt = $db->prepare("SELECT deal_code FROM deals WHERE deal_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '-' . $year . '-%']);
    $last = $stmt->fetch();
    
    if ($last) {
        $parts = explode('-', $last['deal_code']);
        $lastNumber = intval(end($parts));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
}
?>
