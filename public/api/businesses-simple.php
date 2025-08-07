<?php
/**
 * Simplified Businesses API - Working Version
 */
session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../src/classes/Auth.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $auth = new Auth($db);
    
    // Get current user (this will handle authentication)
    $current_user = $auth->getCurrentUser();
    
    $pdo = $db->connect();
    
    // Simple query to get all businesses
    $customers = $pdo->query("
        SELECT 'customer' as source_type, id, customer_code as code, customer_name as name, 
               is_active, contact_person, email, phone, city, state
        FROM customers ORDER BY customer_name
    ")->fetchAll();
    
    $suppliers = $pdo->query("
        SELECT 'supplier' as source_type, id, supplier_code as code, supplier_name as name, 
               is_active, contact_person, email, phone, city, state  
        FROM suppliers ORDER BY supplier_name
    ")->fetchAll();
    
    $all_businesses = array_merge($customers, $suppliers);
    
    // Format for frontend
    $formatted = array_map(function($business) {
        $location = array_filter([$business['city'], $business['state']]);
        return [
            'id' => $business['source_type'] . '_' . $business['id'],
            'source_type' => $business['source_type'],
            'source_id' => $business['id'],
            'code' => $business['code'],
            'name' => $business['name'],
            'contact_person' => $business['contact_person'],
            'email' => $business['email'],
            'phone' => $business['phone'],
            'location' => implode(', ', $location) ?: null,
            'is_active' => (bool)$business['is_active'],
            'is_customer' => $business['source_type'] === 'customer',
            'is_supplier' => $business['source_type'] === 'supplier',
            'contact_count' => 0,
            'email_count' => 0,
            'primary_contact' => $business['contact_person'],
            'primary_contact_email' => $business['email'],
            'primary_contact_phone' => $business['phone']
        ];
    }, $all_businesses);
    
    echo json_encode([
        'success' => true,
        'businesses' => $formatted,
        'total' => count($formatted)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load businesses: ' . $e->getMessage()
    ]);
}
?>