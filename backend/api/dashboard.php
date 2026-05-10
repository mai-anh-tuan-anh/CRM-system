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

// For viewing data: Admin, Manager, and Sales can see all data
// Only filter by user when specifically requested or for certain actions
$userId = null; // Allow all roles to see all data for dashboard overview

switch ($action) {
    case 'overview':
        try {
            // Get complete dashboard overview
            $data = [
                'statistics' => $dashboardModel->getStatistics($userId),
                'upcoming' => $dashboardModel->getUpcomingItems($userId, 5),
                'recent_activity' => $activityModel->getRecent(10, $userId),
                'performance' => $dashboardModel->getPerformanceMetrics($userId, 30),
                'comparison' => $dashboardModel->getComparisonData()
            ];
            
            jsonSuccess($data);
        } catch (Throwable $e) {
            error_log('Dashboard overview error: ' . $e->getMessage());
            jsonError('Server error: ' . $e->getMessage(), 500);
        }
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
        // Get leads summary - all roles can see all data
        $stats = $leadModel->getStatistics();
        jsonSuccess($stats);
        break;
        
    case 'deals-summary':
        // Get deals summary - all roles can see all data
        $stats = $dealModel->getStatistics();
        jsonSuccess($stats);
        break;
        
    default:
        jsonError('Invalid action', 400);
}
