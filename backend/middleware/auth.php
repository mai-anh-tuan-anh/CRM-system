<?php
/**
 * Authentication Middleware
 */

require_once __DIR__ . '/../utils/helpers.php';

/**
 * Authenticate API request
 */
function authenticate() {
    startSession();
    
    if (!isLoggedIn()) {
        jsonError('Unauthorized. Please login.', 401);
    }
    
    return getCurrentUser();
}

/**
 * Authorize by role
 */
function authorize($roles) {
    $user = authenticate();
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($user['role'], $roles)) {
        jsonError('Forbidden. Insufficient permissions.', 403);
    }
    
    return $user;
}

/**
 * Check if user can manage other users
 */
function canManageUsers() {
    $user = authenticate();
    return in_array($user['role'], ['admin', 'manager']);
}

/**
 * Check if user is admin only
 */
function requireAdmin() {
    return authorize('admin');
}

/**
 * Check if user is admin or manager
 */
function requireAdminOrManager() {
    return authorize(['admin', 'manager']);
}

/**
 * Check ownership or admin access
 */
function checkOwnership($assignedTo, $createdBy = null) {
    $user = authenticate();
    
    if (in_array($user['role'], ['admin', 'manager'])) {
        return true;
    }
    
    if ($assignedTo == $user['id'] || $createdBy == $user['id']) {
        return true;
    }
    
    jsonError('Forbidden. You do not have access to this resource.', 403);
}
