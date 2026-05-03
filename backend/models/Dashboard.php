<?php
/**
 * Dashboard Model - Aggregates statistics from all modules
 */

require_once __DIR__ . '/../config/database.php';

class Dashboard {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get complete dashboard statistics
     */
    public function getStatistics($userId = null) {
        $stats = [];
        
        // Add user filter where applicable
        $userFilter = $userId ? "AND assigned_to = {$userId}" : "";
        $userFilterCustomers = $userId ? "AND (assigned_to = {$userId} OR created_by = {$userId})" : "";
        
        // ===== CUSTOMERS =====
        // Total customers
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM customers WHERE 1=1 {$userFilterCustomers}");
        $stats['customers'] = [
            'total' => $stmt->fetch()['count'],
            'new_this_month' => $this->getNewThisMonth('customers', $userFilterCustomers),
            'active' => $this->getCountByStatus('customers', 'active', $userFilterCustomers),
            'prospect' => $this->getCountByStatus('customers', 'prospect', $userFilterCustomers)
        ];
        
        // ===== LEADS =====
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM leads WHERE 1=1 {$userFilter}");
        $totalLeads = $stmt->fetch()['count'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM leads WHERE status = 'converted' {$userFilter}");
        $convertedLeads = $stmt->fetch()['count'];
        
        $stats['leads'] = [
            'total' => $totalLeads,
            'new_this_month' => $this->getNewThisMonth('leads', $userFilter),
            'converted' => $convertedLeads,
            'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0,
            'by_status' => $this->getGroupedCounts('leads', 'status', $userFilter)
        ];
        
        // ===== DEALS =====
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM deals WHERE 1=1 {$userFilter}");
        $totalDeals = $stmt->fetch()['count'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as count, SUM(value) as total FROM deals WHERE stage = 'won' {$userFilter}");
        $wonData = $stmt->fetch();
        
        $stmt = $this->db->query("SELECT SUM(value) as total FROM deals WHERE stage NOT IN ('won', 'lost') {$userFilter}");
        $pipelineValue = $stmt->fetch()['total'] ?? 0;
        
        $stats['deals'] = [
            'total' => $totalDeals,
            'won' => $wonData['count'],
            'won_value' => $wonData['total'] ?? 0,
            'pipeline_value' => $pipelineValue,
            'win_rate' => $this->calculateWinRate($userFilter),
            'avg_deal_value' => $this->getAvgDealValue($userFilter),
            'by_stage' => $this->getGroupedCounts('deals', 'stage', $userFilter, true)
        ];
        
        // ===== TASKS =====
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM tasks WHERE 1=1 {$userFilter}");
        $totalTasks = $stmt->fetch()['count'];
        
        $stmt = $this->db->query("
            SELECT COUNT(*) as count FROM tasks 
            WHERE status != 'completed' AND due_date < NOW() {$userFilter}
        ");
        $overdueTasks = $stmt->fetch()['count'];
        
        $stmt = $this->db->query("
            SELECT COUNT(*) as count FROM tasks 
            WHERE DATE(due_date) = CURDATE() AND status != 'completed' {$userFilter}
        ");
        $todayTasks = $stmt->fetch()['count'];
        
        $stats['tasks'] = [
            'total' => $totalTasks,
            'pending' => $this->getCountByStatus('tasks', 'pending', $userFilter),
            'in_progress' => $this->getCountByStatus('tasks', 'in_progress', $userFilter),
            'completed' => $this->getCountByStatus('tasks', 'completed', $userFilter),
            'overdue' => $overdueTasks,
            'due_today' => $todayTasks
        ];
        
        // ===== REVENUE =====
        $stats['revenue'] = [
            'this_month' => $this->getRevenueByPeriod('month', $userFilter),
            'this_quarter' => $this->getRevenueByPeriod('quarter', $userFilter),
            'this_year' => $this->getRevenueByPeriod('year', $userFilter),
            'monthly_trend' => $this->getMonthlyRevenueTrend(12, $userFilter)
        ];
        
        return $stats;
    }
    
    /**
     * Get new records this month
     */
    private function getNewThisMonth($table, $filter = '') {
        $stmt = $this->db->query("
            SELECT COUNT(*) as count FROM {$table} 
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
            {$filter}
        ");
        return $stmt->fetch()['count'];
    }
    
    /**
     * Get count by status
     */
    private function getCountByStatus($table, $status, $filter = '') {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM {$table} WHERE status = '{$status}' {$filter}");
        return $stmt->fetch()['count'];
    }
    
    /**
     * Get grouped counts
     */
    private function getGroupedCounts($table, $column, $filter = '', $includeSum = false) {
        $sumColumn = $includeSum ? ", SUM(value) as total_value" : "";
        $stmt = $this->db->query("
            SELECT {$column} as name, COUNT(*) as count {$sumColumn}
            FROM {$table} 
            WHERE 1=1 {$filter}
            GROUP BY {$column}
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Calculate win rate
     */
    private function calculateWinRate($filter = '') {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM deals WHERE stage = 'won' {$filter}");
        $won = $stmt->fetch()['count'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM deals WHERE stage IN ('won', 'lost') {$filter}");
        $total = $stmt->fetch()['count'];
        
        return $total > 0 ? round(($won / $total) * 100, 2) : 0;
    }
    
    /**
     * Get average deal value
     */
    private function getAvgDealValue($filter = '') {
        $stmt = $this->db->query("SELECT AVG(value) as avg FROM deals WHERE 1=1 {$filter}");
        return round($stmt->fetch()['avg'] ?? 0, 2);
    }
    
    /**
     * Get revenue by period
     */
    private function getRevenueByPeriod($period, $filter = '') {
        $dateFilter = match($period) {
            'month' => "MONTH(actual_close_date) = MONTH(CURRENT_DATE()) AND YEAR(actual_close_date) = YEAR(CURRENT_DATE())",
            'quarter' => "QUARTER(actual_close_date) = QUARTER(CURRENT_DATE()) AND YEAR(actual_close_date) = YEAR(CURRENT_DATE())",
            'year' => "YEAR(actual_close_date) = YEAR(CURRENT_DATE())",
            default => "1=1"
        };
        
        $stmt = $this->db->query("
            SELECT SUM(value) as total 
            FROM deals 
            WHERE stage = 'won' AND {$dateFilter} {$filter}
        ");
        
        return $stmt->fetch()['total'] ?? 0;
    }
    
    /**
     * Get monthly revenue trend
     */
    private function getMonthlyRevenueTrend($months = 12, $filter = '') {
        $stmt = $this->db->query("
            SELECT 
                DATE_FORMAT(actual_close_date, '%Y-%m') as month,
                DATE_FORMAT(actual_close_date, '%b %Y') as month_label,
                SUM(value) as revenue,
                COUNT(*) as deals_count
            FROM deals 
            WHERE stage = 'won' 
            AND actual_close_date >= DATE_SUB(CURRENT_DATE(), INTERVAL {$months} MONTH)
            {$filter}
            GROUP BY month
            ORDER BY month ASC
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get upcoming items for dashboard
     */
    public function getUpcomingItems($userId = null, $limit = 5) {
        $userFilter = $userId ? "AND assigned_to = {$userId}" : "";
        
        $items = [];
        
        // Upcoming tasks
        $stmt = $this->db->query("
            SELECT 'task' as type, t.id, t.title as name, t.due_date, t.priority, t.status,
                   u.full_name as assigned_to_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.status != 'completed' AND t.due_date >= NOW()
            {$userFilter}
            ORDER BY t.due_date ASC
            LIMIT {$limit}
        ");
        $items['tasks'] = $stmt->fetchAll();
        
        // Upcoming deals (closing soon)
        $stmt = $this->db->query("
            SELECT 'deal' as type, d.id, d.title as name, d.expected_close_date as due_date,
                   d.stage as priority, d.value,
                   c.full_name as customer_name
            FROM deals d
            LEFT JOIN customers c ON d.customer_id = c.id
            WHERE d.stage NOT IN ('won', 'lost') 
            AND d.expected_close_date >= CURDATE()
            {$userFilter}
            ORDER BY d.expected_close_date ASC
            LIMIT {$limit}
        ");
        $items['deals'] = $stmt->fetchAll();
        
        return $items;
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($userId = null, $days = 30) {
        $userFilter = $userId ? "AND performed_by = {$userId}" : "";
        $assignedFilter = $userId ? "AND assigned_to = {$userId}" : "";
        
        $metrics = [];
        
        // Activities count
        $stmt = $this->db->query("
            SELECT activity_type, COUNT(*) as count
            FROM activities
            WHERE performed_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            {$userFilter}
            GROUP BY activity_type
        ");
        $metrics['activities'] = $stmt->fetchAll();
        
        // Deals won
        $stmt = $this->db->query("
            SELECT COUNT(*) as count, SUM(value) as value
            FROM deals
            WHERE stage = 'won'
            AND actual_close_date >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            {$assignedFilter}
        ");
        $metrics['deals_won'] = $stmt->fetch();
        
        // Deals lost
        $stmt = $this->db->query("
            SELECT COUNT(*) as count, SUM(value) as value
            FROM deals
            WHERE stage = 'lost'
            AND actual_close_date >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            {$assignedFilter}
        ");
        $metrics['deals_lost'] = $stmt->fetch();
        
        // New leads
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM leads
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            {$assignedFilter}
        ");
        $metrics['new_leads'] = $stmt->fetch()['count'];
        
        // New customers
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM customers
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
            {$userFilter}
        ");
        $metrics['new_customers'] = $stmt->fetch()['count'];
        
        return $metrics;
    }
    
    /**
     * Get comparison data (this month vs last month)
     */
    public function getComparisonData() {
        $comparison = [];
        
        // Revenue comparison
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN MONTH(actual_close_date) = MONTH(CURRENT_DATE()) AND YEAR(actual_close_date) = YEAR(CURRENT_DATE()) THEN value ELSE 0 END) as this_month,
                SUM(CASE WHEN MONTH(actual_close_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(actual_close_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) THEN value ELSE 0 END) as last_month
            FROM deals
            WHERE stage = 'won'
        ");
        $comparison['revenue'] = $stmt->fetch();
        
        // New customers comparison
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) THEN 1 ELSE 0 END) as last_month
            FROM customers
        ");
        $comparison['customers'] = $stmt->fetch();
        
        // New leads comparison
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) THEN 1 ELSE 0 END) as last_month
            FROM leads
        ");
        $comparison['leads'] = $stmt->fetch();
        
        // Calculate percentage changes
        foreach ($comparison as $key => $values) {
            $thisMonth = $values['this_month'] ?? 0;
            $lastMonth = $values['last_month'] ?? 0;
            
            if ($lastMonth > 0) {
                $comparison[$key]['change_percent'] = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
            } else {
                $comparison[$key]['change_percent'] = $thisMonth > 0 ? 100 : 0;
            }
        }
        
        return $comparison;
    }
}
