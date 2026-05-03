<?php
/**
 * Notification Model
 */

require_once __DIR__ . '/../config/database.php';

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all notifications for user
     */
    public function getAll($userId, $page = 1, $perPage = 20, $unreadOnly = false) {
        $where = "user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }
        
        $query = "SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get notification by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new notification
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_to_type, related_to_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['message'],
            $data['type'] ?? 'info',
            $data['related_to_type'] ?? null,
            $data['related_to_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id, $userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$id, $userId]);
    }
    
    /**
     * Mark all as read
     */
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Delete notification
     */
    public function delete($id, $userId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'];
    }
    
    /**
     * Get recent notifications
     */
    public function getRecent($userId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Delete old notifications
     */
    public function deleteOld($days = 30) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
    
    /**
     * Create bulk notifications
     */
    public function createBulk($userIds, $title, $message, $type = 'info', $relatedType = null, $relatedId = null) {
        $created = 0;
        foreach ($userIds as $userId) {
            if ($this->create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'related_to_type' => $relatedType,
                'related_to_id' => $relatedId
            ])) {
                $created++;
            }
        }
        return $created;
    }
}
