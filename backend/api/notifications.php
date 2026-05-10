<?php
/**
 * Notifications API
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Notification.php';

$method = $_SERVER['REQUEST_METHOD'];
$notificationModel = new Notification();

// Require authentication
$user = authenticate();

switch ($method) {
    case 'GET':
        if (isset($_GET['count'])) {
            // Get unread count
            $count = $notificationModel->getUnreadCount($user['id']);
            jsonSuccess(['count' => $count]);
        } elseif (isset($_GET['recent'])) {
            // Get recent notifications
            $limit = intval($_GET['limit'] ?? 5);
            $notifications = $notificationModel->getRecent($user['id'], $limit);
            jsonSuccess($notifications);
        } else {
            // Get list
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            $unreadOnly = isset($_GET['unread']) ? true : false;
            
            $result = $notificationModel->getAll($user['id'], $page, $perPage, $unreadOnly);
            jsonSuccess($result);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action']) && $data['action'] === 'mark_all_read') {
            // Mark all as read
            if ($notificationModel->markAllAsRead($user['id'])) {
                jsonSuccess(null, 'Đã đánh dấu tất cả thông báo là đã đọc');
            } else {
                jsonError('Không thể đánh dấu thông báo đã đọc');
            }
        } elseif (!empty($data['id'])) {
            // Mark single as read
            if ($notificationModel->markAsRead($data['id'], $user['id'])) {
                jsonSuccess(null, 'Đã đánh dấu thông báo đã đọc');
            } else {
                jsonError('Không thể đánh dấu thông báo đã đọc');
            }
        } else {
            jsonError('Yêu cầu ID thông báo hoặc hành động');
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonError('Yêu cầu ID thông báo');
        }
        
        $result = $notificationModel->delete($id, $user['id']);
        
        if ($result['success']) {
            jsonSuccess(null, 'Đã xóa thông báo thành công');
        } else {
            jsonError($result['message']);
        }
        break;
        
    default:
        jsonError('Phương thức không được phép', 405);
}
