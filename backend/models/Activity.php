<?php
/**
 * Activity Model
 */

require_once __DIR__ . '/../config/database.php';

class Activity {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all activities with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['activity_type'])) {
            $where[] = "a.activity_type = ?";
            $params[] = $filters['activity_type'];
        }
        
        if (!empty($filters['performed_by'])) {
            $where[] = "a.performed_by = ?";
            $params[] = $filters['performed_by'];
        }
        
        if (!empty($filters['related_to_type'])) {
            $where[] = "a.related_to_type = ?";
            $params[] = $filters['related_to_type'];
        }
        
        if (!empty($filters['related_to_id'])) {
            $where[] = "a.related_to_id = ?";
            $params[] = $filters['related_to_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "a.performed_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "a.performed_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT a.*, u.full_name as performed_by_name, u.avatar as performed_by_avatar
                  FROM activities a
                  LEFT JOIN users u ON a.performed_by = u.id
                  {$whereClause}
                  ORDER BY a.performed_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get activity by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.full_name as performed_by_name, u.avatar as performed_by_avatar
            FROM activities a
            LEFT JOIN users u ON a.performed_by = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new activity
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO activities (activity_type, description, related_to_type, related_to_id, performed_by, metadata)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['activity_type'],
            $data['description'] ?? null,
            $data['related_to_type'],
            $data['related_to_id'],
            $data['performed_by'],
            isset($data['metadata']) ? json_encode($data['metadata']) : null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get recent activities
     */
    public function getRecent($limit = 10, $userId = null) {
        $params = [];
        $whereUser = "";
        
        if ($userId) {
            // Get activities for items assigned to this user
            $whereUser = "AND (
                (a.related_to_type = 'customer' AND a.related_to_id IN (SELECT id FROM customers WHERE assigned_to = ?))
                OR (a.related_to_type = 'lead' AND a.related_to_id IN (SELECT id FROM leads WHERE assigned_to = ?))
                OR (a.related_to_type = 'deal' AND a.related_to_id IN (SELECT id FROM deals WHERE assigned_to = ?))
                OR a.performed_by = ?
            )";
            $params = [$userId, $userId, $userId, $userId];
        }
        
        $sql = "
            SELECT a.*, u.full_name as performed_by_name, u.avatar as performed_by_avatar
            FROM activities a
            LEFT JOIN users u ON a.performed_by = u.id
            WHERE 1=1 {$whereUser}
            ORDER BY a.performed_at DESC
            LIMIT ?
        ";
        
        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get activity statistics
     */
    public function getStatistics($userId = null, $days = 30) {
        $stats = [];
        $whereUser = "";
        $params = [];
        
        if ($userId) {
            $whereUser = "AND performed_by = ?";
            $params[] = $userId;
        }
        
        // By type
        $stmt = $this->db->prepare("
            SELECT activity_type, COUNT(*) as count 
            FROM activities 
            WHERE performed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) {$whereUser}
            GROUP BY activity_type
        ");
        $stmt->execute(array_merge([$days], $params));
        $stats['by_type'] = $stmt->fetchAll();
        
        // Daily activity count
        $stmt = $this->db->prepare("
            SELECT DATE(performed_at) as date, COUNT(*) as count 
            FROM activities 
            WHERE performed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) {$whereUser}
            GROUP BY DATE(performed_at)
            ORDER BY date ASC
        ");
        $stmt->execute(array_merge([$days], $params));
        $stats['daily'] = $stmt->fetchAll();
        
        // Total count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM activities 
            WHERE performed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) {$whereUser}
        ");
        $stmt->execute(array_merge([$days], $params));
        $stats['total'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Get activities for specific entity
     */
    public function getByEntity($type, $id, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.full_name as performed_by_name, u.avatar as performed_by_avatar
            FROM activities a
            LEFT JOIN users u ON a.performed_by = u.id
            WHERE a.related_to_type = ? AND a.related_to_id = ?
            ORDER BY a.performed_at DESC
            LIMIT ?
        ");
        $stmt->execute([$type, $id, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Delete old activities
     */
    public function deleteOld($days = 365) {
        $stmt = $this->db->prepare("DELETE FROM activities WHERE performed_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
}
