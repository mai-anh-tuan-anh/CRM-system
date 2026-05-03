<?php
/**
 * Task Model
 */

require_once __DIR__ . '/../config/database.php';

class Task {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all tasks with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $where[] = "t.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = "t.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where[] = "t.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['related_to_type'])) {
            $where[] = "t.related_to_type = ?";
            $params[] = $filters['related_to_type'];
        }
        
        if (!empty($filters['related_to_id'])) {
            $where[] = "t.related_to_id = ?";
            $params[] = $filters['related_to_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(t.title LIKE ? OR t.description LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($filters['overdue'])) {
            $where[] = "t.due_date < NOW() AND t.status != 'completed'";
        }
        
        if (!empty($filters['today'])) {
            $where[] = "DATE(t.due_date) = CURDATE()";
        }
        
        if (!empty($filters['upcoming'])) {
            $where[] = "t.due_date >= NOW() AND t.status != 'completed'";
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT t.*, u.full_name as assigned_to_name, creator.full_name as created_by_name
                  FROM tasks t
                  LEFT JOIN users u ON t.assigned_to = u.id
                  LEFT JOIN users creator ON t.created_by = creator.id
                  {$whereClause}
                  ORDER BY 
                    CASE t.priority
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    t.due_date ASC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get task by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.full_name as assigned_to_name, u.avatar as assigned_to_avatar,
                   creator.full_name as created_by_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users creator ON t.created_by = creator.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new task
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO tasks (title, description, type, status, priority, related_to_type,
                related_to_id, assigned_to, due_date, reminder_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['type'] ?? 'task',
            $data['status'] ?? 'pending',
            $data['priority'] ?? 'medium',
            $data['related_to_type'],
            $data['related_to_id'],
            $data['assigned_to'],
            $data['due_date'] ?? null,
            $data['reminder_at'] ?? null,
            $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update task
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'type', 'status', 'priority', 
                         'related_to_type', 'related_to_id', 'assigned_to', 
                         'due_date', 'reminder_at'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE tasks SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Complete task
     */
    public function complete($id) {
        $stmt = $this->db->prepare("
            UPDATE tasks SET status = 'completed', completed_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
    
    /**
     * Delete task
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Get task statistics
     */
    public function getStatistics($userId = null) {
        $stats = [];
        $whereUser = $userId ? "AND assigned_to = ?" : "";
        $params = $userId ? [$userId] : [];
        
        // Total tasks
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM tasks WHERE 1=1 {$whereUser}");
        $stmt->execute($params);
        $stats['total'] = $stmt->fetch()['total'];
        
        // By status
        $stmt = $this->db->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE 1=1 {$whereUser} GROUP BY status");
        $stmt->execute($params);
        $stats['by_status'] = $stmt->fetchAll();
        
        // By priority
        $stmt = $this->db->prepare("SELECT priority, COUNT(*) as count FROM tasks WHERE 1=1 {$whereUser} GROUP BY priority");
        $stmt->execute($params);
        $stats['by_priority'] = $stmt->fetchAll();
        
        // By type
        $stmt = $this->db->prepare("SELECT type, COUNT(*) as count FROM tasks WHERE 1=1 {$whereUser} GROUP BY type");
        $stmt->execute($params);
        $stats['by_type'] = $stmt->fetchAll();
        
        // Overdue
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM tasks 
            WHERE due_date < NOW() AND status != 'completed' {$whereUser}
        ");
        $stmt->execute($params);
        $stats['overdue'] = $stmt->fetch()['count'];
        
        // Due today
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM tasks 
            WHERE DATE(due_date) = CURDATE() AND status != 'completed' {$whereUser}
        ");
        $stmt->execute($params);
        $stats['due_today'] = $stmt->fetch()['count'];
        
        // Due this week
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM tasks 
            WHERE YEARWEEK(due_date) = YEARWEEK(CURDATE()) AND status != 'completed' {$whereUser}
        ");
        $stmt->execute($params);
        $stats['due_this_week'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Get upcoming tasks
     */
    public function getUpcoming($userId = null, $limit = 5) {
        $whereUser = $userId ? "AND t.assigned_to = ?" : "";
        $params = $userId ? [$userId] : [];
        
        $sql = "
            SELECT t.*, u.full_name as assigned_to_name,
                   CASE 
                       WHEN t.related_to_type = 'customer' THEN c.full_name
                       WHEN t.related_to_type = 'lead' THEN l.full_name
                       WHEN t.related_to_type = 'deal' THEN d.title
                   END as related_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN customers c ON t.related_to_type = 'customer' AND t.related_to_id = c.id
            LEFT JOIN leads l ON t.related_to_type = 'lead' AND t.related_to_id = l.id
            LEFT JOIN deals d ON t.related_to_type = 'deal' AND t.related_to_id = d.id
            WHERE t.status != 'completed' AND t.due_date >= NOW() {$whereUser}
            ORDER BY t.due_date ASC
            LIMIT ?
        ";
        
        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get overdue tasks
     */
    public function getOverdue($userId = null, $limit = 5) {
        $whereUser = $userId ? "AND t.assigned_to = ?" : "";
        $params = $userId ? [$userId] : [];
        
        $sql = "
            SELECT t.*, u.full_name as assigned_to_name,
                   CASE 
                       WHEN t.related_to_type = 'customer' THEN c.full_name
                       WHEN t.related_to_type = 'lead' THEN l.full_name
                       WHEN t.related_to_type = 'deal' THEN d.title
                   END as related_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN customers c ON t.related_to_type = 'customer' AND t.related_to_id = c.id
            LEFT JOIN leads l ON t.related_to_type = 'lead' AND t.related_to_id = l.id
            LEFT JOIN deals d ON t.related_to_type = 'deal' AND t.related_to_id = d.id
            WHERE t.due_date < NOW() AND t.status != 'completed' {$whereUser}
            ORDER BY t.due_date ASC
            LIMIT ?
        ";
        
        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tasks for calendar
     */
    public function getForCalendar($userId = null, $startDate = null, $endDate = null) {
        $whereUser = $userId ? "AND assigned_to = ?" : "";
        $params = $userId ? [$userId] : [];
        
        $whereDate = "";
        if ($startDate && $endDate) {
            $whereDate = "AND due_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $stmt = $this->db->prepare("
            SELECT t.*, u.full_name as assigned_to_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE 1=1 {$whereUser} {$whereDate}
            ORDER BY due_date ASC
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
