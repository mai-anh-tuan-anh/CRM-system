<?php
/**
 * User Model
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['role'])) {
            $where[] = "role = ?";
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT id, username, email, full_name, phone, role, avatar, is_active, last_login, created_at 
                  FROM users 
                  {$whereClause} 
                  ORDER BY created_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, full_name, phone, role, avatar, is_active, last_login, created_at
            FROM users WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get user by username
     */
    public function getByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password, full_name, phone, role, avatar, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['full_name'],
            $data['phone'] ?? null,
            $data['role'] ?? 'sales',
            $data['avatar'] ?? null,
            $data['is_active'] ?? 1,
            $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        // Check if address column exists, create if not
        $hasAddressColumn = false;
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'address'");
            $hasAddressColumn = $stmt->fetch() !== false;
            
            // Auto-create address column if not exists
            if (!$hasAddressColumn) {
                $this->db->exec("ALTER TABLE users ADD COLUMN address VARCHAR(255) NULL AFTER phone");
                $hasAddressColumn = true;
            }
        } catch (Exception $e) {
            $hasAddressColumn = false;
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        if (isset($data['full_name'])) {
            $fields[] = "full_name = ?";
            $params[] = $data['full_name'];
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
        }
        if (isset($data['address']) && $hasAddressColumn) {
            $fields[] = "address = ?";
            $params[] = $data['address'];
        }
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $params[] = $data['role'];
        }
        if (isset($data['avatar'])) {
            $fields[] = "avatar = ?";
            $params[] = $data['avatar'];
        }
        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $params[] = $data['is_active'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete user
     */
    public function delete($id) {
        // Check if user has related records
        $tables = ['customers', 'leads', 'deals', 'tasks'];
        foreach ($tables as $table) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE assigned_to = ? OR created_by = ?");
            $stmt->execute([$id, $id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Cannot delete user with existing records'];
            }
        }
        
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
        
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Update last login
     */
    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($userId, $password) {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return true;
        }
        return false;
    }
    
    /**
     * Count users by role
     */
    public function countByRole() {
        $stmt = $this->db->query("SELECT role, COUNT(*) as count FROM users WHERE is_active = 1 GROUP BY role");
        return $stmt->fetchAll();
    }
    
    /**
     * Get users for dropdown
     */
    public function getForDropdown($role = null) {
        $sql = "SELECT id, full_name, role FROM users WHERE is_active = 1";
        $params = [];
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY full_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
