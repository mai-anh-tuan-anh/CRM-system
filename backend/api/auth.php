<?php
/**
 * Authentication API
 * Endpoints: login, logout, register, me, check
 */

// CORS Headers - Cho phép request từ frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../models/User.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$userModel = new User();

switch ($action) {
    case 'login':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['username']) || empty($data['password'])) {
            jsonError('Username and password are required');
        }
        
        $username = sanitize($data['username']);
        $password = $data['password'];
        $remember = $data['remember'] ?? false;
        
        // Find user
        $user = $userModel->getByUsername($username);
        
        if (!$user) {
            // Try email
            $user = $userModel->getByEmail($username);
        }
        
        if (!$user) {
            jsonError('Invalid credentials', 401);
        }
        
        // Check password
        if (!password_verify($password, $user['password'])) {
            jsonError('Invalid credentials', 401);
        }
        
        // Check if active
        if (!$user['is_active']) {
            jsonError('Account is disabled', 403);
        }
        
        // Update last login
        $userModel->updateLastLogin($user['id']);
        
        // Set session
        startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['avatar'] = $user['avatar'];
        
        // Set session expiry if remember me
        if ($remember) {
            $_SESSION['expires'] = time() + (30 * 24 * 60 * 60); // 30 days
        } else {
            $_SESSION['expires'] = time() + (8 * 60 * 60); // 8 hours
        }
        
        // Log activity
        logActivity('login', 'User logged in', 'user', $user['id'], $user['id']);
        
        jsonSuccess([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'avatar' => $user['avatar']
        ], 'Login successful');
        break;
        
    case 'logout':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        
        startSession();
        
        if (isset($_SESSION['user_id'])) {
            logActivity('logout', 'User logged out', 'user', $_SESSION['user_id'], $_SESSION['user_id']);
        }
        
        // Clear session
        $_SESSION = [];
        session_destroy();
        
        jsonSuccess(null, 'Logout successful');
        break;
        
    case 'register':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        
        // Only admin can register new users
        requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'email', 'password', 'full_name'];
        $missing = validateRequired($data, $required);
        
        if (!empty($missing)) {
            jsonError('Required fields missing', 400, ['missing' => $missing]);
        }
        
        // Check if username exists
        if ($userModel->getByUsername($data['username'])) {
            jsonError('Username already exists');
        }
        
        // Check if email exists
        if ($userModel->getByEmail($data['email'])) {
            jsonError('Email already exists');
        }
        
        // Validate role
        $allowedRoles = ['admin', 'sales', 'manager'];
        if (isset($data['role']) && !in_array($data['role'], $allowedRoles)) {
            jsonError('Invalid role');
        }
        
        // Create user
        $currentUser = getCurrentUser();
        $data['created_by'] = $currentUser['id'];
        
        $userId = $userModel->create($data);
        
        if ($userId) {
            logActivity('user_created', "User account created: {$data['username']}", 'user', $userId, $currentUser['id']);
            jsonSuccess(['id' => $userId], 'User registered successfully');
        } else {
            jsonError('Failed to create user');
        }
        break;
        
    case 'me':
        if ($method !== 'GET') {
            jsonError('Method not allowed', 405);
        }
        
        $user = authenticate();
        jsonSuccess($user);
        break;
        
    case 'check':
        if ($method !== 'GET') {
            jsonError('Method not allowed', 405);
        }
        
        startSession();
        
        if (isLoggedIn()) {
            // Check session expiry
            if (isset($_SESSION['expires']) && time() > $_SESSION['expires']) {
                $_SESSION = [];
                session_destroy();
                jsonSuccess(['authenticated' => false]);
            }
            
            jsonSuccess([
                'authenticated' => true,
                'user' => getCurrentUser()
            ]);
        } else {
            jsonSuccess(['authenticated' => false]);
        }
        break;
        
    case 'change-password':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        
        $user = authenticate();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['current_password']) || empty($data['new_password'])) {
            jsonError('Current password and new password are required');
        }
        
        // Verify current password
        if (!$userModel->verifyPassword($user['id'], $data['current_password'])) {
            jsonError('Current password is incorrect');
        }
        
        // Update password
        if ($userModel->update($user['id'], ['password' => $data['new_password']])) {
            jsonSuccess(null, 'Password changed successfully');
        } else {
            jsonError('Failed to change password');
        }
        break;
        
    default:
        jsonError('Invalid action', 400);
}
