<?php
/**
 * Activities API
 * CRUD operations for activities log
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// Require authentication for all endpoints
$user = authenticate();

switch ($method) {
    case 'GET':
        // Get list with filters
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 20;

        $filters = [
            'activity_type' => $_GET['activity_type'] ?? null,
            'related_to_type' => $_GET['related_to_type'] ?? null,
            'search' => $_GET['search'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'performed_by' => $_GET['performed_by'] ?? null
        ];

        // Remove null values
        $filters = array_filter($filters, function($v) { return $v !== null; });

        $result = getActivities($page, $perPage, $filters);
        jsonSuccess($result);
        break;

    default:
        jsonError('Method not allowed', 405);
}

/**
 * Get activities with pagination and filters
 */
function getActivities($page = 1, $perPage = 20, $filters = []) {
    $db = getDB();

    $where = [];
    $params = [];

    // Activity type filter
    if (!empty($filters['activity_type'])) {
        $where[] = "a.activity_type = ?";
        $params[] = $filters['activity_type'];
    }

    // Related type filter
    if (!empty($filters['related_to_type'])) {
        $where[] = "a.related_to_type = ?";
        $params[] = $filters['related_to_type'];
    }

    // Search in description
    if (!empty($filters['search'])) {
        $where[] = "a.description LIKE ?";
        $params[] = "%{$filters['search']}%";
    }

    // Date range filter
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(a.performed_at) >= ?";
        $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = "DATE(a.performed_at) <= ?";
        $params[] = $filters['date_to'];
    }

    // Performed by filter
    if (!empty($filters['performed_by'])) {
        $where[] = "a.performed_by = ?";
        $params[] = $filters['performed_by'];
    }

    // Build query
    $sql = "
        SELECT a.*, u.full_name as performed_by_name, u.avatar as performed_by_avatar
        FROM activities a
        LEFT JOIN users u ON a.performed_by = u.id
    ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY a.performed_at DESC";

    return paginate($sql, $params, $page, $perPage);
}

/**
 * Get activity types for filter dropdown
 */
function getActivityTypes() {
    return [
        'call' => 'Gọi điện',
        'email' => 'Email',
        'meeting' => 'Họp',
        'note' => 'Ghi chú',
        'status_change' => 'Thay đổi trạng thái',
        'file_upload' => 'Tải file',
        'deal_created' => 'Tạo thỏa thuận',
        'lead_converted' => 'Chuyển đổi lead'
    ];
}