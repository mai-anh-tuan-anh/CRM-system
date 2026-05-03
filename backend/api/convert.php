<?php
/**
 * Convert API
 * Lead to Customer conversion
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Deal.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonError('Method not allowed', 405);
}

$user = authenticate();
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['lead_id'])) {
    jsonError('Lead ID is required');
}

$leadModel = new Lead();
$customerModel = new Customer();
$dealModel = new Deal();

$lead = $leadModel->getById($data['lead_id']);

if (!$lead) {
    jsonError('Lead not found', 404);
}

// Check access permission
if (!canAccessResource($lead['assigned_to'], $lead['created_by'])) {
    jsonError('Forbidden', 403);
}

// Check if already converted
if ($lead['status'] === 'converted') {
    jsonError('Lead is already converted');
}

// Prepare customer data
$customerData = [
    'full_name' => $lead['full_name'],
    'email' => $lead['email'],
    'phone' => $lead['phone'],
    'company_name' => $lead['company_name'],
    'source' => $lead['source'],
    'assigned_to' => $lead['assigned_to'] ?? $user['id'],
    'created_by' => $user['id'],
    'notes' => "Converted from lead {$lead['lead_code']}\n\n" . ($lead['notes'] ?? '')
];

// Override with provided data if any
if (!empty($data['customer_data'])) {
    $customerData = array_merge($customerData, $data['customer_data']);
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Create customer
    $customerId = $customerModel->create($customerData);
    
    if (!$customerId) {
        throw new Exception('Failed to create customer');
    }
    
    // Convert lead
    $leadModel->convertToCustomer($lead['id'], $customerId, $user['id']);
    
    // Create deal if value provided
    $dealId = null;
    if (!empty($data['create_deal']) && !empty($data['deal_value'])) {
        $dealData = [
            'title' => $data['deal_title'] ?? "Deal from {$lead['full_name']}",
            'customer_id' => $customerId,
            'lead_id' => $lead['id'],
            'value' => $data['deal_value'],
            'stage' => $data['deal_stage'] ?? 'prospect',
            'assigned_to' => $customerData['assigned_to'],
            'created_by' => $user['id']
        ];
        
        $dealId = $dealModel->create($dealData);
    }
    
    $db->commit();
    
    // Log activity
    logActivity('lead_converted', "Lead {$lead['full_name']} converted to customer", 'lead', $lead['id'], $user['id']);
    logActivity('customer_created', "Customer created from lead conversion", 'customer', $customerId, $user['id']);
    
    // Create notification
    createNotification(
        $customerData['assigned_to'],
        'Lead Converted',
        "Lead {$lead['full_name']} has been converted to customer",
        'success',
        'customer',
        $customerId
    );
    
    jsonSuccess([
        'lead_id' => $lead['id'],
        'customer_id' => $customerId,
        'deal_id' => $dealId
    ], 'Lead converted successfully');
    
} catch (Exception $e) {
    $db->rollBack();
    jsonError('Conversion failed: ' . $e->getMessage());
}
