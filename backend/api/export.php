<?php
/**
 * Export API
 * Export data to CSV/Excel
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Deal.php';
require_once __DIR__ . '/../models/Task.php';

$user = authenticate();
$type = $_GET['type'] ?? '';

$allowedTypes = ['customers', 'leads', 'deals', 'tasks'];

if (!in_array($type, $allowedTypes)) {
    jsonError('Invalid export type');
}

// Build filter
$filters = [];
if ($user['role'] !== 'admin' && $user['role'] !== 'manager') {
    $filters['assigned_to'] = $user['id'];
}

// Get data based on type
$data = [];
$headers = [];

switch ($type) {
    case 'customers':
        $model = new Customer();
        $result = $model->getAll(1, 10000, $filters);
        $data = $result['data'] ?? [];
        $headers = ['customer_code', 'full_name', 'email', 'phone', 'company_name', 
                   'address', 'city', 'industry', 'source', 'status', 'notes', 'created_at'];
        break;
        
    case 'leads':
        $model = new Lead();
        $result = $model->getAll(1, 10000, $filters);
        $data = $result['data'] ?? [];
        $headers = ['lead_code', 'full_name', 'email', 'phone', 'company_name', 
                   'job_title', 'source', 'status', 'priority', 'score', 'notes', 'created_at'];
        break;
        
    case 'deals':
        $model = new Deal();
        $result = $model->getAll(1, 10000, $filters);
        $data = $result['data'] ?? [];
        $headers = ['deal_code', 'title', 'customer_name', 'value', 'currency', 
                   'stage', 'probability', 'expected_close_date', 'notes', 'created_at'];
        break;
        
    case 'tasks':
        $model = new Task();
        $result = $model->getAll(1, 10000, $filters);
        $data = $result['data'] ?? [];
        $headers = ['title', 'type', 'status', 'priority', 'related_to_type', 
                   'due_date', 'assigned_to_name', 'created_at'];
        break;
}

if (empty($data)) {
    jsonError('No data to export');
}

// Export to CSV
$filename = $type . '_' . date('Y-m-d_His') . '.csv';
$result = exportToCSV($data, $filename, $headers);

if ($result['success']) {
    // Set headers for file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');
    
    echo $result['content'];
    exit;
} else {
    jsonError($result['message']);
}
