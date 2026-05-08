<?php
/**
 * Tasks Import API - Excel Format
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/excel.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Task.php';

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
$requiredColumns = ['title'];
$headers = $parseResult['headers'];
$missingColumns = array_diff($requiredColumns, $headers);

if (!empty($missingColumns)) {
    jsonError('Thiếu các cột bắt buộc: ' . implode(', ', $missingColumns));
}

// Process import
$imported = 0;
$failed = 0;
$errors = [];

$taskModel = new Task();

foreach ($parseResult['data'] as $index => $row) {
    // Skip empty rows
    if (empty($row['title'])) {
        continue;
    }
    
    // Clean and validate data
    $data = [];
    $allowedColumns = ['title', 'type', 'status', 'priority', 'due_date', 'related_to_type', 'related_to_id'];
    
    foreach ($allowedColumns as $column) {
        if (isset($row[$column])) {
            $data[$column] = sanitize($row[$column]);
        }
    }
    
    // Set required fields
    $data['created_by'] = $user['id'];
    $data['assigned_to'] = $_POST['assigned_to'] ?? $user['id'];
    $data['status'] = $data['status'] ?? 'pending';
    $data['priority'] = $data['priority'] ?? 'medium';
    $data['type'] = $data['type'] ?? 'other';
    
    // Validate related entity if provided
    if (!empty($data['related_to_type']) && !empty($data['related_to_id'])) {
        try {
            $db = getDB();
            $table = $data['related_to_type'] . 's'; // customer -> customers, lead -> leads
            $stmt = $db->prepare("SELECT id FROM {$table} WHERE id = ?");
            $stmt->execute([$data['related_to_id']]);
            if (!$stmt->fetch()) {
                $failed++;
                $errors[] = "Row " . ($index + 2) . ": {$data['related_to_type']} ID " . $data['related_to_id'] . " không tồn tại";
                continue;
            }
        } catch (Exception $e) {
            $failed++;
            $errors[] = "Row " . ($index + 2) . ": Error validating related entity: " . $e->getMessage();
            continue;
        }
    }
    
    try {
        $id = $taskModel->create($data);
        if ($id) {
            $imported++;
            // Log activity
            logActivity('import', 'Imported task via Excel', 'task', $id, $user['id'], $data);
        } else {
            $failed++;
            $errors[] = "Row " . ($index + 2) . ": Failed to create task";
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
], "Import hoàn tất. {$imported} tasks được nhập, {$failed} thất bại.");
?>
