<?php
/**
 * Setting Model
 */

require_once __DIR__ . '/../config/database.php';

class Setting {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get all settings
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM settings ORDER BY setting_key");
        return $stmt->fetchAll();
    }
    
    /**
     * Get setting by key
     */
    public function get($key) {
        $stmt = $this->db->prepare("SELECT * FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetch();
    }
    
    /**
     * Get setting value
     */
    public function getValue($key, $default = null) {
        $setting = $this->get($key);
        
        if (!$setting) {
            return $default;
        }
        
        $value = $setting['setting_value'];
        
        // Convert based on type
        switch ($setting['setting_type']) {
            case 'boolean':
                return $value === 'true' || $value === '1' || $value === 1;
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return explode(',', $value);
            default:
                return $value;
        }
    }
    
    /**
     * Get multiple settings as array
     */
    public function getMultiple($keys) {
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $this->getValue($key);
        }
        return $settings;
    }
    
    /**
     * Create or update setting
     */
    public function set($key, $value, $type = 'string', $description = null, $userId = null) {
        // Check if exists
        $existing = $this->get($key);
        
        // Convert value based on type
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif ($type === 'array' && is_array($value)) {
            $value = implode(',', $value);
        } elseif ($type === 'boolean') {
            $value = $value ? 'true' : 'false';
        }
        
        if ($existing) {
            // Update
            $stmt = $this->db->prepare("
                UPDATE settings 
                SET setting_value = ?, setting_type = ?, description = ?, updated_by = ?
                WHERE setting_key = ?
            ");
            return $stmt->execute([$value, $type, $description, $userId, $key]);
        } else {
            // Create
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, setting_type, description, updated_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$key, $value, $type, $description, $userId]);
        }
    }
    
    /**
     * Update multiple settings
     */
    public function updateMultiple($settings, $userId = null) {
        $updated = 0;
        foreach ($settings as $setting) {
            if (isset($setting['key']) && isset($setting['value'])) {
                if ($this->set($setting['key'], $setting['value'], $setting['type'] ?? 'string', $setting['description'] ?? null, $userId)) {
                    $updated++;
                }
            }
        }
        return $updated;
    }
    
    /**
     * Delete setting
     */
    public function delete($key) {
        $stmt = $this->db->prepare("DELETE FROM settings WHERE setting_key = ? AND is_editable = 1");
        $stmt->execute([$key]);
        return ['success' => $stmt->rowCount() > 0];
    }
    
    /**
     * Get company info
     */
    public function getCompanyInfo() {
        return [
            'name' => $this->getValue('company_name', 'My Company'),
            'email' => $this->getValue('company_email', 'contact@company.com'),
            'phone' => $this->getValue('company_phone', ''),
            'address' => $this->getValue('company_address', ''),
            'logo' => $this->getValue('company_logo', null),
            'currency' => $this->getValue('currency', 'VND')
        ];
    }
    
    /**
     * Get email settings
     */
    public function getEmailSettings() {
        return [
            'smtp_host' => $this->getValue('email_smtp_host', 'smtp.gmail.com'),
            'smtp_port' => $this->getValue('email_smtp_port', 587),
            'smtp_encryption' => $this->getValue('email_smtp_encryption', 'tls'),
            'smtp_username' => $this->getValue('email_smtp_username', ''),
            'smtp_password' => $this->getValue('email_smtp_password', ''),
            'from_address' => $this->getValue('email_from_address', 'noreply@crm.local'),
            'from_name' => $this->getValue('email_from_name', 'CRM System')
        ];
    }
    
    /**
     * Get CRM settings
     */
    public function getCRMSettings() {
        return [
            'items_per_page' => $this->getValue('items_per_page', 20),
            'lead_auto_assign' => $this->getValue('lead_auto_assign', false),
            'deal_stages' => $this->getValue('deal_stages', ['prospect', 'qualification', 'proposal', 'negotiation', 'won', 'lost']),
            'lead_sources' => $this->getValue('lead_sources', ['Website', 'Social Media', 'Referral', 'Email', 'Phone', 'Event', 'Other']),
            'date_format' => $this->getValue('date_format', 'd/m/Y'),
            'time_format' => $this->getValue('time_format', 'H:i')
        ];
    }
}
