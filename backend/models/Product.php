<?php
/**
 * Product Model
 */

require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all products with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR product_code LIKE ? OR description LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT p.*, u.full_name as created_by_name
                  FROM products p
                  LEFT JOIN users u ON p.created_by = u.id
                  {$whereClause}
                  ORDER BY p.created_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get product by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name as created_by_name
            FROM products p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get product by code
     */
    public function getByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE product_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    /**
     * Create new product
     */
    public function create($data) {
        // Generate product code if not provided
        $productCode = $data['product_code'] ?? generateCode('PROD', 'products', 'product_code');
        
        $stmt = $this->db->prepare("
            INSERT INTO products (product_code, name, description, price, cost, category, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $productCode,
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $data['cost'] ?? 0,
            $data['category'] ?? null,
            $data['is_active'] ?? 1,
            $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update product
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['product_code', 'name', 'description', 'price', 'cost', 'category', 'is_active'];
        
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
        $sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete product
     */
    public function delete($id) {
        // Check if product is used in deals
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM deal_products WHERE product_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Cannot delete product used in deals'];
        }
        
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Get categories
     */
    public function getCategories() {
        $stmt = $this->db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get products for dropdown
     */
    public function getForDropdown() {
        $stmt = $this->db->query("SELECT id, product_code, name, price FROM products WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    }
    
    /**
     * Get product statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total products
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM products");
        $stats['total'] = $stmt->fetch()['count'];
        
        // Active products
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
        $stats['active'] = $stmt->fetch()['count'];
        
        // By category
        $stmt = $this->db->query("SELECT category, COUNT(*) as count FROM products WHERE category IS NOT NULL GROUP BY category");
        $stats['by_category'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Calculate profit margin
     */
    public function calculateMargin($cost, $price) {
        if ($price <= 0) return 0;
        return round((($price - $cost) / $price) * 100, 2);
    }
}
