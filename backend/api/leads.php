<?php
/**
 * Leads API
 * CRUD operations for leads
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Customer.php';

$method = $_SERVER['REQUEST_METHOD'];
$leadModel = new Lead();
$customerModel = new Customer();

// Require authentication
$user = authenticate();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $lead = $leadModel->getById($_GET['id']);
            
            if (!$lead) {
                jsonError('Lead not found', 404);
            }
            
            // Check access permission
            if (!canAccessResource($lead['assigned_to'], $lead['created_by'])) {
                jsonError('Forbidden', 403);
            }
            
            jsonSuccess($lead);
        } else {
            // Get list
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'source' => $_GET['source'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            // All roles can see all leads (permissions checked on write operations)
            // Sales/Manager can view all but only edit their own
            if (!empty($_GET['assigned_to'])) {
                $filters['assigned_to'] = $_GET['assigned_to'];
            }
            
            // Remove null values
            $filters = array_filter($filters, function($v) { return $v !== null; });
            
            $result = $leadModel->getAll($page, $perPage, $filters);
            jsonSuccess($result);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['full_name'])) {
            jsonError('Lead name is required');
        }
        
        // Set created_by
        $data['created_by'] = $user['id'];
        
        // If sales user creating, assign to self
        if ($user['role'] === 'sales') {
            $data['assigned_to'] = $user['id'];
        }
        
        $leadId = $leadModel->create($data);
        
        if ($leadId) {
            logActivity('lead_created', "Đã tạo khách hàng tiềm năng: {$data['full_name']}", 'lead', $leadId, $user['id']);
            
            // Notify assignee
            if (!empty($data['assigned_to']) && $data['assigned_to'] != $user['id']) {
                createNotification(
                    $data['assigned_to'],
                    'Khách hàng tiềm năng mới',
                    "Bạn được giao khách hàng tiềm năng: {$data['full_name']}",
                    'info',
                    'lead',
                    $leadId
                );
            }
            
            jsonSuccess(['id' => $leadId], 'Lead created successfully');
        } else {
            jsonError('Failed to create lead');
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            jsonError('Lead ID is required');
        }
        
        $leadId = $data['id'];
        $lead = $leadModel->getById($leadId);
        
        if (!$lead) {
            jsonError('Lead not found', 404);
        }
        
        // Check access permission
        if (!canAccessResource($lead['assigned_to'], $lead['created_by'])) {
            jsonError('Forbidden', 403);
        }
        
        // Check if status is being changed
        $oldStatus = $lead['status'];
        $newStatus = $data['status'] ?? $oldStatus;
        
        // Check if assigned_to is being changed
        $oldAssignedTo = $lead['assigned_to'];
        $newAssignedTo = $data['assigned_to'] ?? $oldAssignedTo;
        
        if ($leadModel->update($leadId, $data)) {
            // Log status change
            if ($newStatus !== $oldStatus) {
                logActivity('status_change', "Đã thay đổi trạng thái lead từ {$oldStatus} thành {$newStatus}", 'lead', $leadId, $user['id'], ['from' => $oldStatus, 'to' => $newStatus]);
            }
            
            logActivity('lead_updated', "Đã cập nhật khách hàng tiềm năng: {$lead['full_name']}", 'lead', $leadId, $user['id']);
            
            // Notify new assignee
            if ($newAssignedTo != $oldAssignedTo && $newAssignedTo != $user['id']) {
                createNotification(
                    $newAssignedTo,
                    'KH tiềm năng được giao',
                    "Bạn được giao KH tiềm năng: {$lead['full_name']}",
                    'info',
                    'lead',
                    $leadId
                );
            }
            
            jsonSuccess(null, 'Lead updated successfully');
        } else {
            jsonError('Failed to update lead');
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonError('Lead ID is required');
        }
        
        $lead = $leadModel->getById($id);
        
        if (!$lead) {
            jsonError('Lead not found', 404);
        }
        
        // Check access permission
        if (!canAccessResource($lead['assigned_to'], $lead['created_by'])) {
            jsonError('Forbidden', 403);
        }
        
        $result = $leadModel->delete($id);
        
        if ($result['success']) {
            logActivity('lead_deleted', "Đã xóa khách hàng tiềm năng: {$lead['full_name']}", 'lead', $id, $user['id']);
            jsonSuccess(null, 'Lead deleted successfully');
        } else {
            jsonError($result['message']);
        }
        break;
        
    default:
        jsonError('Method not allowed', 405);
}
