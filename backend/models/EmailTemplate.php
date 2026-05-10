<?php
/**
 * Email Template Model
 */

require_once __DIR__ . '/../config/database.php';

class EmailTemplate {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all templates
     */
    public function getAll($page = 1, $perPage = 20, $activeOnly = false) {
        $where = "";
        $params = [];
        
        if ($activeOnly) {
            $where = "WHERE is_active = 1";
        }
        
        $query = "SELECT et.*, u.full_name as created_by_name
                  FROM email_templates et
                  LEFT JOIN users u ON et.created_by = u.id
                  {$where}
                  ORDER BY et.created_at DESC";
        
        return paginate($query, $params, $page, $perPage);
    }
    
    /**
     * Get template by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT et.*, u.full_name as created_by_name
            FROM email_templates et
            LEFT JOIN users u ON et.created_by = u.id
            WHERE et.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get template by name
     */
    public function getByName($name) {
        $stmt = $this->db->prepare("
            SELECT * FROM email_templates 
            WHERE name = ? AND is_active = 1
        ");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }
    
    /**
     * Create new template
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO email_templates (name, subject, body, variables, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['name'],
            $data['subject'],
            $data['body'],
            is_array($data['variables']) ? json_encode($data['variables']) : $data['variables'],
            $data['is_active'] ?? 1,
            $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update template
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['name', 'subject', 'body', 'variables', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                if ($field === 'variables' && is_array($data[$field])) {
                    $params[] = json_encode($data[$field]);
                } else {
                    $params[] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE email_templates SET " . implode(", ", $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete template
     */
    public function delete($id) {
        // Check if template is used in emails
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM emails WHERE template_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Cannot delete template used in emails'];
        }
        
        $stmt = $this->db->prepare("DELETE FROM email_templates WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Parse template variables
     * Auto-inject company info
     */
    public function parseTemplate($template, $variables) {
        $content = $template['body'];
        $subject = $template['subject'];
        
        // Load company info from settings
        $companyName = $this->getSettingValue('company_name', 'My Company');
        $companyEmail = $this->getSettingValue('company_email', '');
        
        // Auto-inject company variables
        $variables['company_name'] = $companyName;
        $variables['company_email'] = $companyEmail;
        
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
            $subject = str_replace($placeholder, $value, $subject);
        }
        
        return [
            'subject' => $subject,
            'body' => $content
        ];
    }
    
    /**
     * Get setting value helper
     */
    private function getSettingValue($key, $default = null) {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    }
    
    /**
     * Get available variables for template
     */
    public function getVariables($templateId) {
        $template = $this->getById($templateId);
        
        if (!$template || empty($template['variables'])) {
            return [];
        }
        
        $variables = json_decode($template['variables'], true);
        return $variables ?: [];
    }
    
    /**
     * Duplicate template
     */
    public function duplicate($id, $newName = null) {
        $template = $this->getById($id);
        
        if (!$template) {
            return false;
        }
        
        $data = [
            'name' => $newName ?: $template['name'] . ' (Copy)',
            'subject' => $template['subject'],
            'body' => $template['body'],
            'variables' => $template['variables'],
            'is_active' => 1,
            'created_by' => $template['created_by']
        ];
        
        return $this->create($data);
    }
    
    /**
     * Get templates for dropdown
     */
    public function getForDropdown() {
        $stmt = $this->db->query("SELECT id, name, subject FROM email_templates WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    }
    
    /**
     * Send email using template
     */
    public function sendEmail($templateId, $to, $variables, $from = null, $userId = null) {
        $template = $this->getById($templateId);
        
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }
        
        $parsed = $this->parseTemplate($template, $variables);
        
        // Log email
        // Get company email for from address
        $companyEmail = $this->getSettingValue('company_email', 'noreply@crm.local');
        
        $stmt = $this->db->prepare("
            INSERT INTO emails (template_id, from_email, to_email, subject, body, status, sent_by)
            VALUES (?, ?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $templateId,
            $from ?: $companyEmail,
            $to,
            $parsed['subject'],
            $parsed['body'],
            $userId
        ]);
        $emailId = $this->db->lastInsertId();
        
        // Send email
        $result = sendEmail($to, $parsed['subject'], $parsed['body'], $from);
        
        // Update email log
        $status = $result['success'] ? 'sent' : 'failed';
        $stmt = $this->db->prepare("UPDATE emails SET status = ?, sent_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $emailId]);
        
        return array_merge($result, ['email_id' => $emailId]);
    }
}
