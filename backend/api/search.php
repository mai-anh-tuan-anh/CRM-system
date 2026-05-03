<?php
/**
 * Search API
 * Global search across entities
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = authenticate();
$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all';
$limit = intval($_GET['limit'] ?? 10);

if (empty($query) || strlen($query) < 2) {
    jsonError('Search query must be at least 2 characters');
}

$db = getDB();
$results = [];
$search = "%{$query}%";

// User filter for non-admin
$userFilter = ($user['role'] !== 'admin' && $user['role'] !== 'manager') ? "AND assigned_to = {$user['id']}" : '';

// Search customers
if ($type === 'all' || $type === 'customers') {
    $stmt = $db->prepare("
        SELECT id, full_name, email, phone, company_name, 'customer' as type
        FROM customers
        WHERE (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company_name LIKE ?)
        {$userFilter}
        LIMIT ?
    ");
    $stmt->execute([$search, $search, $search, $search, $limit]);
    $results = array_merge($results, $stmt->fetchAll());
}

// Search leads
if ($type === 'all' || $type === 'leads') {
    $stmt = $db->prepare("
        SELECT id, full_name, email, phone, company_name, status, 'lead' as type
        FROM leads
        WHERE (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company_name LIKE ?)
        AND status != 'converted'
        {$userFilter}
        LIMIT ?
    ");
    $stmt->execute([$search, $search, $search, $search, $limit]);
    $results = array_merge($results, $stmt->fetchAll());
}

// Search deals
if ($type === 'all' || $type === 'deals') {
    $stmt = $db->prepare("
        SELECT d.id, d.title, d.value, d.stage, c.full_name as customer_name, 'deal' as type
        FROM deals d
        LEFT JOIN customers c ON d.customer_id = c.id
        WHERE (d.title LIKE ? OR c.full_name LIKE ? OR c.company_name LIKE ?)
        {$userFilter}
        LIMIT ?
    ");
    $stmt->execute([$search, $search, $search, $limit]);
    $results = array_merge($results, $stmt->fetchAll());
}

// Search tasks
if ($type === 'all' || $type === 'tasks') {
    $stmt = $db->prepare("
        SELECT id, title, status, priority, due_date, 'task' as type
        FROM tasks
        WHERE title LIKE ?
        {$userFilter}
        LIMIT ?
    ");
    $stmt->execute([$search, $limit]);
    $results = array_merge($results, $stmt->fetchAll());
}

// Limit total results
$results = array_slice($results, 0, $limit * 2);

jsonSuccess([
    'query' => $query,
    'count' => count($results),
    'results' => $results
]);
