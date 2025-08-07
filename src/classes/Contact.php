<?php
/**
 * Contact Management Class
 * Handles individual contacts and their relationships to customers/suppliers
 */

class Contact {
    private $db;
    private $pdo;
    
    public function __construct(Database $db) {
        $this->db = $db;
        $this->pdo = $db->connect();
    }
    
    /**
     * Create a new contact
     */
    public function createContact($data, $created_by) {
        $stmt = $this->pdo->prepare("
            INSERT INTO contacts (first_name, last_name, email, phone, phone_ext, mobile_phone, 
                                job_title, department, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['first_name'],
            $data['last_name'], 
            $data['email'] ?: null,
            $data['phone'] ?: null,
            $data['phone_ext'] ?: null,
            $data['mobile_phone'] ?: null,
            $data['job_title'] ?: null,
            $data['department'] ?: null,
            $data['notes'] ?: null,
            $created_by
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update an existing contact
     */
    public function updateContact($contact_id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE contacts SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, phone_ext = ?, 
                mobile_phone = ?, job_title = ?, department = ?, notes = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'] ?: null,
            $data['phone'] ?: null, 
            $data['phone_ext'] ?: null,
            $data['mobile_phone'] ?: null,
            $data['job_title'] ?: null,
            $data['department'] ?: null,
            $data['notes'] ?: null,
            $contact_id
        ]);
    }
    
    /**
     * Get contact by ID
     */
    public function getContact($contact_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$contact_id]);
        return $stmt->fetch();
    }
    
    /**
     * Link contact to customer with role
     */
    public function linkToCustomer($contact_id, $customer_id, $role = 'Primary', $is_primary = false) {
        // If this is being set as primary, unset other primary contacts
        if ($is_primary) {
            $this->pdo->prepare("UPDATE customer_contacts SET is_primary = FALSE WHERE customer_id = ?")
                     ->execute([$customer_id]);
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO customer_contacts (contact_id, customer_id, role, is_primary)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE role = VALUES(role), is_primary = VALUES(is_primary)
        ");
        
        return $stmt->execute([$contact_id, $customer_id, $role, $is_primary]);
    }
    
    /**
     * Link contact to supplier with role
     */
    public function linkToSupplier($contact_id, $supplier_id, $role = 'Primary', $is_primary = false) {
        // If this is being set as primary, unset other primary contacts
        if ($is_primary) {
            $this->pdo->prepare("UPDATE supplier_contacts SET is_primary = FALSE WHERE supplier_id = ?")
                     ->execute([$supplier_id]);
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO supplier_contacts (contact_id, supplier_id, role, is_primary)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE role = VALUES(role), is_primary = VALUES(is_primary)
        ");
        
        return $stmt->execute([$contact_id, $supplier_id, $role, $is_primary]);
    }
    
    /**
     * Get all contacts for a customer
     */
    public function getCustomerContacts($customer_id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cc.role, cc.is_primary, cc.is_active as relationship_active
            FROM contacts c
            JOIN customer_contacts cc ON c.id = cc.contact_id
            WHERE cc.customer_id = ? AND c.is_active = TRUE
            ORDER BY cc.is_primary DESC, c.last_name, c.first_name
        ");
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all contacts for a supplier
     */
    public function getSupplierContacts($supplier_id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, sc.role, sc.is_primary, sc.is_active as relationship_active
            FROM contacts c
            JOIN supplier_contacts sc ON c.id = sc.contact_id
            WHERE sc.supplier_id = ? AND c.is_active = TRUE
            ORDER BY sc.is_primary DESC, c.last_name, c.first_name
        ");
        $stmt->execute([$supplier_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get primary contact for customer
     */
    public function getCustomerPrimaryContact($customer_id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, cc.role
            FROM contacts c
            JOIN customer_contacts cc ON c.id = cc.contact_id
            WHERE cc.customer_id = ? AND cc.is_primary = TRUE AND c.is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute([$customer_id]);
        return $stmt->fetch();
    }
    
    /**
     * Get primary contact for supplier
     */
    public function getSupplierPrimaryContact($supplier_id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, sc.role
            FROM contacts c
            JOIN supplier_contacts sc ON c.id = sc.contact_id
            WHERE sc.supplier_id = ? AND sc.is_primary = TRUE AND c.is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute([$supplier_id]);
        return $stmt->fetch();
    }
    
    /**
     * Remove contact relationship (but keep contact record)
     */
    public function unlinkFromCustomer($contact_id, $customer_id) {
        $stmt = $this->pdo->prepare("DELETE FROM customer_contacts WHERE contact_id = ? AND customer_id = ?");
        return $stmt->execute([$contact_id, $customer_id]);
    }
    
    public function unlinkFromSupplier($contact_id, $supplier_id) {
        $stmt = $this->pdo->prepare("DELETE FROM supplier_contacts WHERE contact_id = ? AND supplier_id = ?");
        return $stmt->execute([$contact_id, $supplier_id]);
    }
    
    /**
     * Search for existing contacts by name or email (to avoid duplicates)
     */
    public function searchContacts($search_term) {
        $search = "%{$search_term}%";
        $stmt = $this->pdo->prepare("
            SELECT * FROM contacts 
            WHERE (full_name LIKE ? OR email LIKE ? OR phone LIKE ?) 
            AND is_active = TRUE
            ORDER BY last_name, first_name
            LIMIT 10
        ");
        $stmt->execute([$search, $search, $search]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get contact roles for dropdowns
     */
    public static function getCustomerRoles() {
        return [
            'Primary' => 'Primary Contact',
            'Buyer' => 'Buyer/Purchasing', 
            'Engineering' => 'Engineering',
            'Quality' => 'Quality Assurance',
            'Finance' => 'Finance/Accounting',
            'Shipping' => 'Shipping/Receiving',
            'Management' => 'Management'
        ];
    }
    
    public static function getSupplierRoles() {
        return [
            'Primary' => 'Primary Contact',
            'Sales' => 'Sales Representative',
            'Technical' => 'Technical Support',
            'Quality' => 'Quality Assurance', 
            'Billing' => 'Billing/Accounting',
            'Customer Service' => 'Customer Service',
            'Management' => 'Management'
        ];
    }
}
?>