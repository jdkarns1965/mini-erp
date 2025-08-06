<?php
/**
 * Step-by-step creation of customers and suppliers tables
 */

require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "Creating customers and suppliers tables...\n\n";
    
    // Create customers table
    echo "1. Creating customers table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_code VARCHAR(20) UNIQUE,
            customer_name VARCHAR(100) NOT NULL,
            contact_person VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            address_line1 VARCHAR(100),
            address_line2 VARCHAR(100),
            city VARCHAR(50),
            state VARCHAR(20),
            zip_code VARCHAR(20),
            country VARCHAR(50) DEFAULT 'USA',
            is_active BOOLEAN DEFAULT TRUE,
            payment_terms VARCHAR(50),
            credit_limit DECIMAL(12,2) DEFAULT 0.00,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by INT DEFAULT 1
        )
    ");
    echo "✓ Customers table created\n";
    
    // Create suppliers table
    echo "2. Creating suppliers table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_code VARCHAR(20) UNIQUE,
            supplier_name VARCHAR(100) NOT NULL,
            contact_person VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            address_line1 VARCHAR(100),
            address_line2 VARCHAR(100),
            city VARCHAR(50),
            state VARCHAR(20),
            zip_code VARCHAR(20),
            country VARCHAR(50) DEFAULT 'USA',
            is_active BOOLEAN DEFAULT TRUE,
            payment_terms VARCHAR(50),
            lead_time_days INT DEFAULT 0,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by INT DEFAULT 1
        )
    ");
    echo "✓ Suppliers table created\n";
    
    // Insert sample data
    echo "3. Inserting sample customers...\n";
    $customers = $pdo->query("SELECT DISTINCT customer_name FROM products WHERE customer_name IS NOT NULL AND customer_name != ''")->fetchAll();
    $customerCount = 0;
    foreach ($customers as $customer) {
        $customerCount++;
        $code = 'CUST' . str_pad($customerCount, 3, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO customers (customer_name, customer_code) VALUES (?, ?)");
        $stmt->execute([$customer['customer_name'], $code]);
    }
    echo "✓ Inserted $customerCount customers\n";
    
    echo "4. Inserting sample suppliers...\n";
    $suppliers = $pdo->query("SELECT DISTINCT supplier_name FROM materials WHERE supplier_name IS NOT NULL AND supplier_name != ''")->fetchAll();
    $supplierCount = 0;
    foreach ($suppliers as $supplier) {
        $supplierCount++;
        $code = 'SUPP' . str_pad($supplierCount, 3, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO suppliers (supplier_name, supplier_code) VALUES (?, ?)");
        $stmt->execute([$supplier['supplier_name'], $code]);
    }
    echo "✓ Inserted $supplierCount suppliers\n";
    
    // Update foreign key references
    echo "5. Updating product customer references...\n";
    $updated = $pdo->exec("
        UPDATE products p 
        JOIN customers c ON p.customer_name = c.customer_name 
        SET p.customer_id = c.id 
        WHERE p.customer_name IS NOT NULL
    ");
    echo "✓ Updated $updated product records\n";
    
    echo "6. Updating material supplier references...\n";
    $updated = $pdo->exec("
        UPDATE materials m 
        JOIN suppliers s ON m.supplier_name = s.supplier_name 
        SET m.supplier_id = s.id 
        WHERE m.supplier_name IS NOT NULL
    ");
    echo "✓ Updated $updated material records\n";
    
    echo "\n🎉 Normalization completed successfully!\n";
    echo "- Created $customerCount customers\n";
    echo "- Created $supplierCount suppliers\n";
    echo "- Updated foreign key references\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}