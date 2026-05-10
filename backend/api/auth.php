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
require_once __DIR__ . '/../middleware/auth.php';
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
        logActivity('login', 'Đã đăng nhập vào hệ thống', 'user', $user['id'], $user['id']);
        
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
            logActivity('logout', 'Đã đăng xuất khỏi hệ thống', 'user', $_SESSION['user_id'], $_SESSION['user_id']);
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
        
        // Refresh user data from database to ensure avatar sync
        $dbUser = $userModel->getById($user['id']);
        if ($dbUser) {
            $_SESSION['avatar'] = $dbUser['avatar'];
            $_SESSION['full_name'] = $dbUser['full_name'];
            $_SESSION['email'] = $dbUser['email'];
            $user = getCurrentUser();
        }
        
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
            
            // Refresh user data from database to ensure avatar sync
            $dbUser = $userModel->getById($_SESSION['user_id']);
            if ($dbUser) {
                $_SESSION['avatar'] = $dbUser['avatar'];
                $_SESSION['full_name'] = $dbUser['full_name'];
                $_SESSION['email'] = $dbUser['email'];
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
        
    case 'profile':
        if ($method !== 'GET') {
            jsonError('Method not allowed', 405);
        }
        
        $user = authenticate();
        // Remove sensitive data
        unset($user['password']);
        jsonSuccess($user);
        break;
        
    case 'update_profile':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        
        $user = authenticate();
        $updateData = [];
        
        // Handle text fields
        if (isset($_POST['full_name'])) {
            $updateData['full_name'] = sanitize($_POST['full_name']);
        }
        if (isset($_POST['email'])) {
            $email = sanitize($_POST['email']);
            // Check if email is unique (excluding current user)
            $existing = $userModel->getByEmail($email);
            if ($existing && $existing['id'] != $user['id']) {
                jsonError('Email đã được sử dụng bởi tài khoản khác');
            }
            $updateData['email'] = $email;
        }
        if (isset($_POST['phone'])) {
            $updateData['phone'] = sanitize($_POST['phone']);
        }
        if (isset($_POST['address'])) {
            $updateData['address'] = sanitize($_POST['address']);
        }
        
        // Handle avatar upload
        $uploadedAvatar = null;
        error_log("FILES data: " . json_encode($_FILES));
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            error_log("Avatar file received: " . $_FILES['avatar']['name'] . " (" . $_FILES['avatar']['size'] . " bytes)");
            $uploadDir = __DIR__ . '/../../uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $fileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
                jsonError('Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WebP)');
            }
            
            // Validate file size (max 2MB)
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                jsonError('Kích thước file không được vượt quá 2MB');
            }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
                error_log("Avatar uploaded successfully to: " . $uploadPath);
                // Delete old avatar if exists
                if (!empty($user['avatar'])) {
                    $oldAvatarPath = __DIR__ . '/../..' . parse_url($user['avatar'], PHP_URL_PATH);
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
                
                $uploadedAvatar = '/customer_management/uploads/avatars/' . $fileName;
                $updateData['avatar'] = $uploadedAvatar;
            } else {
                jsonError('Không thể upload ảnh');
            }
        }
        
        error_log("Update data: " . json_encode($updateData));
        if (empty($updateData)) {
            jsonError('Không có dữ liệu để cập nhật');
        }
        
        try {
            if ($userModel->update($user['id'], $updateData)) {
                // Update session data - individual keys used by getCurrentUser()
                if (isset($updateData['email'])) {
                    $_SESSION['email'] = $updateData['email'];
                }
                if (isset($updateData['full_name'])) {
                    $_SESSION['full_name'] = $updateData['full_name'];
                }
                if (isset($updateData['phone'])) {
                    $_SESSION['phone'] = $updateData['phone'];
                }
                if (isset($updateData['address'])) {
                    $_SESSION['address'] = $updateData['address'];
                }
                if (isset($updateData['avatar'])) {
                    $_SESSION['avatar'] = $updateData['avatar'];
                }
                jsonSuccess(['avatar' => $uploadedAvatar], 'Cập nhật hồ sơ thành công');
            } else {
                jsonError('Cập nhật thất bại');
            }
        } catch (Exception $e) {
            jsonError('Database error: ' . $e->getMessage());
        }
        break;
        
    case 'change_password':
        if ($method !== 'PUT') {
            jsonError('Method not allowed', 405);
        }
        
        $user = authenticate();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['current_password']) || empty($data['new_password'])) {
            jsonError('Current password and new password are required');
        }
        
        if (strlen($data['new_password']) < 6) {
            jsonError('New password must be at least 6 characters');
        }
        
        // Verify current password
        $dbUser = $userModel->findById($user['id']);
        if (!$dbUser || !password_verify($data['current_password'], $dbUser['password'])) {
            jsonError('Current password is incorrect');
        }
        
        // Update password
        $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
        if ($userModel->update($user['id'], ['password' => $hashedPassword])) {
            jsonSuccess(null, 'Password changed successfully');
        } else {
            jsonError('Failed to change password');
        }
        break;
        
    default:
        jsonError('Invalid action', 400);
}
