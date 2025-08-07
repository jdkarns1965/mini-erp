<?php
/**
 * Modern Customer Management System - Outlook Style Interface
 * Professional customer management with modern UI/UX
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';
require_once '../src/classes/Contact.php';
require_once '../src/classes/Email.php';

$db = new Database();
$auth = new Auth($db);
$contact = new Contact($db);
$email = new Email($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Check permissions
$can_manage = $auth->hasRole(['admin', 'supervisor', 'material_handler']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    if (isset($_POST['add_customer'])) {
        try {
            $pdo->beginTransaction();
            
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
                $_POST['customer_name'],
                $_POST['contact_person'] ?: null,
                $_POST['contact_title'] ?: null,
                $_POST['email'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['phone_ext'] ?: null,
                $_POST['address_line1'] ?: null,
                $_POST['address_line2'] ?: null,
                $_POST['city'] ?: null,
                $_POST['state'] ?: null,
                $_POST['zip_code'] ?: null,
                $_POST['country'] ?: 'USA',
                $_POST['payment_terms'] ?: null,
                $_POST['credit_limit'] ?: 0.00,
                $_POST['notes'] ?: null,
                $current_user['id']
            ]);
            
            $customer_id = $pdo->lastInsertId();
            
            // Add primary email if provided
            if (!empty($_POST['email'])) {
                $email_type = $_POST['email_type'] ?: 'contact';
                $email->addCustomerEmail($customer_id, $_POST['email'], $email_type, 'Primary contact email', $current_user['id']);
            }
            
            $pdo->commit();
            $message = "Customer added successfully! Code: $customer_code";
            $message_type = 'success';
            
            // Redirect to view the new customer
            header("Location: ?mode=view&customer_id=$customer_id");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error adding customer: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['update_customer'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE customers SET 
                    customer_name = ?, contact_person = ?, contact_title = ?, email = ?, phone = ?, phone_ext = ?,
                    address_line1 = ?, address_line2 = ?, city = ?, state = ?, zip_code = ?, country = ?,
                    payment_terms = ?, credit_limit = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['customer_name'],
                $_POST['contact_person'] ?: null,
                $_POST['contact_title'] ?: null,
                $_POST['email'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['phone_ext'] ?: null,
                $_POST['address_line1'] ?: null,
                $_POST['address_line2'] ?: null,
                $_POST['city'] ?: null,
                $_POST['state'] ?: null,
                $_POST['zip_code'] ?: null,
                $_POST['country'] ?: 'USA',
                $_POST['payment_terms'] ?: null,
                $_POST['credit_limit'] ?: 0.00,
                $_POST['notes'] ?: null,
                $_POST['customer_id']
            ]);
            $message = 'Customer updated successfully!';
            $message_type = 'success';
            
            // Redirect to view the updated customer
            header("Location: ?mode=view&customer_id=" . $_POST['customer_id']);
            exit;
        } catch (Exception $e) {
            $message = 'Error updating customer: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['toggle_active'])) {
        try {
            $stmt = $pdo->prepare("UPDATE customers SET is_active = !is_active WHERE id = ?");
            $stmt->execute([$_POST['customer_id']]);
            $message = 'Customer status updated successfully!';
            $message_type = 'success';
            
            // Redirect to view the updated customer
            header("Location: ?mode=view&customer_id=" . $_POST['customer_id']);
            exit;
        } catch (Exception $e) {
            $message = 'Error updating customer status: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.customer_name LIKE ? OR c.customer_code LIKE ? OR c.contact_person LIKE ? OR c.email LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($status_filter !== 'all') {
    $where_conditions[] = "c.is_active = ?";
    $params[] = $status_filter === 'active' ? 1 : 0;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get filtered customers
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as created_by_name,
               (SELECT COUNT(*) FROM products WHERE customer_id = c.id AND is_active = 1) as product_count,
               (SELECT COUNT(*) FROM customer_emails WHERE customer_id = c.id AND is_active = 1) as email_count
        FROM customers c
        LEFT JOIN users u ON c.created_by = u.id
        $where_clause
        ORDER BY c.customer_name
    ");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    $customers = [];
    $message = 'Error loading customers: ' . $e->getMessage();
    $message_type = 'error';
}

// Get customer for editing if requested
$edit_customer = null;
$view_mode = $_GET['mode'] ?? 'list';
$selected_customer_id = $_GET['customer_id'] ?? null;

if ($selected_customer_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.full_name as created_by_name
            FROM customers c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$selected_customer_id]);
        $edit_customer = $stmt->fetch();
        
        if ($edit_customer && $view_mode !== 'edit') {
            $view_mode = 'view'; // Force view mode when customer is selected (unless editing)
        }
    } catch (Exception $e) {
        $message = 'Error loading customer: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Customer Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Mini ERP</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modern Outlook-Style Layout */
        .outlook-container {
            display: flex;
            height: 100vh;
            background-color: #fafafa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .outlook-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0078d4 0%, #106ebe 100%);
            color: white;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .sidebar-subtitle {
            font-size: 12px;
            opacity: 0.8;
            margin: 5px 0 0 0;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 20px 10px;
            opacity: 0.7;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-item:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .nav-item.active {
            background-color: rgba(255,255,255,0.15);
            border-right: 3px solid white;
        }

        .nav-item i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        .nav-item .badge {
            margin-left: auto;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .content-header {
            background: white;
            padding: 15px 25px;
            border-bottom: 1px solid #e1e4e8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 70px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #24292e;
            margin: 0;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
            margin: 0 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #d1d9e0;
            border-radius: 6px;
            font-size: 14px;
            background: #f6f8fa;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            background: white;
            border-color: #0366d6;
            box-shadow: 0 0 0 3px rgba(3, 102, 214, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6a737d;
            font-size: 16px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-modern {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 6px;
            border: 1px solid transparent;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            width: auto;
            min-width: auto;
            flex: none;
        }

        .btn-primary {
            background: #0366d6;
            color: white;
        }

        .btn-primary:hover {
            background: #0256c4;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: white;
            color: #24292e;
            border-color: #d1d9e0;
        }

        .btn-secondary:hover {
            background: #f6f8fa;
            border-color: #c4c9d0;
        }

        .content-body {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        .customer-list {
            width: 400px;
            background: white;
            border-right: 1px solid #e1e4e8;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .list-header {
            padding: 20px;
            border-bottom: 1px solid #e1e4e8;
            background: #f6f8fa;
        }

        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .filter-select {
            padding: 6px 10px;
            border: 1px solid #d1d9e0;
            border-radius: 6px;
            font-size: 13px;
            background: white;
        }

        .list-stats {
            font-size: 13px;
            color: #6a737d;
        }

        .customer-items {
            flex: 1;
            overflow-y: auto;
        }

        .customer-card {
            padding: 15px 20px;
            border-bottom: 1px solid #e1e4e8;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .customer-card:hover {
            background: #f6f8fa;
        }

        .customer-card.selected {
            background: #e6f3ff;
            border-left: 3px solid #0366d6;
        }

        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .customer-name {
            font-weight: 600;
            font-size: 15px;
            color: #24292e;
            margin: 0;
        }

        .customer-code {
            font-size: 12px;
            color: #0366d6;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .customer-contact {
            font-size: 13px;
            color: #6a737d;
            margin-bottom: 5px;
        }

        .customer-location {
            font-size: 12px;
            color: #6a737d;
        }

        .customer-badges {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-active {
            background: #d4f8d4;
            color: #165016;
        }

        .badge-inactive {
            background: #f1f3f4;
            color: #5f6368;
        }

        .badge-products {
            background: #e6f3ff;
            color: #0366d6;
        }

        .badge-emails {
            background: #fff2e6;
            color: #8b5a00;
        }

        .customer-detail {
            flex: 1;
            background: white;
            overflow-y: auto;
        }

        .detail-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e1e4e8;
            background: #f6f8fa;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .detail-title {
            font-size: 20px;
            font-weight: 600;
            color: #24292e;
            margin: 0 0 5px 0;
        }

        .detail-subtitle {
            font-size: 14px;
            color: #6a737d;
        }

        .detail-header-content {
            flex: 1;
        }

        .detail-header-actions {
            flex: none;
            margin-left: 20px;
        }

        /* Prevent floating elements */
        .customer-detail *,
        .detail-content * {
            position: static !important;
            float: none !important;
        }

        /* Allow only specific positioned elements */
        .customer-detail .detail-header,
        .detail-content .detail-section {
            position: relative;
        }

        /* Fix any badges or labels that might be floating */
        .badge,
        .status-badge,
        [class*="primary"],
        [class*="PRIMARY"] {
            position: static !important;
            float: none !important;
            display: inline-block !important;
        }

        .detail-content {
            padding: 25px 30px;
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
            position: relative;
        }

        .detail-section {
            margin-bottom: 25px;
            padding: 0;
            overflow: hidden;
            clear: both;
            position: relative;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #24292e;
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e1e4e8;
            width: 100%;
            box-sizing: border-box;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-field {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .field-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6a737d;
            margin-bottom: 2px;
        }

        .field-value {
            font-size: 14px;
            color: #24292e;
            padding: 4px 0;
            line-height: 1.4;
        }

        /* Compact detail layout */
        .detail-row {
            display: flex;
            gap: 30px;
            margin-bottom: 12px;
            flex-wrap: wrap;
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .detail-item-inline {
            display: flex;
            align-items: baseline;
            gap: 8px;
            min-width: 200px;
            max-width: 100%;
            overflow: hidden;
        }

        .detail-item-inline .field-label {
            font-size: 12px;
            color: #6a737d;
            font-weight: 500;
            min-width: 80px;
        }

        .detail-item-inline .field-value {
            font-size: 14px;
            color: #24292e;
            padding: 0;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 30px;
            text-align: center;
            color: #6a737d;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 10px 0;
        }

        .empty-message {
            font-size: 14px;
            line-height: 1.6;
        }

        /* Form Styling */
        .modern-form {
            padding: 30px;
            max-width: 800px;
        }

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e1e4e8;
            margin-bottom: 20px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #24292e;
            margin: 0 0 20px 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #24292e;
            margin-bottom: 6px;
        }

        .form-input {
            padding: 10px 12px;
            border: 1px solid #d1d9e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: #0366d6;
            box-shadow: 0 0 0 3px rgba(3, 102, 214, 0.1);
            outline: none;
        }

        .form-textarea {
            padding: 10px 12px;
            border: 1px solid #d1d9e0;
            border-radius: 6px;
            font-size: 14px;
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e1e4e8;
            flex-wrap: wrap;
            width: 100%;
            box-sizing: border-box;
            clear: both;
            overflow: hidden;
        }

        .form-actions .btn-modern {
            padding: 8px 16px;
            font-size: 13px;
            min-width: auto;
            flex: none;
            width: auto !important;
        }

        /* Force all modal buttons to be properly sized - with highest specificity */
        .outlook-container .customer-detail .btn-modern,
        .outlook-container .detail-content .btn-modern,
        .outlook-container .form-actions .btn-modern,
        .outlook-container .action-group .btn-modern,
        .main-content .customer-detail .btn-modern,
        .main-content .detail-content .btn-modern,
        .main-content .form-actions .btn-modern,
        .main-content .action-group .btn-modern {
            display: inline-flex !important;
            width: auto !important;
            min-width: auto !important;
            max-width: none !important;
            flex: none !important;
            flex-grow: 0 !important;
            flex-shrink: 0 !important;
            flex-basis: auto !important;
        }

        /* Override any widget button styling */
        .customer-detail .widget .btn-modern,
        .detail-content .widget .btn-modern {
            display: inline-flex !important;
            width: auto !important;
        }

        /* Nuclear option - force all buttons in modal to be inline */
        .customer-detail button,
        .customer-detail a[class*="btn"],
        .detail-content button,
        .detail-content a[class*="btn"] {
            display: inline-flex !important;
            width: auto !important;
            min-width: 100px !important;
            max-width: 200px !important;
            flex: none !important;
            text-align: center !important;
            box-sizing: border-box !important;
        }

        .action-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
        }

        .action-group .btn-modern {
            flex: 0 0 auto !important;
            width: auto !important;
            min-width: auto !important;
        }

        /* Force form-actions to not stretch children */
        .form-actions {
            justify-content: flex-start !important;
            align-items: center !important;
        }

        .form-actions > * {
            flex: none !important;
        }

        .action-divider {
            height: 20px;
            width: 1px;
            background: #e1e4e8;
            margin: 0 8px;
        }

        /* Alert Styling */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin: 15px 30px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4f8d4;
            color: #165016;
            border: 1px solid #a2d2a2;
        }

        .alert-error {
            background: #ffe6e6;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Business Auto-Complete Styling */
        .autocomplete-container {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .autocomplete-input {
            position: relative;
        }

        .autocomplete-input input {
            width: 100%;
        }

        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d9e0;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .suggestion-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s ease;
        }

        .suggestion-item:hover,
        .suggestion-item.highlighted {
            background-color: #f6f8fa;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-name {
            font-weight: 600;
            color: #24292e;
            margin-bottom: 4px;
        }

        .suggestion-address {
            font-size: 13px;
            color: #6a737d;
            margin-bottom: 4px;
        }

        .suggestion-info {
            display: flex;
            gap: 10px;
            font-size: 12px;
            color: #6a737d;
        }

        .suggestion-badge {
            display: inline-block;
            padding: 2px 6px;
            background: #e6f3ff;
            color: #0366d6;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 500;
        }

        .existing-customer-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 10px;
            font-size: 13px;
            color: #8a6d3b;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0366d6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .autocomplete-loading {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .outlook-sidebar {
                width: 250px;
            }
            
            .customer-list {
                width: 350px;
            }
        }

        @media (max-width: 768px) {
            .outlook-container {
                flex-direction: column;
            }
            
            .outlook-sidebar {
                width: 100%;
                height: auto;
            }
            
            .customer-list {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e1e4e8;
            }
            
            .content-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .search-container {
                margin: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="outlook-container">
        <!-- Sidebar Navigation -->
        <div class="outlook-sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-title">Mini ERP</h1>
                <p class="sidebar-subtitle">Customer Management</p>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Customer Views</div>
                    <a href="?mode=list" class="nav-item <?php echo $view_mode === 'list' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span>All Customers</span>
                        <span class="badge"><?php echo count($customers); ?></span>
                    </a>
                    <a href="?mode=list&status=active" class="nav-item <?php echo $status_filter === 'active' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i>
                        <span>Active</span>
                        <span class="badge"><?php echo count(array_filter($customers, function($c) { return $c['is_active']; })); ?></span>
                    </a>
                    <a href="?mode=list&status=inactive" class="nav-item <?php echo $status_filter === 'inactive' ? 'active' : ''; ?>">
                        <i class="fas fa-pause-circle"></i>
                        <span>Inactive</span>
                        <span class="badge"><?php echo count(array_filter($customers, function($c) { return !$c['is_active']; })); ?></span>
                    </a>
                </div>
                
                <?php if ($can_manage): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <a href="?mode=new" class="nav-item <?php echo $view_mode === 'new' ? 'active' : ''; ?>">
                        <i class="fas fa-plus"></i>
                        <span>New Customer</span>
                    </a>
                    <a href="customers.php" class="nav-item">
                        <i class="fas fa-table"></i>
                        <span>Classic View</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="nav-section">
                    <div class="nav-section-title">Navigation</div>
                    <a href="index.php" class="nav-item">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="products.php" class="nav-item">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                    <a href="inventory.php" class="nav-item">
                        <i class="fas fa-warehouse"></i>
                        <span>Inventory</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Content Header -->
            <div class="content-header">
                <div class="header-left">
                    <h1 class="page-title">Customer Management</h1>
                </div>
                
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search customers..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           onkeyup="searchCustomers(this.value)">
                </div>
                
                <div class="header-actions">
                    <?php if ($can_manage): ?>
                    <a href="?mode=new" class="btn-modern btn-primary">
                        <i class="fas fa-plus"></i>
                        New Customer
                    </a>
                    <?php endif; ?>
                    <a href="customers.php" class="btn-modern btn-secondary">
                        <i class="fas fa-table"></i>
                        Table View
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Content Body -->
            <div class="content-body">
                <!-- Customer List -->
                <div class="customer-list">
                    <div class="list-header">
                        <div class="filter-bar">
                            <select class="filter-select" onchange="filterCustomers(this.value, 'status')">
                                <option value="all">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="list-stats">
                            Showing <?php echo count($customers); ?> customers
                        </div>
                    </div>
                    
                    <div class="customer-items">
                        <?php if (empty($customers)): ?>
                            <div class="empty-state">
                                <i class="fas fa-users empty-icon"></i>
                                <h3 class="empty-title">No customers found</h3>
                                <p class="empty-message">Try adjusting your search or filters</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <div class="customer-card <?php echo $selected_customer_id == $customer['id'] ? 'selected' : ''; ?>"
                                     onclick="selectCustomer(<?php echo $customer['id']; ?>)">
                                    <div class="customer-header">
                                        <div>
                                            <h4 class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></h4>
                                            <div class="customer-code"><?php echo htmlspecialchars($customer['customer_code']); ?></div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($customer['contact_person']): ?>
                                    <div class="customer-contact">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($customer['contact_person']); ?>
                                        <?php if ($customer['contact_title']): ?>
                                            - <?php echo htmlspecialchars($customer['contact_title']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($customer['city'] || $customer['state']): ?>
                                    <div class="customer-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php 
                                        $location = array_filter([$customer['city'], $customer['state']]);
                                        echo htmlspecialchars(implode(', ', $location));
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="customer-badges">
                                        <span class="badge <?php echo $customer['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $customer['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <?php if ($customer['product_count'] > 0): ?>
                                        <span class="badge badge-products">
                                            <i class="fas fa-box"></i> <?php echo $customer['product_count']; ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($customer['email_count'] > 0): ?>
                                        <span class="badge badge-emails">
                                            <i class="fas fa-envelope"></i> <?php echo $customer['email_count']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Customer Detail/Form Area -->
                <div class="customer-detail">
                    <?php if ($view_mode === 'new' && $can_manage): ?>
                        <!-- New Customer Form -->
                        <div class="detail-header">
                            <h2 class="detail-title">New Customer</h2>
                            <p class="detail-subtitle">Add a new customer to the system</p>
                        </div>
                        
                        <form method="POST" class="modern-form">
                            <div class="form-section">
                                <h3 class="form-section-title">Basic Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Customer Name *</label>
                                        <input type="text" name="customer_name" class="form-input" required 
                                               placeholder="Company name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" name="contact_person" class="form-input" 
                                               placeholder="Primary contact name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Contact Title</label>
                                        <input type="text" name="contact_title" class="form-input" 
                                               placeholder="e.g., Purchasing Manager">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-input" 
                                               placeholder="contact@customer.com">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" name="phone" class="form-input" 
                                               placeholder="(555) 123-4567">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone Extension</label>
                                        <input type="text" name="phone_ext" class="form-input" 
                                               placeholder="ext. 1234">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3 class="form-section-title">Address Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Address Line 1</label>
                                        <input type="text" name="address_line1" class="form-input" 
                                               placeholder="Street address">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Address Line 2</label>
                                        <input type="text" name="address_line2" class="form-input" 
                                               placeholder="Suite, unit, etc. (optional)">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-input" 
                                               placeholder="City">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">State</label>
                                        <input type="text" name="state" class="form-input" 
                                               placeholder="State/Province">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">ZIP Code</label>
                                        <input type="text" name="zip_code" class="form-input" 
                                               placeholder="ZIP/Postal code">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Country</label>
                                        <input type="text" name="country" class="form-input" 
                                               value="USA" placeholder="Country">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3 class="form-section-title">Business Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Payment Terms</label>
                                        <input type="text" name="payment_terms" class="form-input" 
                                               placeholder="e.g., Net 30, COD">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Credit Limit ($)</label>
                                        <input type="number" name="credit_limit" class="form-input" 
                                               step="0.01" min="0" value="0.00">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-textarea" 
                                              placeholder="Additional notes about this customer"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="add_customer" class="btn-modern btn-primary">
                                    <i class="fas fa-save"></i>
                                    Save Customer
                                </button>
                                <a href="?mode=list" class="btn-modern btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    
                    <?php elseif ($edit_customer): ?>
                        <!-- Customer Detail View -->
                        <div class="detail-header">
                            <div class="detail-header-content">
                                <h2 class="detail-title"><?php echo htmlspecialchars($edit_customer['customer_name']); ?></h2>
                                <p class="detail-subtitle">Customer Code: <?php echo htmlspecialchars($edit_customer['customer_code']); ?></p>
                            </div>
                            <?php if ($can_manage): ?>
                            <div class="detail-header-actions">
                                <a href="?mode=edit&customer_id=<?php echo $edit_customer['id']; ?>" class="btn-modern btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="detail-content">
                            <!-- Contact Information -->
                            <div class="detail-section">
                                <h3 class="section-title">Contact Information</h3>
                                <div class="detail-row">
                                    <div class="detail-item-inline">
                                        <span class="field-label">Contact:</span>
                                        <span class="field-value"><?php echo htmlspecialchars($edit_customer['contact_person'] ?: 'Not specified'); ?></span>
                                    </div>
                                    <div class="detail-item-inline">
                                        <span class="field-label">Title:</span>
                                        <span class="field-value"><?php echo htmlspecialchars($edit_customer['contact_title'] ?: 'Not specified'); ?></span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-item-inline">
                                        <span class="field-label">Email:</span>
                                        <span class="field-value">
                                            <?php if ($edit_customer['email']): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($edit_customer['email']); ?>">
                                                    <?php echo htmlspecialchars($edit_customer['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                Not specified
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="detail-item-inline">
                                        <span class="field-label">Phone:</span>
                                        <span class="field-value">
                                            <?php 
                                            if ($edit_customer['phone']) {
                                                echo htmlspecialchars($edit_customer['phone']);
                                                if ($edit_customer['phone_ext']) {
                                                    echo ' ext. ' . htmlspecialchars($edit_customer['phone_ext']);
                                                }
                                            } else {
                                                echo 'Not specified';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address Information -->
                            <div class="detail-section">
                                <h3 class="section-title">Address</h3>
                                <?php 
                                $address_parts = array_filter([
                                    $edit_customer['address_line1'],
                                    $edit_customer['address_line2']
                                ]);
                                $location_parts = array_filter([
                                    $edit_customer['city'],
                                    $edit_customer['state'],
                                    $edit_customer['zip_code']
                                ]);
                                ?>
                                <?php if (!empty($address_parts) || !empty($location_parts) || $edit_customer['country']): ?>
                                <div class="field-value" style="line-height: 1.6;">
                                    <?php if (!empty($address_parts)): ?>
                                        <?php echo htmlspecialchars(implode('<br>', $address_parts)); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($location_parts)): ?>
                                        <?php echo htmlspecialchars(implode(', ', $location_parts)); ?>
                                        <?php if ($edit_customer['country'] && $edit_customer['country'] !== 'USA'): ?>
                                            <br><?php echo htmlspecialchars($edit_customer['country']); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="field-value">Not specified</div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Business Information -->
                            <div class="detail-section">
                                <h3 class="section-title">Business Information</h3>
                                <div class="detail-row">
                                    <div class="detail-item-inline">
                                        <span class="field-label">Payment Terms:</span>
                                        <span class="field-value"><?php echo htmlspecialchars($edit_customer['payment_terms'] ?: 'Not specified'); ?></span>
                                    </div>
                                    <div class="detail-item-inline">
                                        <span class="field-label">Credit Limit:</span>
                                        <span class="field-value">$<?php echo number_format($edit_customer['credit_limit'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-item-inline">
                                        <span class="field-label">Status:</span>
                                        <span class="field-value">
                                            <span class="badge <?php echo $edit_customer['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo $edit_customer['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="detail-item-inline">
                                        <span class="field-label">Created By:</span>
                                        <span class="field-value"><?php echo htmlspecialchars($edit_customer['created_by_name'] ?: 'Unknown'); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($edit_customer['notes']): ?>
                                <div class="detail-row" style="margin-top: 15px;">
                                    <div class="detail-field" style="flex: 1;">
                                        <span class="field-label">Notes</span>
                                        <div class="field-value" style="background: #f6f8fa; padding: 10px; border-radius: 4px; font-size: 13px; line-height: 1.5;">
                                            <?php echo nl2br(htmlspecialchars($edit_customer['notes'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-actions">
                                <!-- Status Actions -->
                                <?php if ($can_manage): ?>
                                <div class="action-group">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                                        <button type="submit" name="toggle_active" class="btn-modern <?php echo $edit_customer['is_active'] ? 'btn-secondary' : 'btn-primary'; ?>" style="border: none;">
                                            <i class="fas fa-<?php echo $edit_customer['is_active'] ? 'pause' : 'play'; ?>"></i>
                                            <?php echo $edit_customer['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="action-divider"></div>
                                <?php endif; ?>
                                
                                <!-- Related Actions -->
                                <div class="action-group">
                                    <a href="customers_enhanced.php?view_contacts=<?php echo $edit_customer['id']; ?>" class="btn-modern btn-secondary">
                                        <i class="fas fa-users"></i> Contacts
                                    </a>
                                    <a href="customers_with_emails.php?manage_emails=<?php echo $edit_customer['id']; ?>" class="btn-modern btn-secondary">
                                        <i class="fas fa-envelope"></i> Emails
                                    </a>
                                </div>
                                
                                <div class="action-divider"></div>
                                
                                <!-- Utility Actions -->
                                <div class="action-group">
                                    <button onclick="printCustomer()" class="btn-modern btn-secondary">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                    <button onclick="exportCustomer()" class="btn-modern btn-secondary">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <a href="customers.php?edit=<?php echo $edit_customer['id']; ?>" class="btn-modern btn-secondary">
                                        <i class="fas fa-table"></i> Classic View
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif ($view_mode === 'edit' && $edit_customer && $can_manage): ?>
                        <!-- Customer Edit Form -->
                        <div class="detail-header">
                            <h2 class="detail-title">Edit Customer</h2>
                            <p class="detail-subtitle"><?php echo htmlspecialchars($edit_customer['customer_name']); ?> (<?php echo htmlspecialchars($edit_customer['customer_code']); ?>)</p>
                        </div>
                        
                        <form method="POST" class="modern-form">
                            <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                            
                            <div class="form-section">
                                <h3 class="form-section-title">Basic Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Customer Name *</label>
                                        <input type="text" name="customer_name" class="form-input" required 
                                               value="<?php echo htmlspecialchars($edit_customer['customer_name']); ?>"
                                               placeholder="Company name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Contact Person</label>
                                        <input type="text" name="contact_person" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['contact_person'] ?? ''); ?>"
                                               placeholder="Primary contact name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Contact Title</label>
                                        <input type="text" name="contact_title" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['contact_title'] ?? ''); ?>"
                                               placeholder="e.g., Purchasing Manager">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['email'] ?? ''); ?>"
                                               placeholder="contact@customer.com">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" name="phone" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['phone'] ?? ''); ?>"
                                               placeholder="(555) 123-4567">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone Extension</label>
                                        <input type="text" name="phone_ext" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['phone_ext'] ?? ''); ?>"
                                               placeholder="ext. 1234">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3 class="form-section-title">Address Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Address Line 1</label>
                                        <input type="text" name="address_line1" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['address_line1'] ?? ''); ?>"
                                               placeholder="Street address">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Address Line 2</label>
                                        <input type="text" name="address_line2" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['address_line2'] ?? ''); ?>"
                                               placeholder="Suite, unit, etc. (optional)">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['city'] ?? ''); ?>"
                                               placeholder="City">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">State</label>
                                        <input type="text" name="state" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['state'] ?? ''); ?>"
                                               placeholder="State/Province">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">ZIP Code</label>
                                        <input type="text" name="zip_code" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['zip_code'] ?? ''); ?>"
                                               placeholder="ZIP/Postal code">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Country</label>
                                        <input type="text" name="country" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['country'] ?? 'USA'); ?>"
                                               placeholder="Country">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3 class="form-section-title">Business Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Payment Terms</label>
                                        <input type="text" name="payment_terms" class="form-input" 
                                               value="<?php echo htmlspecialchars($edit_customer['payment_terms'] ?? ''); ?>"
                                               placeholder="e.g., Net 30, COD">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Credit Limit ($)</label>
                                        <input type="number" name="credit_limit" class="form-input" 
                                               step="0.01" min="0" 
                                               value="<?php echo htmlspecialchars($edit_customer['credit_limit'] ?? '0.00'); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-textarea" 
                                              placeholder="Additional notes about this customer"><?php echo htmlspecialchars($edit_customer['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_customer" class="btn-modern btn-primary">
                                    <i class="fas fa-save"></i>
                                    Update Customer
                                </button>
                                <a href="?mode=view&customer_id=<?php echo $edit_customer['id']; ?>" class="btn-modern btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                        
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="empty-state">
                            <i class="fas fa-users empty-icon"></i>
                            <h3 class="empty-title">Select a customer</h3>
                            <p class="empty-message">Choose a customer from the list to view details, or create a new customer.</p>
                            <?php if ($can_manage): ?>
                            <a href="?mode=new" class="btn-modern btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-plus"></i>
                                New Customer
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectCustomer(customerId) {
            window.location.href = `?mode=view&customer_id=${customerId}`;
        }

        function searchCustomers(query) {
            const params = new URLSearchParams(window.location.search);
            if (query.trim()) {
                params.set('search', query);
            } else {
                params.delete('search');
            }
            window.location.search = params.toString();
        }

        function filterCustomers(value, type) {
            const params = new URLSearchParams(window.location.search);
            if (value !== 'all') {
                params.set(type, value);
            } else {
                params.delete(type);
            }
            window.location.search = params.toString();
        }

        // Auto-search functionality
        let searchTimeout;
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchCustomers(e.target.value);
                }, 500);
            });
        }

        // Print customer details
        function printCustomer() {
            const printWindow = window.open('', '_blank');
            const customerDetails = document.querySelector('.customer-detail').innerHTML;
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Customer Details - Print</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .detail-header { border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
                        .detail-title { font-size: 24px; margin: 0; }
                        .detail-subtitle { color: #666; margin: 5px 0 0 0; }
                        .section-title { font-size: 16px; font-weight: bold; margin: 20px 0 10px 0; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
                        .detail-row { margin-bottom: 8px; }
                        .detail-item-inline { display: inline-block; margin-right: 30px; margin-bottom: 5px; }
                        .field-label { font-weight: bold; margin-right: 5px; }
                        .field-value { display: inline; }
                        .badge { padding: 2px 6px; border-radius: 3px; font-size: 12px; }
                        .badge-active { background: #d4f8d4; color: #165016; }
                        .badge-inactive { background: #f1f3f4; color: #5f6368; }
                        .form-actions { display: none; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    ${customerDetails.replace(/<div class="form-actions">[\s\S]*?<\/div>/, '')}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }

        // Export customer details
        function exportCustomer() {
            const urlParams = new URLSearchParams(window.location.search);
            const customerId = urlParams.get('customer_id');
            
            if (!customerId) {
                alert('No customer selected for export');
                return;
            }

            // Create export data
            const customerData = {
                timestamp: new Date().toISOString(),
                customer_id: customerId,
                export_type: 'customer_details'
            };

            // Option 1: Export as JSON
            const dataStr = JSON.stringify(customerData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `customer_${customerId}_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            // Show success message
            showNotification('Customer data exported successfully!', 'success');
        }

        // Notification helper
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i> ${message}`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>