<?php
/**
 * Import API
 * Import data from CSV
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Lead.php';

$user = requireAdminOrManager();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonError('Method not allowed', 405);
}

if (empty($_FILES['file'])) {
    jsonError('No file uploaded');
}

$type = $_POST['type'] ?? '';

if (!in_array($type, ['customers', 'leads'])) {
    jsonError('Invalid import type');
}

// Upload and parse CSV
$uploadResult = uploadFile($_FILES['file'], 'imports');

if (!$uploadResult['success']) {
    jsonError($uploadResult['message']);
}

$csvFile = UPLOAD_PATH . $uploadResult['path'];

// Define required and allowed columns
$requiredColumns = ['full_name'];
$allowedColumns = [
    'customers' => ['full_name', 'email', 'phone', 'company_name', 'address', 
                   'city', 'country', 'industry', 'source', 'notes'],
    'leads' => ['full_name', 'email', 'phone', 'company_name', 'job_title', 
                'source', 'priority', 'notes']
];

// Import data
$importResult = importFromCSV($csvFile, $requiredColumns);

// Clean up uploaded file
unlink($csvFile);

if (!$importResult['success']) {
    jsonError($importResult['message']);
}

// Process import
$imported = 0;
$failed = 0;
$errors = [];

$model = ($type === 'customers') ? new Customer() : new Lead();

foreach ($importResult['data'] as $index => $row) {
    // Clean and validate data
    $data = [];
    foreach ($allowedColumns[$type] as $column) {
        if (isset($row[$column])) {
            $data[$column] = sanitize($row[$column]);
        }
    }
    
    // Set required fields
    $data['created_by'] = $user['id'];
    $data['assigned_to'] = $_POST['assigned_to'] ?? $user['id'];
    
    if ($type === 'customers') {
        $data['status'] = $_POST['default_status'] ?? 'prospect';
    } else {
        $data['status'] = 'new';
        $data['priority'] = $data['priority'] ?? 'medium';
    }
    
    try {
        $id = $model->create($data);
        if ($id) {
            $imported++;
        } else {
            $failed++;
            $errors[] = "Row {$index}: Failed to create record";
        }
    } catch (Exception $e) {
        $failed++;
        $errors[] = "Row {$index}: {$e->getMessage()}";
    }
}

jsonSuccess([
    'total_rows' => $importResult['count'],
    'imported' => $imported,
    'failed' => $failed,
    'errors' => $errors
], "Import completed. {$imported} records imported, {$failed} failed.");
