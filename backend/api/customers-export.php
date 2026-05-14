<?php
/**
 * Customers Export API - Excel Format
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/excel.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Customer.php';

$user = authenticate();

// Build filters
$filters = [];
if ($user['role'] !== 'admin' && $user['role'] !== 'manager') {
    $filters['assigned_to'] = $user['id'];
}

// Get customers data
$customerModel = new Customer();
$result = $customerModel->getAll(1, 10000, $filters);
$customers = $result['data'] ?? [];

if (empty($customers)) {
    jsonError('Không có dữ liệu để xuất');
}

// Prepare headers and data
$headers = [
    'customer_code',
    'full_name',
    'email',
    'phone',
    'company_name',
    'address',
    'city',
    'country',
    'industry',
    'source',
    'status',
    'assigned_to_name',
    'created_at'
];

// Format data for Excel
$data = [];
foreach ($customers as $customer) {
    $data[] = [
        'customer_code' => $customer['customer_code'] ?? '',
        'full_name' => $customer['full_name'] ?? '',
        'email' => $customer['email'] ?? '',
        'phone' => $customer['phone'] ?? '',
        'company_name' => $customer['company_name'] ?? '',
        'address' => $customer['address'] ?? '',
        'city' => $customer['city'] ?? '',
        'country' => $customer['country'] ?? '',
        'industry' => $customer['industry'] ?? '',
        'source' => $customer['source'] ?? '',
        'status' => $customer['status'] ?? '',
        'assigned_to_name' => $customer['assigned_to_name'] ?? '',
        'created_at' => date('Y-m-d', strtotime($customer['created_at']))
    ];
}

// Generate Excel XML
$excelXML = generateExcelXML($headers, $data, 'Customers');

// Download file
$filename = 'customers_' . date('Y-m-d_His') . '.xls';
downloadExcelFile($excelXML, $filename);
?>