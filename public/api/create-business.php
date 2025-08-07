<?php
/**
 * API: Create new business (customer/supplier)
 * Handles unified business creation with contacts and emails
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../src/classes/Auth.php';
require_once '../../src/classes/Contact.php';
require_once '../../src/classes/Email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = new Database();
    $auth = new Auth($db);
    $contact = new Contact($db);
    $email = new Email($db);
    
    // Get current user
    $current_user = $auth->getCurrentUser();
    
    // Check permissions
    if (!$auth->hasRole(['admin', 'supervisor', 'material_handler'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit;
    }
    
    $pdo = $db->connect();
    $pdo->beginTransaction();
    
    // Validate required fields
    if (empty($_POST['business_name'])) {
        throw new Exception('Business name is required');
    }
    
    $business_type = $_POST['business_type'] ?? 'customer';
    if (!in_array($business_type, ['customer', 'supplier', 'both'])) {
        throw new Exception('Invalid business type');
    }
    
    $business_name = trim($_POST['business_name']);
    $created_ids = [];
    
    // Create customer record if needed
    if ($business_type === 'customer' || $business_type === 'both') {
        // Generate customer code
        $latest_customer = $pdo->query("SELECT customer_code FROM customers WHERE customer_code LIKE 'CUST%' ORDER BY customer_code DESC LIMIT 1")->fetch();
        if ($latest_customer) {
            $num = (int)substr($latest_customer['customer_code'], 4) + 1;
        } else {
            $num = 1;
        }
        $customer_code = 'CUST' . str_pad($num, 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("
            INSERT INTO customers (customer_code, customer_name, contact_person, contact_title, email, phone, phone_ext,
                                 address_line1, address_line2, city, state, zip_code, country,
                                 payment_terms, credit_limit, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $customer_code,
            $business_name,
            $_POST['contact_first_name'] ? trim($_POST['contact_first_name'] . ' ' . ($_POST['contact_last_name'] ?? '')) : null,
            $_POST['contact_title'] ?: null,
            $_POST['primary_email'] ?: null,
            $_POST['primary_phone'] ?: null,
            $_POST['phone_ext'] ?: null,
            $_POST['address_line1'] ?: null,
            $_POST['address_line2'] ?: null,
            $_POST['city'] ?: null,
            $_POST['state'] ?: null,
            $_POST['zip_code'] ?: null,
            $_POST['country'] ?: 'USA',
            $_POST['payment_terms'] ?: null,
            floatval($_POST['credit_limit'] ?? 0),
            $_POST['notes'] ?: null,
            $current_user['id']
        ]);
        
        $customer_id = $pdo->lastInsertId();
        $created_ids['customer'] = $customer_id;
        
        // Add primary email to customer emails if provided
        if (!empty($_POST['primary_email'])) {
            $email_type = $_POST['email_type'] ?: 'contact';
            $email->addCustomerEmail($customer_id, $_POST['primary_email'], $email_type, 'Primary contact email', $current_user['id']);
        }
        
        // Add additional emails if provided
        if (!empty($_POST['additional_emails'])) {
            $additional_emails = array_filter(array_map('trim', explode("\n", $_POST['additional_emails'])));
            foreach ($additional_emails as $add_email) {
                if (Email::validateEmail($add_email) && !$email->customerEmailExists($customer_id, $add_email)) {
                    $email->addCustomerEmail($customer_id, $add_email, 'department', 'Additional email', $current_user['id']);
                }
            }
        }
        
        // Create contact record if contact details provided
        if (!empty($_POST['contact_first_name'])) {
            $contact_data = [
                'first_name' => $_POST['contact_first_name'],
                'last_name' => $_POST['contact_last_name'] ?: '',
                'email' => $_POST['primary_email'] ?: null,
                'phone' => $_POST['primary_phone'] ?: null,
                'phone_ext' => $_POST['phone_ext'] ?: null,
                'mobile_phone' => null,
                'job_title' => $_POST['contact_title'] ?: null,
                'department' => $_POST['contact_department'] ?: null,
                'notes' => null
            ];
            
            $contact_id = $contact->createContact($contact_data, $current_user['id']);
            $contact->linkToCustomer($contact_id, $customer_id, 'Primary', true);
        }
    }
    
    // Create supplier record if needed
    if ($business_type === 'supplier' || $business_type === 'both') {
        // Generate supplier code
        $latest_supplier = $pdo->query("SELECT supplier_code FROM suppliers WHERE supplier_code LIKE 'SUPP%' ORDER BY supplier_code DESC LIMIT 1")->fetch();
        if ($latest_supplier) {
            $num = (int)substr($latest_supplier['supplier_code'], 4) + 1;
        } else {
            $num = 1;
        }
        $supplier_code = 'SUPP' . str_pad($num, 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("
            INSERT INTO suppliers (supplier_code, supplier_name, contact_person, contact_title, email, phone, phone_ext,
                                 address_line1, address_line2, city, state, zip_code, country,
                                 payment_terms, lead_time_days, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $supplier_code,
            $business_name,
            $_POST['contact_first_name'] ? trim($_POST['contact_first_name'] . ' ' . ($_POST['contact_last_name'] ?? '')) : null,
            $_POST['contact_title'] ?: null,
            $_POST['primary_email'] ?: null,
            $_POST['primary_phone'] ?: null,
            $_POST['phone_ext'] ?: null,
            $_POST['address_line1'] ?: null,
            $_POST['address_line2'] ?: null,
            $_POST['city'] ?: null,
            $_POST['state'] ?: null,
            $_POST['zip_code'] ?: null,
            $_POST['country'] ?: 'USA',
            $_POST['payment_terms'] ?: null,
            intval($_POST['lead_time_days'] ?? 0),
            $_POST['notes'] ?: null,
            $current_user['id']
        ]);
        
        $supplier_id = $pdo->lastInsertId();
        $created_ids['supplier'] = $supplier_id;
        
        // Add primary email to supplier emails if provided
        if (!empty($_POST['primary_email'])) {
            $email_type = $_POST['email_type'] ?: 'contact';
            $email->addSupplierEmail($supplier_id, $_POST['primary_email'], $email_type, 'Primary contact email', $current_user['id']);
        }
        
        // Add additional emails if provided
        if (!empty($_POST['additional_emails'])) {
            $additional_emails = array_filter(array_map('trim', explode("\n", $_POST['additional_emails'])));
            foreach ($additional_emails as $add_email) {
                if (Email::validateEmail($add_email) && !$email->supplierEmailExists($supplier_id, $add_email)) {
                    $email->addSupplierEmail($supplier_id, $add_email, 'department', 'Additional email', $current_user['id']);
                }
            }
        }
        
        // Create contact record if contact details provided (for suppliers, we can also use the Contact class)
        if (!empty($_POST['contact_first_name'])) {
            $contact_data = [
                'first_name' => $_POST['contact_first_name'],
                'last_name' => $_POST['contact_last_name'] ?: '',
                'email' => $_POST['primary_email'] ?: null,
                'phone' => $_POST['primary_phone'] ?: null,
                'phone_ext' => $_POST['phone_ext'] ?: null,
                'mobile_phone' => null,
                'job_title' => $_POST['contact_title'] ?: null,
                'department' => $_POST['contact_department'] ?: null,
                'notes' => null
            ];
            
            $contact_id = $contact->createContact($contact_data, $current_user['id']);
            $contact->linkToSupplier($contact_id, $supplier_id, 'Primary', true);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Business created successfully',
        'created_ids' => $created_ids,
        'business_type' => $business_type
    ]);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}