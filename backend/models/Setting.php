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
     * Supports both formats:
     * 1. Flat object: ['key1' => 'value1', 'key2' => 'value2']
     * 2. Array of objects: [['key' => 'key1', 'value' => 'value1'], ...]
     */
    public function updateMultiple($settings, $userId = null) {
        $updated = 0;
        
        // Check if it's a flat object (associative array with string keys)
        if (is_array($settings) && !empty($settings)) {
            $firstKey = array_keys($settings)[0];
            
            // If first key is string and value is not an array with 'key' property -> flat format
            if (is_string($firstKey) && (!is_array($settings[$firstKey]) || !isset($settings[$firstKey]['key']))) {
                // Flat object format: ['key1' => 'value1', 'key2' => 'value2']
                foreach ($settings as $key => $value) {
                    if ($this->set($key, $value, 'string', null, $userId)) {
                        $updated++;
                    }
                }
            } else {
                // Array of objects format: [['key' => 'key1', 'value' => 'value1'], ...]
                foreach ($settings as $setting) {
                    if (isset($setting['key']) && isset($setting['value'])) {
                        if ($this->set($setting['key'], $setting['value'], $setting['type'] ?? 'string', $setting['description'] ?? null, $userId)) {
                            $updated++;
                        }
                    }
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
            'company_name' => $this->getValue('company_name', 'My Company'),
            'company_email' => $this->getValue('company_email', ''),
            'company_phone' => $this->getValue('company_phone', ''),
            'company_address' => $this->getValue('company_address', ''),
            'logo_url' => $this->getValue('logo_url', ''),
            'favicon_url' => $this->getValue('favicon_url', '')
        ];
    }
    
    /**
     * Get email settings
     */
    public function getEmailSettings() {
        return [
            'smtp_host' => $this->getValue('smtp_host', ''),
            'smtp_port' => $this->getValue('smtp_port', ''),
            'smtp_username' => $this->getValue('smtp_username', ''),
            'smtp_password' => $this->getValue('smtp_password', ''),
            'smtp_secure' => $this->getValue('smtp_secure', '0')
        ];
    }
    
    /**
     * Get CRM settings
     */
    public function getCRMSettings() {
        return [
            'default_language' => $this->getValue('default_language', 'vi'),
            'default_currency' => $this->getValue('default_currency', 'VND'),
            'timezone' => $this->getValue('timezone', 'Asia/Ho_Chi_Minh'),
            'date_format' => $this->getValue('date_format', 'd/m/Y')
        ];
    }
}