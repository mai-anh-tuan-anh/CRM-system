<?php
/**
 * Tasks Export API - Excel Format
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/excel.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Task.php';

$user = authenticate();

// Build filters
$filters = [];
if ($user['role'] !== 'admin' && $user['role'] !== 'manager') {
    $filters['assigned_to'] = $user['id'];
}

// Get tasks data
$taskModel = new Task();
$result = $taskModel->getAll(1, 10000, $filters);
$tasks = $result['data'] ?? [];

if (empty($tasks)) {
    jsonError('Không có dữ liệu để xuất');
}

// Prepare headers and data
$headers = [
    'title',
    'type',
    'status',
    'priority',
    'due_date',
    'related_to_type',
    'related_to_id',
    'assigned_to_name',
    'created_at'
];

// Format data for Excel
$data = [];
foreach ($tasks as $task) {
    $data[] = [
        'title' => $task['title'] ?? '',
        'type' => $task['type'] ?? '',
        'status' => $task['status'] ?? '',
        'priority' => $task['priority'] ?? '',
        'due_date' => $task['due_date'] ? date('Y-m-d', strtotime($task['due_date'])) : '',
        'related_to_type' => $task['related_to_type'] ?? '',
        'related_to_id' => $task['related_to_id'] ?? '',
        'assigned_to_name' => $task['assigned_to_name'] ?? '',
        'created_at' => date('Y-m-d', strtotime($task['created_at']))
    ];
}

// Generate Excel XML
$excelXML = generateExcelXML($headers, $data, 'Tasks');

// Download file
$filename = 'tasks_' . date('Y-m-d_His') . '.xls';
downloadExcelFile($excelXML, $filename);
?>
