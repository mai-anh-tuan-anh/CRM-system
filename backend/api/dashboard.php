<?php
/**
 * Dashboard API
 * Statistics and summary data
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../models/Deal.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Activity.php';

$action = $_GET['action'] ?? 'overview';

// Require authentication
$user = authenticate();

$dashboardModel = new Dashboard();
$dealModel = new Deal();
$leadModel = new Lead();
$taskModel = new Task();
$activityModel = new Activity();

// For sales users, filter by their assignments
$userId = ($user['role'] === 'admin' || $user['role'] === 'manager') ? null : $user['id'];

switch ($action) {
    case 'overview':
        // Get complete dashboard overview
        $data = [
            'statistics' => $dashboardModel->getStatistics($userId),
            'upcoming' => $dashboardModel->getUpcomingItems($userId, 5),
            'recent_activity' => $activityModel->getRecent(10, $userId),
            'performance' => $dashboardModel->getPerformanceMetrics($userId, 30),
            'comparison' => $dashboardModel->getComparisonData()
        ];
        
        jsonSuccess($data);
        break;
        
    case 'stats':
        // Get statistics only
        $stats = $dashboardModel->getStatistics($userId);
        jsonSuccess($stats);
        break;
        
    case 'pipeline':
        // Get pipeline data
        $filters = ($user['role'] === 'admin' || $user['role'] === 'manager') ? [] : ['assigned_to' => $user['id']];
        $pipeline = $dealModel->getPipeline($filters);
        jsonSuccess($pipeline);
        break;
        
    case 'upcoming':
        // Get upcoming tasks and deals
        $upcoming = $dashboardModel->getUpcomingItems($userId, 10);
        jsonSuccess($upcoming);
        break;
        
    case 'recent-activity':
        // Get recent activity
        $limit = $_GET['limit'] ?? 10;
        $activities = $activityModel->getRecent($limit, $userId);
        jsonSuccess($activities);
        break;
        
    case 'performance':
        // Get performance metrics
        $days = $_GET['days'] ?? 30;
        $performance = $dashboardModel->getPerformanceMetrics($userId, $days);
        jsonSuccess($performance);
        break;
        
    case 'revenue-trend':
        // Get revenue trend
        $months = $_GET['months'] ?? 12;
        $trend = $dashboardModel->getMonthlyRevenueTrend($months, $userId ? "AND d.assigned_to = {$userId}" : '');
        jsonSuccess($trend);
        break;
        
    case 'comparison':
        // Get comparison data (this month vs last month)
        $comparison = $dashboardModel->getComparisonData();
        jsonSuccess($comparison);
        break;
        
    case 'tasks-summary':
        // Get tasks summary
        $stats = $taskModel->getStatistics($userId);
        jsonSuccess($stats);
        break;
        
    case 'leads-summary':
        // Get leads summary
        $stats = $leadModel->getStatistics();
        
        // If sales user, filter by assigned leads
        if ($user['role'] === 'sales') {
            $stmt = getDB()->prepare("SELECT status, COUNT(*) as count FROM leads WHERE assigned_to = ? GROUP BY status");
            $stmt->execute([$user['id']]);
            $stats['by_status'] = $stmt->fetchAll();
            
            $stmt = getDB()->prepare("SELECT COUNT(*) as count FROM leads WHERE assigned_to = ?");
            $stmt->execute([$user['id']]);
            $stats['total'] = $stmt->fetch()['count'];
        }
        
        jsonSuccess($stats);
        break;
        
    case 'deals-summary':
        // Get deals summary
        $stats = $dealModel->getStatistics();
        
        // If sales user, filter by assigned deals
        if ($user['role'] === 'sales') {
            $db = getDB();
            $stmt = $db->prepare("SELECT stage, COUNT(*) as count, SUM(value) as total_value FROM deals WHERE assigned_to = ? GROUP BY stage");
            $stmt->execute([$user['id']]);
            $stats['by_stage'] = $stmt->fetchAll();
            
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM deals WHERE assigned_to = ?");
            $stmt->execute([$user['id']]);
            $stats['total'] = $stmt->fetch()['count'];
        }
        
        jsonSuccess($stats);
        break;
        
    default:
        jsonError('Invalid action', 400);
}
