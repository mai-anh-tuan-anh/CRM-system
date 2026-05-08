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

// Prepare customer data
$customerData = [
    'full_name' => $lead['full_name'],
    'email' => $lead['email'],
    'phone' => $lead['phone'],
    'dob' => $lead['dob'],
    'company_name' => $lead['company_name'],
    'address' => $lead['address'],
    'city' => $lead['city'],
    'website' => $lead['website'],
    'source' => $lead['source'],
    'status' => 'active', // Default status is active for converted leads
    'assigned_to' => $lead['assigned_to'] ?? $user['id'],
    'created_by' => $user['id'],
    'notes' => "Chuyển đổi từ lead {$lead['lead_code']}\n\n" . ($lead['notes'] ?? '')
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
    
    // Delete lead completely (instead of just marking as converted)
    $leadModel->delete($lead['id']);
    
    // Create deal if value provided
    $dealId = null;
    if (!empty($data['create_deal']) && !empty($data['deal_value'])) {
        $dealData = [
            'title' => $data['deal_title'] ?? "Deal from {$lead['full_name']}",
            'customer_id' => $customerId,
            'value' => $data['deal_value'],
            'stage' => $data['deal_stage'] ?? 'prospect',
            'assigned_to' => $customerData['assigned_to'],
            'created_by' => $user['id']
        ];
        
        $dealId = $dealModel->create($dealData);
    }
    
    $db->commit();
    
    // Log activity
    logActivity('lead_deleted', "Lead {$lead['full_name']} converted và đã xóa khỏi hệ thống", 'lead', $lead['id'], $user['id']);
    logActivity('customer_created', "Tạo khách hàng từ lead conversion", 'customer', $customerId, $user['id']);
    
    // Create notification
    createNotification(
        $customerData['assigned_to'],
        'Chuyển đổi thành công',
        "Lead {$lead['full_name']} đã được chuyển đổi thành khách hàng và xóa khỏi danh sách leads",
        'success',
        'customer',
        $customerId
    );
    
    jsonSuccess([
        'lead_id' => $lead['id'],
        'customer_id' => $customerId,
        'deal_id' => $dealId
    ], 'Chuyển đổi lead thành khách hàng thành công');
    
} catch (Exception $e) {
    $db->rollBack();
    jsonError('Conversion failed: ' . $e->getMessage());
}
