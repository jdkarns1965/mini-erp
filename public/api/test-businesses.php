<?php
/**
 * Test businesses API with simulated authentication
 */
session_start();

// Simulate authentication for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test User';
}

require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $pdo = $db->connect();
    
    // Simple unified query similar to the main API
    $query = "
        SELECT 
            'customer' as source_type,
            c.id,
            c.customer_code as code,
            c.customer_name as name,
            c.is_active,
            TRUE as is_customer,
            FALSE as is_supplier
        FROM customers c
        
        UNION ALL
        
        SELECT 
            'supplier' as source_type,
            s.id,
            s.supplier_code as code,
            s.supplier_name as name,
            s.is_active,
            FALSE as is_customer,
            TRUE as is_supplier
        FROM suppliers s
        
        ORDER BY is_active DESC, name ASC
    ";
    
    $businesses = $pdo->query($query)->fetchAll();
    
    // Format for frontend
    $formattedBusinesses = array_map(function($business) {
        return [
            'id' => $business['source_type'] . '_' . $business['id'],
            'source_type' => $business['source_type'],
            'code' => $business['code'],
            'name' => $business['name'],
            'is_active' => (bool)$business['is_active'],
            'is_customer' => (bool)$business['is_customer'],
            'is_supplier' => (bool)$business['is_supplier'],
            'location' => null,
            'contact_count' => 0,
            'email_count' => 0,
            'primary_contact' => null
        ];
    }, $businesses);
    
    echo json_encode([
        'success' => true,
        'businesses' => $formattedBusinesses,
        'total' => count($formattedBusinesses)
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load businesses: ' . $e->getMessage()
    ]);
}
?>