<?php
/**
 * Settings API
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Setting.php';

$method = $_SERVER['REQUEST_METHOD'];
$settingModel = new Setting();

// Require authentication
$user = authenticate();

switch ($method) {
    case 'GET':
        if (isset($_GET['key'])) {
            // Get single setting
            $value = $settingModel->getValue($_GET['key']);
            jsonSuccess(['key' => $_GET['key'], 'value' => $value]);
        } else {
            // Get all settings or specific groups
            if (isset($_GET['group'])) {
                switch ($_GET['group']) {
                    case 'company':
                        $settings = $settingModel->getCompanyInfo();
                        break;
                    case 'email':
                        $settings = $settingModel->getEmailSettings();
                        break;
                    case 'crm':
                        $settings = $settingModel->getCRMSettings();
                        break;
                    default:
                        $settings = [];
                }
            } else {
                $settings = $settingModel->getAll();
            }
            jsonSuccess($settings);
        }
        break;
        
    case 'PUT':
        // Update settings - admin only
        requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['settings']) && is_array($data['settings'])) {
            // Multiple settings update
            $updated = $settingModel->updateMultiple($data['settings'], $user['id']);
            jsonSuccess(['updated' => $updated], 'Settings updated successfully');
        } elseif (isset($data['key'])) {
            // Single setting update
            $type = $data['type'] ?? 'string';
            $description = $data['description'] ?? null;
            
            if ($settingModel->set($data['key'], $data['value'], $type, $description, $user['id'])) {
                jsonSuccess(null, 'Setting updated successfully');
            } else {
                jsonError('Failed to update setting');
            }
        } else {
            jsonError('Invalid data format');
        }
        break;
        
    case 'DELETE':
        // Delete setting - admin only
        requireAdmin();
        
        $key = $_GET['key'] ?? null;
        
        if (!$key) {
            jsonError('Setting key is required');
        }
        
        $result = $settingModel->delete($key);
        
        if ($result['success']) {
            jsonSuccess(null, 'Setting deleted successfully');
        } else {
            jsonError($result['message']);
        }
        break;
        
    default:
        jsonError('Method not allowed', 405);
}
