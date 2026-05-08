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
        jsonSuccess(getDealsSummary());
        break;
        
    case 'conversion':
        jsonSuccess(getConversionStats());
        break;
        
    case 'performance':
        $year = $_GET['year'] ?? date('Y');
        jsonSuccess(getSalesPerformance($year));
        break;
        
    case 'sources':
        jsonSuccess(getLeadSources());
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
function getDealsSummary() {
    $db = getDB();
    
    $sql = "
        SELECT 
            stage,
            COUNT(*) as count,
            SUM(value) as total_value,
            AVG(value) as avg_value
        FROM deals
        GROUP BY stage
        ORDER BY FIELD(stage, 'prospect', 'qualification', 'proposal', 'negotiation', 'won', 'lost')
    ";
    
    $stmt = $db->query($sql);
    $byStage = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Totals
    $sql = "
        SELECT 
            COUNT(*) as total_deals,
            SUM(CASE WHEN stage = 'won' THEN value ELSE 0 END) as total_won,
            SUM(CASE WHEN stage != 'won' AND stage != 'lost' THEN value ELSE 0 END) as total_pipeline,
            SUM(CASE WHEN stage = 'lost' THEN value ELSE 0 END) as total_lost
        FROM deals
    ";
    $stmt = $db->query($sql);
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
            COUNT(CASE WHEN d.stage = 'lost' THEN 1 END) as lost_count
        FROM users u
        LEFT JOIN deals d ON u.id = d.assigned_to AND YEAR(d.expected_close_date) = ?
        WHERE u.role IN ('sales', 'manager')
        GROUP BY u.id, u.full_name, u.avatar
        ORDER BY won_value DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$year]);
    return ['year' => $year, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

/**
 * Get lead sources breakdown
 */
function getLeadSources() {
    $db = getDB();
    
    // Leads by source
    $sql = "
        SELECT 
            source,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted
        FROM leads
        GROUP BY source
        ORDER BY count DESC
    ";
    $stmt = $db->query($sql);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Deals by source
    $sql = "
        SELECT 
            source,
            COUNT(*) as count,
            SUM(CASE WHEN stage = 'won' THEN value ELSE 0 END) as won_value
        FROM deals
        GROUP BY source
        ORDER BY won_value DESC
    ";
    $stmt = $db->query($sql);
    $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['leads' => $leads, 'deals' => $deals];
}
