<?php
/**
 * Add foreign key columns to existing tables
 */

require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "Adding foreign key columns...\n\n";
    
    // Add customer_id to products
    echo "1. Adding customer_id to products table...\n";
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN customer_id INT AFTER customer_part_number");
        echo "✓ customer_id column added to products\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ customer_id column already exists in products\n";
        } else {
            throw $e;
        }
    }
    
    // Add supplier_id to materials
    echo "2. Adding supplier_id to materials table...\n";
    try {
        $pdo->exec("ALTER TABLE materials ADD COLUMN supplier_id INT AFTER supplier_name");
        echo "✓ supplier_id column added to materials\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ supplier_id column already exists in materials\n";
        } else {
            throw $e;
        }
    }
    
    // Update foreign key references
    echo "3. Updating product customer references...\n";
    $updated = $pdo->exec("
        UPDATE products p 
        JOIN customers c ON p.customer_name = c.customer_name 
        SET p.customer_id = c.id 
        WHERE p.customer_name IS NOT NULL AND p.customer_id IS NULL
    ");
    echo "✓ Updated $updated product records\n";
    
    echo "4. Updating material supplier references...\n";
    $updated = $pdo->exec("
        UPDATE materials m 
        JOIN suppliers s ON m.supplier_name = s.supplier_name 
        SET m.supplier_id = s.id 
        WHERE m.supplier_name IS NOT NULL AND m.supplier_id IS NULL
    ");
    echo "✓ Updated $updated material records\n";
    
    // Final verification
    echo "\nVerification:\n";
    $customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    echo "✓ Customers: $customers\n";
    
    $suppliers = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    echo "✓ Suppliers: $suppliers\n";
    
    $products_linked = $pdo->query("SELECT COUNT(*) FROM products WHERE customer_id IS NOT NULL")->fetchColumn();
    echo "✓ Products with customer links: $products_linked\n";
    
    $materials_linked = $pdo->query("SELECT COUNT(*) FROM materials WHERE supplier_id IS NOT NULL")->fetchColumn();
    echo "✓ Materials with supplier links: $materials_linked\n";
    
    echo "\n🎉 Foreign key setup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}