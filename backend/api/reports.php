<?php
/**
 * Reports API
 * Data for reports and analytics
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = authenticate();

// Check admin/manager role for reports
if ($user['role'] === 'sales') {
    jsonError('Không có quyền truy cập báo cáo', 403);
}

$action = $_GET['action'] ?? 'overview';

switch ($action) {
    case 'revenue':
        $year = $_GET['year'] ?? date('Y');
        jsonSuccess(getRevenueByMonth($year));
        break;
        
    case 'revenue_yearly':
        $startYear = $_GET['start_year'] ?? (date('Y') - 4);
        $endYear = $_GET['end_year'] ?? date('Y');
        jsonSuccess(getRevenueByYear($startYear, $endYear));
        break;
        
    case 'deals_summary':
        $year = $_GET['year'] ?? null;
        jsonSuccess(getDealsSummary($year));
        break;
        
    case 'conversion':
        jsonSuccess(getConversionStats());
        break;
        
    case 'performance':
        $year = $_GET['year'] ?? date('Y');
        jsonSuccess(getSalesPerformance($year));
        break;
        
    case 'sources':
        $year = $_GET['year'] ?? null;
        jsonSuccess(getLeadSources($year));
        break;
        
    case 'win_rate_by_source':
        $year = $_GET['year'] ?? null;
        jsonSuccess(getWinRateBySource($year));
        break;
        
    case 'customers_by_industry':
        $year = $_GET['year'] ?? null;
        jsonSuccess(getCustomersByIndustry($year));
        break;
        
    default:
        jsonSuccess([
            'revenue' => getRevenueByMonth(date('Y')),
            'deals_summary' => getDealsSummary(),
            'conversion' => getConversionStats()
        ]);
}

/**
 * Get revenue by month for a year
 */
function getRevenueByMonth($year) {
    $db = getDB();
    
    $sql = "
        SELECT 
            MONTH(COALESCE(d.actual_close_date, d.expected_close_date)) as month,
            SUM(CASE WHEN d.stage = 'won' THEN d.value ELSE 0 END) as won_revenue,
            SUM(CASE WHEN d.stage != 'won' AND d.stage != 'lost' THEN d.value ELSE 0 END) as pipeline_value,
            COUNT(CASE WHEN d.stage = 'won' THEN 1 END) as won_count,
            COUNT(CASE WHEN d.stage = 'lost' THEN 1 END) as lost_count,
            COUNT(CASE WHEN d.stage != 'won' AND d.stage != 'lost' THEN 1 END) as active_count
        FROM deals d
        INNER JOIN customers c ON d.customer_id = c.id
        WHERE YEAR(COALESCE(d.actual_close_date, d.expected_close_date)) = ?
            AND c.status = 'active'
        GROUP BY MONTH(COALESCE(d.actual_close_date, d.expected_close_date))
        ORDER BY month
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$year]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fill missing months with 0
    $result = [];
    for ($i = 1; $i <= 12; $i++) {
        $found = array_filter($data, fn($d) => $d['month'] == $i);
        if ($found) {
            $result[] = array_values($found)[0];
        } else {
            $result[] = [
                'month' => $i,
                'won_revenue' => 0,
                'pipeline_value' => 0,
                'won_count' => 0,
                'lost_count' => 0,
                'active_count' => 0
            ];
        }
    }
    
    return ['year' => $year, 'data' => $result];
}

/**
 * Get revenue by year
 */
function getRevenueByYear($startYear, $endYear) {
    $db = getDB();
    
    $sql = "
        SELECT 
            YEAR(COALESCE(d.actual_close_date, d.expected_close_date)) as year,
            SUM(CASE WHEN d.stage = 'won' THEN d.value ELSE 0 END) as won_revenue,
            SUM(CASE WHEN d.stage != 'won' AND d.stage != 'lost' THEN d.value ELSE 0 END) as pipeline_value,
            COUNT(*) as total_deals
        FROM deals d
        INNER JOIN customers c ON d.customer_id = c.id
        WHERE YEAR(COALESCE(d.actual_close_date, d.expected_close_date)) BETWEEN ? AND ?
            AND c.status = 'active'
        GROUP BY YEAR(COALESCE(d.actual_close_date, d.expected_close_date))
        ORDER BY year
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$startYear, $endYear]);
    return ['start_year' => $startYear, 'end_year' => $endYear, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

/**
 * Get deals summary by stage
 */
function getDealsSummary($year = null) {
    $db = getDB();
    
    $whereYear = $year ? "WHERE YEAR(COALESCE(actual_close_date, expected_close_date)) = ?" : "";
    $params = $year ? [$year] : [];
    
    $sql = "
        SELECT 
            stage,
            COUNT(*) as count,
            SUM(value) as total_value,
            AVG(value) as avg_value
        FROM deals
        {$whereYear}
        GROUP BY stage
        ORDER BY FIELD(stage, 'prospect', 'qualification', 'proposal', 'negotiation', 'won', 'lost')
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $byStage = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Totals
    $sql = "
        SELECT 
            COUNT(*) as total_deals,
            SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END) as won,
            SUM(CASE WHEN stage = 'won' THEN value ELSE 0 END) as total_won,
            SUM(CASE WHEN stage != 'won' AND stage != 'lost' THEN value ELSE 0 END) as total_pipeline,
            SUM(CASE WHEN stage = 'lost' THEN value ELSE 0 END) as total_lost
        FROM deals
        {$whereYear}
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ['by_stage' => $byStage, 'totals' => $totals];
}

/**
 * Get conversion statistics
 */
function getConversionStats() {
    $db = getDB();
    
    // Leads to Customers conversion
    $sql = "
        SELECT 
            COUNT(*) as total_leads,
            SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads
        FROM leads
    ";
    $stmt = $db->query($sql);
    $leadConversion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Deals win rate
    $sql = "
        SELECT 
            COUNT(*) as total_deals,
            SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END) as won_deals,
            SUM(CASE WHEN stage = 'lost' THEN 1 ELSE 0 END) as lost_deals
        FROM deals
    ";
    $stmt = $db->query($sql);
    $dealConversion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'leads' => [
            'total' => (int)$leadConversion['total_leads'],
            'converted' => (int)$leadConversion['converted_leads'],
            'rate' => $leadConversion['total_leads'] > 0 
                ? round(($leadConversion['converted_leads'] / $leadConversion['total_leads']) * 100, 1)
                : 0
        ],
        'deals' => [
            'total' => (int)$dealConversion['total_deals'],
            'won' => (int)$dealConversion['won_deals'],
            'lost' => (int)$dealConversion['lost_deals'],
            'win_rate' => ($dealConversion['total_deals'] - $dealConversion['lost_deals']) > 0
                ? round(($dealConversion['won_deals'] / ($dealConversion['total_deals'] - $dealConversion['lost_deals'])) * 100, 1)
                : 0
        ]
    ];
}

/**
 * Get sales performance by user
 */
function getSalesPerformance($year) {
    $db = getDB();
    
    $sql = "
        SELECT 
            u.id,
            u.full_name,
            u.avatar,
            COUNT(DISTINCT d.id) as deals_count,
            SUM(CASE WHEN d.stage = 'won' THEN d.value ELSE 0 END) as won_value,
            COUNT(CASE WHEN d.stage = 'won' THEN 1 END) as won_count,
            COUNT(CASE WHEN d.stage = 'lost' THEN 1 END) as lost_count,
            COALESCE(tc.completed_tasks_count, 0) as completed_tasks_count,
            COALESCE(to2.overdue_tasks_count, 0) as overdue_tasks_count
        FROM users u
        LEFT JOIN deals d ON u.id = d.assigned_to AND YEAR(d.expected_close_date) = ?
        LEFT JOIN (
            SELECT
                t.assigned_to,
                COUNT(*) as completed_tasks_count
            FROM tasks t
            WHERE
                t.status = 'completed'
                AND t.completed_at IS NOT NULL
                AND YEAR(t.completed_at) = ?
            GROUP BY t.assigned_to
        ) tc ON tc.assigned_to = u.id
        LEFT JOIN (
            SELECT
                t.assigned_to,
                COUNT(*) as overdue_tasks_count
            FROM tasks t
            WHERE
                t.status != 'completed'
                AND t.due_date IS NOT NULL
                AND t.due_date < NOW()
                AND YEAR(t.due_date) = ?
            GROUP BY t.assigned_to
        ) to2 ON to2.assigned_to = u.id
        WHERE u.role IN ('sales', 'manager')
        GROUP BY
            u.id,
            u.full_name,
            u.avatar,
            tc.completed_tasks_count,
            to2.overdue_tasks_count
        ORDER BY won_value DESC
    ";
    
    $stmt = $db->prepare($sql);
    // Params order must match the placeholders in the SQL above
    $stmt->execute([$year, $year, $year]);
    return ['year' => $year, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

/**
 * Get lead sources breakdown
 */
function getLeadSources($year = null) {
    $db = getDB();
    
    // Leads by source
    $whereYearLeads = $year ? "WHERE YEAR(created_at) = ?" : "";
    $paramsLeads = $year ? [$year] : [];
    
    $sql = "
        SELECT 
            source,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted
        FROM leads
        {$whereYearLeads}
        GROUP BY source
        ORDER BY count DESC
    ";
    $stmt = $year ? $db->prepare($sql) : $db->query($sql);
    if ($year) $stmt->execute($paramsLeads);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Deals by source
    $whereYear = $year ? "AND YEAR(COALESCE(actual_close_date, expected_close_date)) = ?" : "";
    $params = $year ? [$year] : [];
    
    $sql = "
        SELECT 
            source,
            COUNT(*) as count,
            SUM(CASE WHEN stage = 'won' THEN value ELSE 0 END) as won_value
        FROM deals
        WHERE 1=1 {$whereYear}
        GROUP BY source
        ORDER BY won_value DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['leads' => $leads, 'deals' => $deals];
}

/**
 * Get win rate by source
 */
function getWinRateBySource($year = null) {
    $db = getDB();
    
    $whereYear = $year ? "AND YEAR(COALESCE(actual_close_date, expected_close_date)) = ?" : "";
    $params = $year ? [$year] : [];
    
    $sql = "
        SELECT 
            source,
            COALESCE(SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END), 0) as won_deals,
            COALESCE(SUM(CASE WHEN stage = 'lost' THEN 1 ELSE 0 END), 0) as lost_deals,
            COUNT(*) as total_deals,
            COALESCE(
                ROUND(
                    SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END) / 
                    NULLIF(SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END) + SUM(CASE WHEN stage = 'lost' THEN 1 ELSE 0 END), 0) 
                    * 100, 1
                ), 0
            ) as win_rate
        FROM deals
        WHERE 1=1 {$whereYear}
        GROUP BY source
        ORDER BY win_rate DESC, source ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['year' => $year, 'data' => $data];
}

/**
 * Get customers by industry
 */
function getCustomersByIndustry($year = null) {
    $db = getDB();
    
    $whereYear = $year ? "WHERE YEAR(created_at) = ?" : "";
    $params = $year ? [$year] : [];
    
    $sql = "
        SELECT 
            COALESCE(industry, 'Khác') as industry,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
        FROM customers
        {$whereYear}
        GROUP BY industry
        ORDER BY count DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['year' => $year, 'data' => $data];
}