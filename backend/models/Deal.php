<?php
/**
 * Deal Model
 */

require_once __DIR__ . '/../config/database.php';

class Deal {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all deals with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['stage'])) {
            $where[] = "d.stage = ?";
            $params[] = $filters['stage'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where[] = "d.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['customer_id'])) {
            $where[] = "d.customer_id = ?";
            $params[] = $filters['customer_id'];
        }
        
        if (!empty($filters['min_value'])) {
            $where[] = "d.value >= ?";
            $params[] = $filters['min_value'];
        }
        
        if (!empty($filters['max_value'])) {
            $where[] = "d.value <= ?";
            $params[] = $filters['max_value'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(d.title LIKE ? OR c.full_name LIKE ? OR c.company_name LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT d.*, c.full_name as customer_name, c.company_name as customer_company,
                         u.full_name as assigned_to_name, l.full_name as lead_name
                  FROM deals d
                  LEFT JOIN customers c ON d.customer_id = c.id
                  LEFT JOIN users u ON d.assigned_to = u.id
                  LEFT JOIN leads l ON d.lead_id = l.id
                  {$whereClause}
                  ORDER BY d.created_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get deal by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT d.*, c.full_name as customer_name, c.company_name as customer_company,
                   c.email as customer_email, c.phone as customer_phone,
                   u.full_name as assigned_to_name, creator.full_name as created_by_name,
                   l.full_name as lead_name
            FROM deals d
            LEFT JOIN customers c ON d.customer_id = c.id
            LEFT JOIN users u ON d.assigned_to = u.id
            LEFT JOIN users creator ON d.created_by = creator.id
            LEFT JOIN leads l ON d.lead_id = l.id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get deal by code
     */
    public function getByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM deals WHERE deal_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    /**
     * Create new deal
     */
    public function create($data) {
        // Generate deal code
        $dealCode = generateCode('DEAL', 'deals', 'deal_code');
        
        $stmt = $this->db->prepare("
            INSERT INTO deals (deal_code, title, description, customer_id, lead_id, value, currency,
                stage, probability, expected_close_date, assigned_to, source, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $dealCode,
            $data['title'],
            $data['description'] ?? null,
            $data['customer_id'],
            $data['lead_id'] ?? null,
            $data['value'] ?? 0,
            $data['currency'] ?? 'VND',
            $data['stage'] ?? 'prospect',
            $data['probability'] ?? 0,
            $data['expected_close_date'] ?? null,
            $data['assigned_to'] ?? null,
            $data['source'] ?? null,
            $data['notes'] ?? null,
            $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update deal
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'value', 'currency', 'stage', 'probability',
                         'expected_close_date', 'actual_close_date', 'assigned_to', 'source',
                         'competitor', 'loss_reason', 'notes'];
        
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
        $sql = "UPDATE deals SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Update deal stage
     */
    public function updateStage($id, $newStage, $userId, $notes = null) {
        // Get current stage
        $stmt = $this->db->prepare("SELECT stage FROM deals WHERE id = ?");
        $stmt->execute([$id]);
        $deal = $stmt->fetch();
        
        if (!$deal) {
            return false;
        }
        
        $oldStage = $deal['stage'];
        
        // Update deal stage
        $actualCloseDate = null;
        if ($newStage === 'won' || $newStage === 'lost') {
            $actualCloseDate = date('Y-m-d');
        }
        
        $stmt = $this->db->prepare("
            UPDATE deals SET stage = ?, probability = ?, actual_close_date = ?
            WHERE id = ?
        ");
        
        // Calculate probability based on stage
        $probabilities = [
            'prospect' => 10,
            'qualification' => 25,
            'proposal' => 50,
            'negotiation' => 75,
            'won' => 100,
            'lost' => 0
        ];
        $probability = $probabilities[$newStage] ?? 0;
        
        $stmt->execute([$newStage, $probability, $actualCloseDate, $id]);
        
        // Log stage change
        $stmt = $this->db->prepare("
            INSERT INTO deal_stages_history (deal_id, from_stage, to_stage, changed_by, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id, $oldStage, $newStage, $userId, $notes]);
        
        return true;
    }
    
    /**
     * Delete deal
     */
    public function delete($id) {
        // Check for related products
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM deal_products WHERE deal_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            // Delete related products first
            $stmt = $this->db->prepare("DELETE FROM deal_products WHERE deal_id = ?");
            $stmt->execute([$id]);
        }
        
        $stmt = $this->db->prepare("DELETE FROM deals WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Get deal statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total deals
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM deals");
        $stats['total'] = $stmt->fetch()['total'];
        
        // By stage
        $stmt = $this->db->query("SELECT stage, COUNT(*) as count, SUM(value) as total_value FROM deals GROUP BY stage");
        $stats['by_stage'] = $stmt->fetchAll();
        
        // Total pipeline value
        $stmt = $this->db->query("SELECT SUM(value) as total FROM deals WHERE stage NOT IN ('won', 'lost')");
        $stats['pipeline_value'] = $stmt->fetch()['total'] ?? 0;
        
        // Won deals value
        $stmt = $this->db->query("SELECT SUM(value) as total FROM deals WHERE stage = 'won'");
        $stats['won_value'] = $stmt->fetch()['total'] ?? 0;
        
        // Won count
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM deals WHERE stage = 'won'");
        $stats['won_count'] = $stmt->fetch()['count'];
        
        // Lost count
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM deals WHERE stage = 'lost'");
        $stats['lost_count'] = $stmt->fetch()['count'];
        
        // Win rate
        $totalClosed = $stats['won_count'] + $stats['lost_count'];
        $stats['win_rate'] = $totalClosed > 0 ? round(($stats['won_count'] / $totalClosed) * 100, 2) : 0;
        
        // Average deal value
        $stmt = $this->db->query("SELECT AVG(value) as avg FROM deals");
        $stats['avg_value'] = round($stmt->fetch()['avg'] ?? 0, 2);
        
        // Monthly revenue
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(actual_close_date, '%Y-%m') as month, SUM(value) as revenue
            FROM deals
            WHERE stage = 'won' AND actual_close_date IS NOT NULL
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        ");
        $stats['monthly_revenue'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Get pipeline data (for kanban view)
     */
    public function getPipeline($filters = []) {
        $where = ["d.stage NOT IN ('won', 'lost')"];
        $params = [];
        
        if (!empty($filters['assigned_to'])) {
            $where[] = "d.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT d.*, c.full_name as customer_name, c.company_name as customer_company,
                         u.full_name as assigned_to_name, u.avatar as assigned_to_avatar
                  FROM deals d
                  LEFT JOIN customers c ON d.customer_id = c.id
                  LEFT JOIN users u ON d.assigned_to = u.id
                  {$whereClause}
                  ORDER BY d.expected_close_date ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $deals = $stmt->fetchAll();
        
        // Group by stage
        $pipeline = [
            'prospect' => [],
            'qualification' => [],
            'proposal' => [],
            'negotiation' => []
        ];
        
        foreach ($deals as $deal) {
            if (isset($pipeline[$deal['stage']])) {
                $pipeline[$deal['stage']][] = $deal;
            }
        }
        
        // Calculate stage totals
        $totals = [];
        foreach ($pipeline as $stage => $stageDeals) {
            $totals[$stage] = array_sum(array_column($stageDeals, 'value'));
        }
        
        return [
            'stages' => $pipeline,
            'totals' => $totals
        ];
    }
    
    /**
     * Get recent deals
     */
    public function getRecent($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT d.*, c.full_name as customer_name, u.full_name as assigned_to_name
            FROM deals d
            LEFT JOIN customers c ON d.customer_id = c.id
            LEFT JOIN users u ON d.assigned_to = u.id
            ORDER BY d.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get deal products
     */
    public function getProducts($dealId) {
        $stmt = $this->db->prepare("
            SELECT dp.*, p.name as product_name, p.product_code
            FROM deal_products dp
            LEFT JOIN products p ON dp.product_id = p.id
            WHERE dp.deal_id = ?
        ");
        $stmt->execute([$dealId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add product to deal
     */
    public function addProduct($dealId, $productId, $quantity, $unitPrice, $discount = 0) {
        $totalPrice = ($unitPrice * $quantity) * (1 - $discount / 100);
        
        $stmt = $this->db->prepare("
            INSERT INTO deal_products (deal_id, product_id, quantity, unit_price, discount, total_price)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$dealId, $productId, $quantity, $unitPrice, $discount, $totalPrice]);
    }
    
    /**
     * Get deal activities
     */
    public function getActivities($dealId) {
        $stmt = $this->db->prepare("
            SELECT a.*, u.full_name as performed_by_name
            FROM activities a
            LEFT JOIN users u ON a.performed_by = u.id
            WHERE a.related_to_type = 'deal' AND a.related_to_id = ?
            ORDER BY a.performed_at DESC
        ");
        $stmt->execute([$dealId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get deals by customer
     */
    public function getByCustomer($customerId) {
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
}
