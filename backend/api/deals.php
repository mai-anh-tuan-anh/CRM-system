<?php
/**
 * Deals API
 * CRUD operations for deals
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Deal.php';
require_once __DIR__ . '/../models/Customer.php';

$method = $_SERVER['REQUEST_METHOD'];
$dealModel = new Deal();

// Require authentication
$user = authenticate();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $deal = $dealModel->getById($_GET['id']);
            
            if (!$deal) {
                jsonError('Deal not found', 404);
            }
            
            // Check access permission
            if (!canAccessResource($deal['assigned_to'], $deal['created_by'])) {
                jsonError('Forbidden', 403);
            }
            
            // Get additional data
            if (isset($_GET['include']) && $_GET['include'] === 'full') {
                $deal['products'] = $dealModel->getProducts($deal['id']);
                $deal['activities'] = $dealModel->getActivities($deal['id']);
            }
            
            jsonSuccess($deal);
        } elseif (isset($_GET['pipeline'])) {
            // Get pipeline data (Kanban view)
            $filters = [];
            
            // All roles can see all deals
            if (!empty($_GET['assigned_to'])) {
                $filters['assigned_to'] = $_GET['assigned_to'];
            }
            
            $pipeline = $dealModel->getPipeline($filters);
            jsonSuccess($pipeline);
        } else {
            // Get list
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            
            $filters = [
                'stage' => $_GET['stage'] ?? null,
                'customer_id' => $_GET['customer_id'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            // Add value range filters
            if (!empty($_GET['min_value'])) {
                $filters['min_value'] = $_GET['min_value'];
            }
            if (!empty($_GET['max_value'])) {
                $filters['max_value'] = $_GET['max_value'];
            }
            
            // All roles can see all deals (permissions checked on write operations)
            if (!empty($_GET['assigned_to'])) {
                $filters['assigned_to'] = $_GET['assigned_to'];
            }
            
            // Remove null values
            $filters = array_filter($filters, function($v) { return $v !== null; });
            
            $result = $dealModel->getAll($page, $perPage, $filters);
            jsonSuccess($result);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['title'])) {
            jsonError('Deal title is required');
        }
        if (empty($data['customer_id'])) {
            jsonError('Customer is required');
        }
        
        // Check if customer is active
        $customerModel = new Customer();
        $customer = $customerModel->getById($data['customer_id']);
        if (!$customer) {
            jsonError('Customer not found');
        }
        if ($customer['status'] === 'inactive') {
            jsonError('Cannot create deal for inactive customer');
        }
        
        // Set created_by
        $data['created_by'] = $user['id'];
        
        // If sales user creating, assign to self
        if ($user['role'] === 'sales') {
            $data['assigned_to'] = $user['id'];
        }
        
        $dealId = $dealModel->create($data);
        
        if ($dealId) {
            logActivity('deal_created', "Đã tạo thỏa thuận: {$data['title']}", 'deal', $dealId, $user['id']);
            
            // Notify assignee
            if (!empty($data['assigned_to']) && $data['assigned_to'] != $user['id']) {
                createNotification(
                    $data['assigned_to'],
                    'Thỏa thuận mới',
                    "Bạn được giao thỏa thuận: {$data['title']}",
                    'info',
                    'deal',
                    $dealId
                );
            }
            
            jsonSuccess(['id' => $dealId], 'Deal created successfully');
        } else {
            jsonError('Failed to create deal');
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            jsonError('Deal ID is required');
        }
        
        $dealId = $data['id'];
        $deal = $dealModel->getById($dealId);
        
        if (!$deal) {
            jsonError('Deal not found', 404);
        }
        
        // Check access permission
        if (!canAccessResource($deal['assigned_to'], $deal['created_by'])) {
            jsonError('Forbidden', 403);
        }
        
        // Check if stage is being changed
        $oldStage = $deal['stage'];
        $newStage = $data['stage'] ?? $oldStage;
        
        // Check if assigned_to is being changed
        $oldAssignedTo = $deal['assigned_to'];
        $newAssignedTo = $data['assigned_to'] ?? $oldAssignedTo;
        
        // Handle stage change separately (it logs to deal_stages_history)
        if ($newStage !== $oldStage) {
            $dealModel->updateStage($dealId, $newStage, $user['id'], $data['stage_change_notes'] ?? null);
            
            // Special notifications for won/lost
            if ($newStage === 'won') {
                createNotification(
                    $deal['assigned_to'] ?? $user['id'],
                    'Thỏa thuận thành công! 🎉',
                    "Thỏa thuận {$deal['title']} đã thắng!",
                    'success',
                    'deal',
                    $dealId
                );
            } elseif ($newStage === 'lost') {
                createNotification(
                    $deal['assigned_to'] ?? $user['id'],
                    'Thỏa thuận thất bại',
                    "Thỏa thuận {$deal['title']} đã thua",
                    'warning',
                    'deal',
                    $dealId
                );
            }
        }
        
        if ($dealModel->update($dealId, $data)) {
            logActivity('deal_updated', "Đã cập nhật thỏa thuận: {$deal['title']}", 'deal', $dealId, $user['id']);
            
            // Notify new assignee
            if ($newAssignedTo != $oldAssignedTo && $newAssignedTo != $user['id']) {
                createNotification(
                    $newAssignedTo,
                    'Thỏa thuận được giao',
                    "Bạn được giao thỏa thuận: {$deal['title']}",
                    'info',
                    'deal',
                    $dealId
                );
            }
            
            jsonSuccess(null, 'Deal updated successfully');
        } else {
            jsonError('Failed to update deal');
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonError('Deal ID is required');
        }
        
        $deal = $dealModel->getById($id);
        
        if (!$deal) {
            jsonError('Deal not found', 404);
        }
        
        // Check access permission
        if (!canAccessResource($deal['assigned_to'], $deal['created_by'])) {
            jsonError('Forbidden', 403);
        }
        
        $result = $dealModel->delete($id);
        
        if ($result['success']) {
            logActivity('deal_deleted', "Đã xóa thỏa thuận: {$deal['title']}", 'deal', $id, $user['id']);
            jsonSuccess(null, 'Deal deleted successfully');
        } else {
            jsonError($result['message']);
        }
        break;
        
    default:
        jsonError('Method not allowed', 405);
}
