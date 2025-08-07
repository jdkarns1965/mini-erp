<?php
/**
 * API: Get business statistics
 * Returns counts for dashboard widgets
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../src/classes/Auth.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $auth = new Auth($db);
    
    // Require authentication
    $auth->requireAuth();
    $current_user = $auth->getCurrentUser();
    
    $pdo = $db->connect();
    
    // Get comprehensive stats
    $stats = [];
    
    // Customer stats
    $customer_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_customers,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_customers,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_customers
        FROM customers
    ")->fetch();
    
    // Supplier stats
    $supplier_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_suppliers,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_suppliers,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_suppliers
        FROM suppliers
    ")->fetch();
    
    // Contact stats
    $contact_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_contacts,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_contacts
        FROM contacts
    ")->fetch();
    
    // Email stats
    $email_stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM customer_emails WHERE is_active = 1) +
            (SELECT COUNT(*) FROM supplier_emails WHERE is_active = 1) as total_emails
    ")->fetch();
    
    // Recent activity (businesses created in last 30 days)
    $recent_stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) +
            (SELECT COUNT(*) FROM suppliers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_businesses
    ")->fetch();
    
    $stats = [
        'total_businesses' => $customer_stats['total_customers'] + $supplier_stats['total_suppliers'],
        'total_customers' => (int)$customer_stats['total_customers'],
        'active_customers' => (int)$customer_stats['active_customers'],
        'inactive_customers' => (int)$customer_stats['inactive_customers'],
        'total_suppliers' => (int)$supplier_stats['total_suppliers'],
        'active_suppliers' => (int)$supplier_stats['active_suppliers'],
        'inactive_suppliers' => (int)$supplier_stats['inactive_suppliers'],
        'total_contacts' => (int)$contact_stats['total_contacts'],
        'active_contacts' => (int)$contact_stats['active_contacts'],
        'total_emails' => (int)$email_stats['total_emails'],
        'recent_businesses' => (int)$recent_stats['recent_businesses']
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load statistics: ' . $e->getMessage()
    ]);
}