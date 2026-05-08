<?php
/**
 * Files API
 * File upload, download, and management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/File.php';

$method = $_SERVER['REQUEST_METHOD'];
$fileModel = new File();

// Require authentication
$user = authenticate();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get file info
            $file = $fileModel->getById($_GET['id']);
            
            if (!$file) {
                jsonError('File not found', 404);
            }
            
            // Check if download requested
            if (isset($_GET['download']) && $_GET['download'] == 1) {
                $fullPath = UPLOAD_PATH . $file['file_path'];
                
                if (!file_exists($fullPath)) {
                    jsonError('File not found on server', 404);
                }
                
                // Set headers for download
                header('Content-Type: ' . $file['mime_type']);
                header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
                header('Content-Length: ' . $file['file_size']);
                header('Cache-Control: no-cache');
                
                readfile($fullPath);
                exit;
            }
            
            jsonSuccess($file);
        } else {
            // Get list
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            
            $filters = [
                'related_to_type' => $_GET['related_to_type'] ?? null,
                'related_to_id' => $_GET['related_to_id'] ?? null,
                'uploaded_by' => $_GET['uploaded_by'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            // Remove null values
            $filters = array_filter($filters, function($v) { return $v !== null; });
            
            $result = $fileModel->getAll($page, $perPage, $filters);
            jsonSuccess($result);
        }
        break;
        
    case 'POST':
        // Handle file upload
        if (empty($_POST['related_to_type']) || empty($_POST['related_to_id'])) {
            jsonError('Related entity information is required');
        }
        
        if (empty($_FILES['file'])) {
            jsonError('No file uploaded');
        }
        
        $uploadResult = uploadFile($_FILES['file'], $_POST['related_to_type']);
        
        if (!$uploadResult['success']) {
            jsonError($uploadResult['message']);
        }
        
        // Save file record
        $fileData = [
            'file_name' => $uploadResult['filename'],
            'original_name' => $uploadResult['original_name'],
            'file_path' => $uploadResult['path'],
            'file_size' => $uploadResult['size'],
            'mime_type' => $uploadResult['mime_type'],
            'related_to_type' => $_POST['related_to_type'],
            'related_to_id' => $_POST['related_to_id'],
            'uploaded_by' => $user['id'],
            'description' => $_POST['description'] ?? null
        ];
        
        $fileId = $fileModel->create($fileData);
        
        if ($fileId) {
            logActivity('file_upload', "Đã tải file: {$uploadResult['original_name']}", $_POST['related_to_type'], $_POST['related_to_id'], $user['id']);
            
            jsonSuccess([
                'id' => $fileId,
                'file' => $uploadResult
            ], 'File uploaded successfully');
        } else {
            // Delete uploaded file if database insert failed
            deleteFile($uploadResult['path']);
            jsonError('Failed to save file record');
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            jsonError('File ID is required');
        }
        
        $file = $fileModel->getById($data['id']);
        
        if (!$file) {
            jsonError('File not found', 404);
        }
        
        // Only uploader or admin can update
        if ($file['uploaded_by'] != $user['id'] && $user['role'] !== 'admin') {
            jsonError('Forbidden', 403);
        }
        
        if ($fileModel->update($data['id'], $data)) {
            jsonSuccess(null, 'File updated successfully');
        } else {
            jsonError('Failed to update file');
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonError('File ID is required');
        }
        
        $file = $fileModel->getById($id);
        
        if (!$file) {
            jsonError('File not found', 404);
        }
        
        // Only uploader or admin can delete
        if ($file['uploaded_by'] != $user['id'] && $user['role'] !== 'admin') {
            jsonError('Forbidden', 403);
        }
        
        $result = $fileModel->delete($id);
        
        if ($result['success']) {
            logActivity('file_deleted', "Đã xóa file: {$file['original_name']}", $file['related_to_type'], $file['related_to_id'], $user['id']);
            jsonSuccess(null, 'File deleted successfully');
        } else {
            jsonError($result['message']);
        }
        break;
        
    default:
        jsonError('Method not allowed', 405);
}
