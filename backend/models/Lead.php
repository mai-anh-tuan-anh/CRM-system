<?php
/**
 * Lead Model
 */

require_once __DIR__ . '/../config/database.php';

class Lead {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all leads with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "l.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where[] = "l.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = "l.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['source'])) {
            $where[] = "l.source = ?";
            $params[] = $filters['source'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(l.full_name LIKE ? OR l.email LIKE ? OR l.phone LIKE ? OR l.company_name LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT l.*, u.full_name as assigned_to_name, c.full_name as converted_to_customer_name
                  FROM leads l
                  LEFT JOIN users u ON l.assigned_to = u.id
                  LEFT JOIN customers c ON l.converted_to_customer_id = c.id
                  {$whereClause}
                  ORDER BY l.created_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get lead by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT l.*, u.full_name as assigned_to_name, creator.full_name as created_by_name,
                   c.full_name as converted_to_customer_name
            FROM leads l
            LEFT JOIN users u ON l.assigned_to = u.id
            LEFT JOIN users creator ON l.created_by = creator.id
            LEFT JOIN customers c ON l.converted_to_customer_id = c.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get lead by code
     */
    public function getByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM leads WHERE lead_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    /**
     * Create new lead
     */
    public function create($data) {
        // Generate lead code
        $leadCode = generateCode('LEAD', 'leads', 'lead_code');
        
        $stmt = $this->db->prepare("
            INSERT INTO leads (lead_code, full_name, email, phone, company_name, job_title,
                source, status, priority, score, assigned_to, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $leadCode,
            $data['full_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['company_name'] ?? null,
            $data['job_title'] ?? null,
            $data['source'] ?? null,
            $data['status'] ?? 'new',
            $data['priority'] ?? 'medium',
            $data['score'] ?? 0,
            $data['assigned_to'] ?? null,
            $data['notes'] ?? null,
            $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update lead
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['full_name', 'email', 'phone', 'company_name', 'job_title',
                         'source', 'status', 'priority', 'score', 'assigned_to', 'notes'];
        
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
        $sql = "UPDATE leads SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete lead
     */
    public function delete($id) {
        // Check if lead is converted
        $stmt = $this->db->prepare("SELECT status FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        $lead = $stmt->fetch();
        
        if ($lead && $lead['status'] === 'converted') {
            return ['success' => false, 'message' => 'Cannot delete converted lead'];
        }
        
        $stmt = $this->db->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Convert lead to customer
     */
    public function convertToCustomer($leadId, $customerId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE leads 
            SET status = 'converted', converted_to_customer_id = ?, converted_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$customerId, $leadId]);
    }
    
    /**
     * Get lead statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total leads
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM leads");
        $stats['total'] = $stmt->fetch()['total'];
        
        // By status
        $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM leads GROUP BY status");
        $stats['by_status'] = $stmt->fetchAll();
        
        // By source
        $stmt = $this->db->query("SELECT source, COUNT(*) as count FROM leads WHERE source IS NOT NULL GROUP BY source");
        $stats['by_source'] = $stmt->fetchAll();
        
        // By priority
        $stmt = $this->db->query("SELECT priority, COUNT(*) as count FROM leads GROUP BY priority");
        $stats['by_priority'] = $stmt->fetchAll();
        
        // New this month
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM leads WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['new_this_month'] = $stmt->fetch()['count'];
        
        // Converted count
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM leads WHERE status = 'converted'");
        $stats['converted'] = $stmt->fetch()['count'];
        
        // Conversion rate
        if ($stats['total'] > 0) {
            $stats['conversion_rate'] = round(($stats['converted'] / $stats['total']) * 100, 2);
        } else {
            $stats['conversion_rate'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Get sources list
     */
    public function getSources() {
        $stmt = $this->db->query("SELECT DISTINCT source FROM leads WHERE source IS NOT NULL AND source != '' ORDER BY source");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Search leads (AJAX)
     */
    public function search($query, $limit = 10) {
        $search = "%{$query}%";
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, phone, company_name, status
            FROM leads
            WHERE (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company_name LIKE ?)
            AND status != 'converted'
            LIMIT ?
        ");
        $stmt->execute([$search, $search, $search, $search, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent leads
     */
    public function getRecent($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT l.*, u.full_name as assigned_to_name
            FROM leads l
            LEFT JOIN users u ON l.assigned_to = u.id
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
