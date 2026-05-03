<?php
/**
 * File Model
 */

require_once __DIR__ . '/../config/database.php';

class File {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all files with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['related_to_type'])) {
            $where[] = "f.related_to_type = ?";
            $params[] = $filters['related_to_type'];
        }
        
        if (!empty($filters['related_to_id'])) {
            $where[] = "f.related_to_id = ?";
            $params[] = $filters['related_to_id'];
        }
        
        if (!empty($filters['uploaded_by'])) {
            $where[] = "f.uploaded_by = ?";
            $params[] = $filters['uploaded_by'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(f.file_name LIKE ? OR f.original_name LIKE ? OR f.description LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT f.*, u.full_name as uploaded_by_name
                  FROM files f
                  LEFT JOIN users u ON f.uploaded_by = u.id
                  {$whereClause}
                  ORDER BY f.created_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get file by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, u.full_name as uploaded_by_name
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new file record
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO files (file_name, original_name, file_path, file_size, mime_type,
                related_to_type, related_to_id, uploaded_by, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['file_name'],
            $data['original_name'],
            $data['file_path'],
            $data['file_size'],
            $data['mime_type'],
            $data['related_to_type'],
            $data['related_to_id'],
            $data['uploaded_by'],
            $data['description'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update file
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE files SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete file
     */
    public function delete($id) {
        $file = $this->getById($id);
        
        if (!$file) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        // Delete physical file
        $fullPath = UPLOAD_PATH . $file['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Delete database record
        $stmt = $this->db->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Get files by entity
     */
    public function getByEntity($type, $id) {
        $stmt = $this->db->prepare("
            SELECT f.*, u.full_name as uploaded_by_name
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            WHERE f.related_to_type = ? AND f.related_to_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$type, $id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get file statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total files
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM files");
        $stats['total'] = $stmt->fetch()['count'];
        
        // By entity type
        $stmt = $this->db->query("
            SELECT related_to_type, COUNT(*) as count, SUM(file_size) as total_size
            FROM files
            GROUP BY related_to_type
        ");
        $stats['by_type'] = $stmt->fetchAll();
        
        // Total storage used
        $stmt = $this->db->query("SELECT SUM(file_size) as total FROM files");
        $stats['total_size'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Format file size
     */
    public function formatSize($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Get file icon class based on mime type
     */
    public function getFileIcon($mimeType) {
        $icons = [
            'image' => 'bi-image',
            'pdf' => 'bi-file-pdf',
            'word' => 'bi-file-word',
            'excel' => 'bi-file-excel',
            'powerpoint' => 'bi-file-ppt',
            'text' => 'bi-file-text',
            'video' => 'bi-file-play',
            'audio' => 'bi-file-music',
            'zip' => 'bi-file-zip',
            'code' => 'bi-file-code'
        ];
        
        foreach ($icons as $type => $icon) {
            if (strpos($mimeType, $type) !== false) {
                return $icon;
            }
        }
        
        return 'bi-file-earmark';
    }
}
