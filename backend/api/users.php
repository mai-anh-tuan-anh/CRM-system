<?php
/**
 * Users API
 * CRUD operations for users
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/User.php';

$method = $_SERVER['REQUEST_METHOD'];
$userModel = new User();

// Require authentication for all endpoints
$user = authenticate();

switch ($method) {
    case 'GET':
        // Check if getting single user or list
        if (isset($_GET['id'])) {
            // Get single user - admin, manager, or self only
            if ($user['role'] !== 'admin' && $user['role'] !== 'manager' && $user['id'] != $_GET['id']) {
                jsonError('Forbidden', 403);
            }
            
            $userData = $userModel->getById($_GET['id']);
            if (!$userData) {
                jsonError('User not found', 404);
            }
            
            jsonSuccess($userData);
        } else {
            // Get list - admin and manager only
            requireAdminOrManager();
            
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            
            $filters = [
                'role' => $_GET['role'] ?? null,
                'is_active' => isset($_GET['is_active']) ? $_GET['is_active'] : null,
                'search' => $_GET['search'] ?? null
            ];
            
            // Remove null values
            $filters = array_filter($filters, function($v) { return $v !== null; });
            
            $result = $userModel->getAll($page, $perPage, $filters);
            jsonSuccess($result);
        }
        break;
        
    case 'POST':
        // Create user - admin only
        requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate
        $required = ['username', 'email', 'password', 'full_name'];
        $missing = validateRequired($data, $required);
        
        if (!empty($missing)) {
            jsonError('Required fields missing', 400, ['missing' => $missing]);
        }
        
        // Check uniqueness
        if ($userModel->getByUsername($data['username'])) {
            jsonError('Username already exists');
        }
        if ($userModel->getByEmail($data['email'])) {
            jsonError('Email already exists');
        }
        
        $data['created_by'] = $user['id'];
        $userId = $userModel->create($data);
        
        if ($userId) {
            logActivity('user_created', "Created user: {$data['username']}", 'user', $userId, $user['id']);
            jsonSuccess(['id' => $userId], 'User created successfully');
        } else {
            jsonError('Failed to create user');
        }
        break;
        
    case 'PUT':
        // Update user
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            jsonError('User ID is required');
        }
        
        $userId = $data['id'];
        $targetUser = $userModel->getById($userId);
        
        if (!$targetUser) {
            jsonError('User not found', 404);
        }
        
        // Check permissions
        if ($user['role'] !== 'admin' && $user['role'] !== 'manager' && $user['id'] != $userId) {
            jsonError('Forbidden', 403);
        }
        
        // Only admin can change role and is_active
        if ($user['role'] !== 'admin') {
            unset($data['role']);
            unset($data['is_active']);
        }
        
        // Prevent changing own role or disabling self
        if ($user['id'] == $userId) {
            unset($data['role']);
            unset($data['is_active']);
        }
        
        // Check email uniqueness if changed
        if (isset($data['email']) && $data['email'] !== $targetUser['email']) {
            if ($userModel->getByEmail($data['email'])) {
                jsonError('Email already exists');
            }
        }
        
        if ($userModel->update($userId, $data)) {
            logActivity('user_updated', "Updated user: {$targetUser['username']}", 'user', $userId, $user['id']);
            jsonSuccess(null, 'User updated successfully');
        } else {
            jsonError('Failed to update user');
        }
        break;
        
    case 'DELETE':
        // Delete user - admin only
        requireAdmin();
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonError('User ID is required');
        }
        
        // Prevent self-deletion
        if ($user['id'] == $id) {
            jsonError('Cannot delete your own account');
        }
        
        // Prevent deleting last admin
        $admins = $userModel->countByRole();
        $adminCount = 0;
        foreach ($admins as $role) {
            if ($role['role'] === 'admin') {
                $adminCount = $role['count'];
                break;
            }
        }
        
        $targetUser = $userModel->getById($id);
        if ($targetUser && $targetUser['role'] === 'admin' && $adminCount <= 1) {
            jsonError('Cannot delete the last admin account');
        }
        
        $result = $userModel->delete($id);
        
        if ($result['success']) {
            logActivity('user_deleted', "Deleted user: {$targetUser['username']}", 'user', $id, $user['id']);
            jsonSuccess(null, 'User deleted successfully');
        } else {
            jsonError($result['message']);
        }
        break;
        
    default:
        jsonError('Method not allowed', 405);
}
