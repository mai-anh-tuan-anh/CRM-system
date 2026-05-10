<?php
/**
 * Tasks API
 * CRUD operations for tasks
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Customer.php';

$method = $_SERVER['REQUEST_METHOD'];
$taskModel = new Task();

// Require authentication
$user = authenticate();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $task = $taskModel->getById($_GET['id']);
            
            if (!$task) {
                jsonError('Task not found', 404);
            }
            
            // Check access permission
            if (!canAccessResource($task['assigned_to'], $task['created_by'])) {
                jsonError('Forbidden', 403);
            }
            
            jsonSuccess($task);
        } else {
            // Get list
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'type' => $_GET['type'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'related_to_type' => $_GET['related_to_type'] ?? null,
                'related_to_id' => $_GET['related_to_id'] ?? null,
                'search' => $_GET['search'] ?? null,
                'overdue' => isset($_GET['overdue']) ? true : null,
                'today' => isset($_GET['today']) ? true : null,
                'upcoming' => isset($_GET['upcoming']) ? true : null
            ];
            
            // All roles can see all tasks (permissions checked on write operations)
            if (!empty($_GET['assigned_to'])) {
                $filters['assigned_to'] = $_GET['assigned_to'];
            }
            
            // Remove null values
            $filters = array_filter($filters, function($v) { return $v !== null; });
            
            $result = $taskModel->getAll($page, $perPage, $filters);
            jsonSuccess($result);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['title'])) {
            jsonError('Task title is required');
        }
        if (empty($data['related_to_type']) || empty($data['related_to_id'])) {
            jsonError('Related entity is required');
        }
        if (empty($data['assigned_to'])) {
            jsonError('Assignee is required');
        }
        
        // Check if related customer is active (if task is related to customer or deal)
        if ($data['related_to_type'] === 'customer') {
            $customerModel = new Customer();
            $customer = $customerModel->getById($data['related_to_id']);
            if (!$customer) {
                jsonError('Customer not found');
            }
            if ($customer['status'] === 'inactive') {
                jsonError('Cannot create task for inactive customer');
            }
        }
        
        // Set created_by
        $data['created_by'] = $user['id'];
        
        $taskId = $taskModel->create($data);
        
        if ($taskId) {
            logActivity('task_created', "Đã tạo công việc: {$data['title']}", 'task', $taskId, $user['id']);
            
            // Notify assignee
            if ($data['assigned_to'] != $user['id']) {
                createNotification(
                    $data['assigned_to'],
                    'Công việc mới',
                    "Bạn được giao công việc: {$data['title']}",
                    'info',
                    'task',
                    $taskId
                );
            }
            
            jsonSuccess(['id' => $taskId], 'Task created successfully');
        } else {
            jsonError('Failed to create task');
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            jsonError('Task ID is required');
        }
        
        $taskId = $data['id'];
        $task = $taskModel->getById($taskId);
        
        if (!$task) {
            jsonError('Task not found', 404);
        }
        
        // Check access permission
        if (!canAccessResource($task['assigned_to'], $task['created_by'])) {
            jsonError('Forbidden', 403);
        }
        
        // Handle complete action
        if (isset($data['action']) && $data['action'] === 'complete') {
            if ($taskModel->complete($taskId)) {
                logActivity('task_completed', "Đã hoàn thành công việc: {$task['title']}", 'task', $taskId, $user['id']);
                
                // Notify creator
                if ($task['created_by'] && $task['created_by'] != $user['id']) {
                    createNotification(
                        $task['created_by'],
                        'Hoàn thành công việc',
                        "{$user['full_name']} đã hoàn thành công việc: {$task['title']}",
                        'success',
                        'task',
                        $taskId
                    );
                }
                
                jsonSuccess(null, 'Task completed successfully');
            } else {
                jsonError('Failed to complete task');
            }
            break;
        }
        
        // Check if assigned_to is being changed
        $oldAssignedTo = $task['assigned_to'];
        $newAssignedTo = $data['assigned_to'] ?? $oldAssignedTo;
        
        if ($taskModel->update($taskId, $data)) {
            logActivity('task_updated', "Đã cập nhật công việc: {$task['title']}", 'task', $taskId, $user['id']);
            
            // Notify new assignee
            if ($newAssignedTo != $oldAssignedTo && $newAssignedTo != $user['id']) {
                createNotification(
                    $newAssignedTo,
                    'Công việc được giao',
                    "Bạn được giao công việc: {$task['title']}",
                    'info',
                    'task',
                    $taskId
                );
            }
            
            jsonSuccess(null, 'Task updated successfully');
        } else {
            jsonError('Failed to update task');
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonError('Task ID is required');
        }
        
        $task = $taskModel->getById($id);
        
        if (!$task) {
            jsonError('Task not found', 404);
        }
        
        // Check access permission
        if (!canAccessResource($task['assigned_to'], $task['created_by'])) {
            jsonError('Forbidden', 403);
        }
        
        // Only admin/manager or creator can delete
        if ($user['role'] !== 'admin' && $user['role'] !== 'manager' && $task['created_by'] != $user['id']) {
            jsonError('Forbidden', 403);
        }
        
        $result = $taskModel->delete($id);
        
        if ($result['success']) {
            logActivity('task_deleted', "Đã xóa công việc: {$task['title']}", 'task', $id, $user['id']);
            jsonSuccess(null, 'Task deleted successfully');
        } else {
            jsonError($result['message']);
        }
        break;
        
    default:
        jsonError('Method not allowed', 405);
}
