<?php
/**
 * Leads Export API - Excel Format
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/excel.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Lead.php';

$user = authenticate();

// Build filters
$filters = [];
// All roles can export all data

// Get leads data
$leadModel = new Lead();
$result = $leadModel->getAll(1, 10000, $filters);
$leads = $result['data'] ?? [];

if (empty($leads)) {
    jsonError('Không có dữ liệu để xuất');
}

// Prepare headers and data
$headers = [
    'lead_code',
    'full_name', 
    'email',
    'phone',
    'company_name',
    'job_title',
    'source',
    'status',
    'priority',
    'score',
    'created_at'
];

// Format data for Excel
$data = [];
foreach ($leads as $lead) {
    $data[] = [
        'lead_code' => $lead['lead_code'] ?? '',
        'full_name' => $lead['full_name'] ?? '',
        'email' => $lead['email'] ?? '',
        'phone' => $lead['phone'] ?? '',
        'company_name' => $lead['company_name'] ?? '',
        'job_title' => $lead['job_title'] ?? '',
        'source' => $lead['source'] ?? '',
        'status' => $lead['status'] ?? '',
        'priority' => $lead['priority'] ?? '',
        'score' => $lead['score'] ?? 0,
        'created_at' => date('Y-m-d', strtotime($lead['created_at']))
    ];
}

// Generate Excel XML
$excelXML = generateExcelXML($headers, $data, 'Leads');

// Download file
$filename = 'leads_' . date('Y-m-d_His') . '.xls';
downloadExcelFile($excelXML, $filename);
?>
