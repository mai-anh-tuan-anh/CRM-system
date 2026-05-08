<?php
/**
 * Customers API
 * CRUD operations for customers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Customer.php';

$method = $_SERVER['REQUEST_METHOD'];
$customerModel = new Customer();

// Require authentication
$user = authenticate();

switch ($method) {
    case 'GET':
        // Check if getting single customer or list
        if (isset($_GET['id'])) {
            $customer = $customerModel->getById($_GET['id']);
            
            if (!$customer) {
                jsonError('Customer not found', 404);
            }
            
            // Check access permission
            if (!canAccessResource($customer['assigned_to'], $customer['created_by'])) {
                jsonError('Forbidden', 403);
            }
            
            // Get additional data
            if (isset($_GET['include']) && $_GET['include'] === 'full') {
                $customer['deals'] = $customerModel->getDeals($customer['id']);
                $customer['activities'] = $customerModel->getActivities($customer['id']);
            }
            
            jsonSuccess($customer);
        } else {
            // Get list
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'industry' => $_GET['industry'] ?? null,
                'source' => $_GET['source'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            // Non-admin can see their own assigned customers OR unassigned customers
            if ($user['role'] !== 'admin' && $user['role'] !== 'manager') {
                $filters['assigned_to_or_null'] = $user['id'];
            } elseif (!empty($_GET['assigned_to'])) {
                $filters['assigned_to'] = $_GET['assigned_to'];
            }
            
            // Remove null values
            $filters = array_filter($filters, function($v) { return $v !== null; });
            
            $result = $customerModel->getAll($page, $perPage, $filters);
            jsonSuccess($result);
        }
        break;
        
    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($data['full_name'])) {
                jsonError('Customer name is required');
            }
            
            // Set created_by
            $data['created_by'] = $user['id'];
            
            // Fix: Convert empty assigned_to to null
            if (empty($data['assigned_to'])) {
                $data['assigned_to'] = null;
            }
            
            // If sales user creating, assign to self
            if ($user['role'] === 'sales') {
                $data['assigned_to'] = $user['id'];
            }
            
            $customerId = $customerModel->create($data);
            
            if ($customerId) {
                logActivity('customer_created', "Đã tạo khách hàng: {$data['full_name']}", 'customer', $customerId, $user['id']);
                
                // Create notification for assigned user
                if (!empty($data['assigned_to']) && $data['assigned_to'] != $user['id']) {
                    createNotification(
                        $data['assigned_to'],
                        'New Customer Assigned',
                        "Customer {$data['full_name']} has been assigned to you",
                        'info',
                        'customer',
                        $customerId
                    );
                }
                
                jsonSuccess(['id' => $customerId], 'Customer created successfully');
            } else {
                jsonError('Failed to create customer');
            }
        } catch (Exception $e) {
            error_log('Customer create error: ' . $e->getMessage());
            jsonError('Server error: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            jsonError('Customer ID is required');
        }
        
        $customerId = $data['id'];
        $customer = $customerModel->getById($customerId);
        
        if (!$customer) {
            jsonError('Customer not found', 404);
        }
        
        // Check access permission
        if (!canAccessResource($customer['assigned_to'], $customer['created_by'])) {
            jsonError('Forbidden', 403);
        }
        
        // Check if assigned_to is being changed
        $oldAssignedTo = $customer['assigned_to'];
        $newAssignedTo = $data['assigned_to'] ?? $oldAssignedTo;
        
        if ($customerModel->update($customerId, $data)) {
            logActivity('customer_updated', "Đã cập nhật khách hàng: {$customer['full_name']}", 'customer', $customerId, $user['id']);
            
            // Notify new assignee
            if ($newAssignedTo != $oldAssignedTo && $newAssignedTo != $user['id']) {
                createNotification(
                    $newAssignedTo,
                    'Customer Assigned',
                    "Customer {$customer['full_name']} has been assigned to you",
                    'info',
                    'customer',
                    $customerId
                );
            }
            
            jsonSuccess(null, 'Customer updated successfully');
        } else {
            jsonError('Failed to update customer');
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonError('Customer ID is required');
        }
        
        $customer = $customerModel->getById($id);
        
        if (!$customer) {
            jsonError('Customer not found', 404);
        }
        
        // Check access permission
        if (!canAccessResource($customer['assigned_to'], $customer['created_by'])) {
            jsonError('Forbidden', 403);
        }
        
        // Only admin/manager or creator can delete
        if ($user['role'] !== 'admin' && $user['role'] !== 'manager' && $customer['created_by'] != $user['id']) {
            jsonError('Forbidden', 403);
        }
        
        $result = $customerModel->delete($id);
        
        if ($result['success']) {
            logActivity('customer_deleted', "Đã xóa khách hàng: {$customer['full_name']}", 'customer', $id, $user['id']);
            jsonSuccess(null, 'Customer deleted successfully');
        } else {
            jsonError($result['message']);
        }
        break;
        
    default:
        jsonError('Method not allowed', 405);
}
