<?php
/**
 * Customer Model
 */

require_once __DIR__ . '/../config/database.php';

class Customer {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all customers with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where[] = "c.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['industry'])) {
            $where[] = "c.industry = ?";
            $params[] = $filters['industry'];
        }
        
        if (!empty($filters['source'])) {
            $where[] = "c.source = ?";
            $params[] = $filters['source'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.company_name LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT c.*, u.full_name as assigned_to_name
                  FROM customers c
                  LEFT JOIN users u ON c.assigned_to = u.id
                  {$whereClause}
                  ORDER BY c.created_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get customer by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.full_name as assigned_to_name, creator.full_name as created_by_name
            FROM customers c
            LEFT JOIN users u ON c.assigned_to = u.id
            LEFT JOIN users creator ON c.created_by = creator.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get customer by code
     */
    public function getByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE customer_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    /**
     * Create new customer
     */
    public function create($data) {
        // Generate customer code
        $customerCode = generateCode('CUS', 'customers', 'customer_code');
        
        $stmt = $this->db->prepare("
            INSERT INTO customers (customer_code, full_name, email, phone, company_name, address, 
                city, country, industry, website, source, status, assigned_to, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $customerCode,
            $data['full_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['company_name'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['country'] ?? 'Vietnam',
            $data['industry'] ?? null,
            $data['website'] ?? null,
            $data['source'] ?? null,
            $data['status'] ?? 'prospect',
            $data['assigned_to'] ?? null,
            $data['notes'] ?? null,
            $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update customer
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['full_name', 'email', 'phone', 'company_name', 'address', 
                         'city', 'country', 'industry', 'website', 'source', 
                         'status', 'assigned_to', 'notes'];
        
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
        $sql = "UPDATE customers SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete customer
     */
    public function delete($id) {
        // Check for related deals
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM deals WHERE customer_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Cannot delete customer with existing deals'];
        }
        
        $stmt = $this->db->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Get customer statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total customers
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM customers");
        $stats['total'] = $stmt->fetch()['total'];
        
        // By status
        $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM customers GROUP BY status");
        $stats['by_status'] = $stmt->fetchAll();
        
        // By industry
        $stmt = $this->db->query("SELECT industry, COUNT(*) as count FROM customers WHERE industry IS NOT NULL GROUP BY industry");
        $stats['by_industry'] = $stmt->fetchAll();
        
        // By source
        $stmt = $this->db->query("SELECT source, COUNT(*) as count FROM customers WHERE source IS NOT NULL GROUP BY source");
        $stats['by_source'] = $stmt->fetchAll();
        
        // New this month
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM customers WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['new_this_month'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    /**
     * Get industries list
     */
    public function getIndustries() {
        $stmt = $this->db->query("SELECT DISTINCT industry FROM customers WHERE industry IS NOT NULL AND industry != '' ORDER BY industry");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get sources list
     */
    public function getSources() {
        $stmt = $this->db->query("SELECT DISTINCT source FROM customers WHERE source IS NOT NULL AND source != '' ORDER BY source");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get customer deals
     */
    public function getDeals($customerId) {
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name as assigned_to_name
            FROM deals d
            LEFT JOIN users u ON d.assigned_to = u.id
            WHERE d.customer_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get customer activities
     */
    public function getActivities($customerId) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.full_name as performed_by_name
            FROM activities a
            LEFT JOIN users u ON a.performed_by = u.id
            WHERE a.related_to_type = 'customer' AND a.related_to_id = ?
            ORDER BY a.performed_at DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get customers for dropdown
     */
    public function getForDropdown() {
        $stmt = $this->db->query("SELECT id, full_name, company_name FROM customers WHERE status = 'active' ORDER BY full_name");
        return $stmt->fetchAll();
    }
    
    /**
     * Search customers (AJAX)
     */
    public function search($query, $limit = 10) {
        $search = "%{$query}%";
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, phone, company_name
            FROM customers
            WHERE (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company_name LIKE ?)
            AND status = 'active'
            LIMIT ?
        ");
        $stmt->execute([$search, $search, $search, $search, $limit]);
        return $stmt->fetchAll();
    }
}
