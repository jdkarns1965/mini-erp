<?php
/**
 * API: Get all businesses (customers and suppliers)
 * Returns unified business data for the contact management interface
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../src/classes/Auth.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $auth = new Auth($db);
    
    // Check if user is authenticated via session
    if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
        // Try to authenticate normally
        try {
            $auth->requireAuth();
            $current_user = $auth->getCurrentUser();
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required'
            ]);
            exit;
        }
    } else {
        // Get user info from session
        $current_user = $auth->getCurrentUser();
    }
    
    $pdo = $db->connect();
    
    // Get all customers and suppliers in a unified query
    $query = "
        SELECT 
            'customer' as source_type,
            c.id,
            c.customer_code as code,
            c.customer_name as name,
            c.contact_person,
            c.contact_title,
            c.email,
            c.phone,
            c.phone_ext,
            c.address_line1,
            c.address_line2,
            c.city,
            c.state,
            c.zip_code,
            c.country,
            c.payment_terms,
            c.credit_limit,
            c.notes,
            c.is_active,
            c.created_at,
            c.updated_at,
            TRUE as is_customer,
            FALSE as is_supplier,
            CONCAT_WS(', ', c.city, c.state) as location,
            
            -- Contact and email counts
            COALESCE(contact_counts.contact_count, 0) as contact_count,
            COALESCE(email_counts.email_count, 0) as email_count,
            
            -- Primary contact info
            pc.full_name as primary_contact,
            pc.job_title as primary_contact_title,
            pc.email as primary_contact_email,
            pc.phone as primary_contact_phone
            
        FROM customers c
        
        LEFT JOIN (
            SELECT customer_id, COUNT(*) as contact_count
            FROM customer_contacts cc
            JOIN contacts ct ON cc.contact_id = ct.id
            WHERE ct.is_active = TRUE
            GROUP BY customer_id
        ) contact_counts ON c.id = contact_counts.customer_id
        
        LEFT JOIN (
            SELECT customer_id, COUNT(*) as email_count
            FROM customer_emails
            WHERE is_active = TRUE
            GROUP BY customer_id
        ) email_counts ON c.id = email_counts.customer_id
        
        LEFT JOIN customer_contacts cc_primary ON c.id = cc_primary.customer_id AND cc_primary.is_primary = TRUE
        LEFT JOIN contacts pc ON cc_primary.contact_id = pc.id
        
        UNION ALL
        
        SELECT 
            'supplier' as source_type,
            s.id,
            s.supplier_code as code,
            s.supplier_name as name,
            s.contact_person,
            s.contact_title,
            s.email,
            s.phone,
            s.phone_ext,
            s.address_line1,
            s.address_line2,
            s.city,
            s.state,
            s.zip_code,
            s.country,
            s.payment_terms,
            NULL as credit_limit,
            s.notes,
            s.is_active,
            s.created_at,
            s.updated_at,
            FALSE as is_customer,
            TRUE as is_supplier,
            CONCAT_WS(', ', s.city, s.state) as location,
            
            -- Contact and email counts
            COALESCE(supplier_contact_counts.contact_count, 0) as contact_count,
            COALESCE(supplier_email_counts.email_count, 0) as email_count,
            
            -- Primary contact info (from supplier table)
            s.contact_person as primary_contact,
            s.contact_title as primary_contact_title,
            s.email as primary_contact_email,
            s.phone as primary_contact_phone
            
        FROM suppliers s
        
        LEFT JOIN (
            SELECT supplier_id, COUNT(*) as contact_count
            FROM supplier_contacts sc
            JOIN contacts ct ON sc.contact_id = ct.id
            WHERE ct.is_active = TRUE
            GROUP BY supplier_id
        ) supplier_contact_counts ON s.id = supplier_contact_counts.supplier_id
        
        LEFT JOIN (
            SELECT supplier_id, COUNT(*) as email_count
            FROM supplier_emails
            WHERE is_active = TRUE
            GROUP BY supplier_id
        ) supplier_email_counts ON s.id = supplier_email_counts.supplier_id
        
        ORDER BY is_active DESC, name ASC
    ";
    
    $businesses = $pdo->query($query)->fetchAll();
    
    // Format the data for the frontend
    $formattedBusinesses = array_map(function($business) {
        return [
            'id' => $business['source_type'] . '_' . $business['id'], // Unique ID across both tables
            'source_type' => $business['source_type'],
            'source_id' => $business['id'],
            'code' => $business['code'],
            'name' => $business['name'],
            'contact_person' => $business['contact_person'],
            'contact_title' => $business['contact_title'],
            'email' => $business['email'],
            'phone' => $business['phone'],
            'phone_ext' => $business['phone_ext'],
            'location' => $business['location'] ?: null,
            'full_address' => trim(implode(' ', array_filter([
                $business['address_line1'],
                $business['address_line2'],
                $business['city'],
                $business['state'],
                $business['zip_code'],
                $business['country']
            ]))),
            'payment_terms' => $business['payment_terms'],
            'credit_limit' => $business['credit_limit'],
            'notes' => $business['notes'],
            'is_active' => (bool)$business['is_active'],
            'is_customer' => (bool)$business['is_customer'],
            'is_supplier' => (bool)$business['is_supplier'],
            'contact_count' => (int)$business['contact_count'],
            'email_count' => (int)$business['email_count'],
            'primary_contact' => $business['primary_contact'],
            'primary_contact_title' => $business['primary_contact_title'],
            'primary_contact_email' => $business['primary_contact_email'],
            'primary_contact_phone' => $business['primary_contact_phone'],
            'created_at' => $business['created_at'],
            'updated_at' => $business['updated_at']
        ];
    }, $businesses);
    
    echo json_encode([
        'success' => true,
        'businesses' => $formattedBusinesses,
        'total' => count($formattedBusinesses)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load businesses: ' . $e->getMessage()
    ]);
}