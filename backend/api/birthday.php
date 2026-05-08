<?php
/**
 * Birthday API
 * Check for customer/lead birthdays and generate email links
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Lead.php';

$method = $_SERVER['REQUEST_METHOD'];

// Require authentication
$user = authenticate();

switch ($method) {
    case 'GET':
        // Get today's birthdays
        $today = date('m-d');
        $db = getDB();
        
        // Check customers with birthdays today
        $stmt = $db->query("
            SELECT id, full_name, email, dob, 'customer' as type, customer_code as code
            FROM customers 
            WHERE email IS NOT NULL 
            AND DATE_FORMAT(dob, '%m-%d') = '$today'
            AND status = 'active'
        ");
        $customers = $stmt->fetchAll();
        
        // Check leads with birthdays today
        $stmt = $db->query("
            SELECT id, full_name, email, dob, 'lead' as type, lead_code as code
            FROM leads 
            WHERE email IS NOT NULL 
            AND DATE_FORMAT(dob, '%m-%d') = '$today'
            AND status != 'converted'
        ");
        $leads = $stmt->fetchAll();
        
        $birthdays = array_merge($customers, $leads);
        
        // Add Gmail compose links
        foreach ($birthdays as &$person) {
            $person['gmail_url'] = generateBirthdayEmailUrl($person);
        }
        
        jsonSuccess([
            'today' => date('Y-m-d'),
            'count' => count($birthdays),
            'birthdays' => $birthdays
        ]);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['email']) || empty($data['name'])) {
            jsonError('Email and name are required');
        }
        
        $gmailUrl = generateBirthdayEmailUrl([
            'full_name' => $data['name'],
            'email' => $data['email']
        ]);
        
        jsonSuccess([
            'gmail_url' => $gmailUrl
        ]);
        break;
        
    default:
        jsonError('Method not allowed', 405);
}

/**
 * Generate Gmail compose URL for birthday email
 */
function generateBirthdayEmailUrl($person) {
    $name = $person['full_name'];
    $email = $person['email'];
    
    $subject = 'Chúc mừng sinh nhật ' . $name;
    $body = "Chào {$name},\n\n";
    $body .= "Chúc mừng sinh nhật bạn! 🎉\n\n";
    $body .= "Nhân dịp sinh nhật, công ty chúng tôi xin gửi đến bạn những lời chúc tốt đẹp nhất. ";
    $body .= "Chúc bạn luôn mạnh khỏe, hạnh phúc và thành công trong cuộc sống và công việc!\n\n";
    $body .= "Trân trọng,\n";
    $body .= "Đội ngũ chăm sóc khách hàng";
    
    return 'https://mail.google.com/mail/?view=cm&to=' . urlencode($email) . 
           '&su=' . urlencode($subject) . 
           '&body=' . urlencode($body);
}
