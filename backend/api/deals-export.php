<?php
/**
 * Deals Export API - Excel Format
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/excel.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Deal.php';

$user = authenticate();

// Build filters
$filters = [];
if ($user['role'] !== 'admin' && $user['role'] !== 'manager') {
    $filters['assigned_to'] = $user['id'];
}

// Get deals data
$dealModel = new Deal();
$result = $dealModel->getAll(1, 10000, $filters);
$deals = $result['data'] ?? [];

if (empty($deals)) {
    jsonError('Không có dữ liệu để xuất');
}

// Prepare headers and data
$headers = [
    'deal_code',
    'title',
    'customer_name',
    'value',
    'currency',
    'stage',
    'probability',
    'expected_close_date',
    'actual_close_date',
    'source',
    'assigned_to_name',
    'created_at'
];

// Format data for Excel
$data = [];
foreach ($deals as $deal) {
    $data[] = [
        'deal_code' => $deal['deal_code'] ?? '',
        'title' => $deal['title'] ?? '',
        'customer_name' => $deal['customer_name'] ?? '',
        'value' => $deal['value'] ?? 0,
        'currency' => $deal['currency'] ?? 'VND',
        'stage' => $deal['stage'] ?? '',
        'probability' => $deal['probability'] ?? 0,
        'expected_close_date' => date('Y-m-d', strtotime($deal['expected_close_date'])),
        'actual_close_date' => $deal['actual_close_date'] ? date('Y-m-d', strtotime($deal['actual_close_date'])) : '',
        'source' => $deal['source'] ?? '',
        'assigned_to_name' => $deal['assigned_to_name'] ?? '',
        'created_at' => date('Y-m-d', strtotime($deal['created_at']))
    ];
}

// Generate Excel XML
$excelXML = generateExcelXML($headers, $data, 'Deals');

// Download file
$filename = 'deals_' . date('Y-m-d_His') . '.xls';
downloadExcelFile($excelXML, $filename);
?>
