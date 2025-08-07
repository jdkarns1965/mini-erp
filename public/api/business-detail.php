<?php
/**
 * API: Get detailed business information
 * Returns comprehensive business data including contacts and emails
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../src/classes/Auth.php';
require_once '../../src/classes/Contact.php';
require_once '../../src/classes/Email.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Business ID required']);
    exit;
}

try {
    $db = new Database();
    $auth = new Auth($db);
    $contact = new Contact($db);
    $email = new Email($db);
    
    // Get current user
    $current_user = $auth->getCurrentUser();
    $pdo = $db->connect();
    
    // Parse the composite ID (e.g., "customer_1" or "supplier_3")
    $business_id = $_GET['id'];
    $parts = explode('_', $business_id);
    
    if (count($parts) !== 2) {
        throw new Exception('Invalid business ID format');
    }
    
    $source_type = $parts[0];
    $source_id = (int)$parts[1];
    
    if (!in_array($source_type, ['customer', 'supplier'])) {
        throw new Exception('Invalid business type');
    }
    
    // Get business information
    $business = null;
    $contacts = [];
    $emails = [];
    
    if ($source_type === 'customer') {
        // Get customer data
        $stmt = $pdo->prepare("
            SELECT c.*, u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM products WHERE customer_id = c.id AND is_active = 1) as product_count
            FROM customers c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$source_id]);
        $business = $stmt->fetch();
        
        if ($business) {
            // Get customer contacts
            $contacts = $contact->getCustomerContacts($source_id);
            
            // Get customer emails
            $emails = $email->getCustomerEmails($source_id);
        }
    } else {
        // Get supplier data
        $stmt = $pdo->prepare("
            SELECT s.*, u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM materials WHERE supplier_id = s.id AND is_active = 1) as material_count
            FROM suppliers s
            LEFT JOIN users u ON s.created_by = u.id
            WHERE s.id = ?
        ");
        $stmt->execute([$source_id]);
        $business = $stmt->fetch();
        
        if ($business) {
            // Get supplier contacts
            $contacts = $contact->getSupplierContacts($source_id);
            
            // Get supplier emails
            $emails = $email->getSupplierEmails($source_id);
        }
    }
    
    if (!$business) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Business not found']);
        exit;
    }
    
    // Format business data
    $formatted_business = [
        'id' => $business_id,
        'source_type' => $source_type,
        'source_id' => $source_id,
        'code' => $source_type === 'customer' ? $business['customer_code'] : $business['supplier_code'],
        'name' => $source_type === 'customer' ? $business['customer_name'] : $business['supplier_name'],
        'is_customer' => $source_type === 'customer',
        'is_supplier' => $source_type === 'supplier',
        'is_active' => (bool)$business['is_active'],
        
        // Contact information
        'contact_person' => $business['contact_person'],
        'contact_title' => $business['contact_title'] ?? null,
        'email' => $business['email'],
        'phone' => $business['phone'],
        'phone_ext' => $business['phone_ext'],
        
        // Address information
        'address_line1' => $business['address_line1'],
        'address_line2' => $business['address_line2'],
        'city' => $business['city'],
        'state' => $business['state'],
        'zip_code' => $business['zip_code'],
        'country' => $business['country'],
        'full_address' => trim(implode(' ', array_filter([
            $business['address_line1'],
            $business['address_line2'],
            $business['city'],
            $business['state'],
            $business['zip_code'],
            $business['country']
        ]))),
        
        // Business information
        'payment_terms' => $business['payment_terms'],
        'notes' => $business['notes'],
        'created_at' => $business['created_at'],
        'created_by_name' => $business['created_by_name'],
        
        // Type-specific fields
        'credit_limit' => $source_type === 'customer' ? $business['credit_limit'] : null,
        'lead_time_days' => $source_type === 'supplier' ? $business['lead_time_days'] : null,
        'product_count' => $source_type === 'customer' ? ($business['product_count'] ?? 0) : null,
        'material_count' => $source_type === 'supplier' ? ($business['material_count'] ?? 0) : null,
        
        // Related data
        'contacts' => $contacts,
        'emails' => $emails,
        'contact_count' => count($contacts),
        'email_count' => count($emails)
    ];
    
    echo json_encode([
        'success' => true,
        'business' => $formatted_business
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load business details: ' . $e->getMessage()
    ]);
}
?>