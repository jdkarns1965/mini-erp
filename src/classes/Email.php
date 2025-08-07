<?php
/**
 * Email Management Class
 * Handles department/group emails for customers and suppliers
 */

class Email {
    private $db;
    private $pdo;
    
    public function __construct(Database $db) {
        $this->db = $db;
        $this->pdo = $db->connect();
    }
    
    /**
     * Add email to customer
     */
    public function addCustomerEmail($customer_id, $email, $email_type, $description = null, $created_by) {
        $stmt = $this->pdo->prepare("
            INSERT INTO customer_emails (customer_id, email, email_type, description, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$customer_id, $email, $email_type, $description, $created_by]);
    }
    
    /**
     * Add email to supplier
     */
    public function addSupplierEmail($supplier_id, $email, $email_type, $description = null, $created_by) {
        $stmt = $this->pdo->prepare("
            INSERT INTO supplier_emails (supplier_id, email, email_type, description, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$supplier_id, $email, $email_type, $description, $created_by]);
    }
    
    /**
     * Get all emails for a customer
     */
    public function getCustomerEmails($customer_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM customer_emails 
            WHERE customer_id = ? AND is_active = TRUE 
            ORDER BY email_type, email
        ");
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all emails for a supplier
     */
    public function getSupplierEmails($supplier_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM supplier_emails 
            WHERE supplier_id = ? AND is_active = TRUE 
            ORDER BY email_type, email
        ");
        $stmt->execute([$supplier_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get emails by type for customer
     */
    public function getCustomerEmailsByType($customer_id, $email_type) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM customer_emails 
            WHERE customer_id = ? AND email_type = ? AND is_active = TRUE 
            ORDER BY email
        ");
        $stmt->execute([$customer_id, $email_type]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get emails by type for supplier
     */
    public function getSupplierEmailsByType($supplier_id, $email_type) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM supplier_emails 
            WHERE supplier_id = ? AND email_type = ? AND is_active = TRUE 
            ORDER BY email
        ");
        $stmt->execute([$supplier_id, $email_type]);
        return $stmt->fetchAll();
    }
    
    /**
     * Remove email (soft delete)
     */
    public function removeCustomerEmail($email_id) {
        $stmt = $this->pdo->prepare("UPDATE customer_emails SET is_active = FALSE WHERE id = ?");
        return $stmt->execute([$email_id]);
    }
    
    public function removeSupplierEmail($email_id) {
        $stmt = $this->pdo->prepare("UPDATE supplier_emails SET is_active = FALSE WHERE id = ?");
        return $stmt->execute([$email_id]);
    }
    
    /**
     * Get customer email types for dropdown
     */
    public static function getCustomerEmailTypes() {
        return [
            'contact' => 'Main Contact',
            'department' => 'Department Group'
        ];
    }
    
    /**
     * Get supplier email types for dropdown  
     */
    public static function getSupplierEmailTypes() {
        return [
            'contact' => 'Individual Contact',
            'department' => 'Department Email',
            'sales' => 'Sales Department',
            'support' => 'Customer Support',
            'billing' => 'Billing Department',
            'quality' => 'Quality Department',
            'shipping' => 'Shipping Department',
            'returns' => 'Returns Department',
            'general' => 'General Inquiries'
        ];
    }
    
    /**
     * Get all emails for a customer formatted for easy use
     */
    public function getCustomerEmailList($customer_id, $format = 'array') {
        $emails = $this->getCustomerEmails($customer_id);
        
        if ($format === 'string') {
            return implode(', ', array_column($emails, 'email'));
        }
        
        if ($format === 'grouped') {
            $grouped = [];
            foreach ($emails as $email) {
                $grouped[$email['email_type']][] = $email;
            }
            return $grouped;
        }
        
        return $emails; // default array format
    }
    
    /**
     * Get all emails for a supplier formatted for easy use
     */
    public function getSupplierEmailList($supplier_id, $format = 'array') {
        $emails = $this->getSupplierEmails($supplier_id);
        
        if ($format === 'string') {
            return implode(', ', array_column($emails, 'email'));
        }
        
        if ($format === 'grouped') {
            $grouped = [];
            foreach ($emails as $email) {
                $grouped[$email['email_type']][] = $email;
            }
            return $grouped;
        }
        
        return $emails; // default array format
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Check if email already exists for customer
     */
    public function customerEmailExists($customer_id, $email) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM customer_emails 
            WHERE customer_id = ? AND email = ? AND is_active = TRUE
        ");
        $stmt->execute([$customer_id, $email]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Check if email already exists for supplier
     */
    public function supplierEmailExists($supplier_id, $email) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM supplier_emails 
            WHERE supplier_id = ? AND email = ? AND is_active = TRUE
        ");
        $stmt->execute([$supplier_id, $email]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}
?>