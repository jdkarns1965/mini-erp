<?php
/**
 * Manual fix runner for customers and suppliers
 */

require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "Running customers and suppliers normalization fix...\n\n";
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/fix_customers_suppliers.sql');
    
    // Remove comments and split into statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
                echo "✓ Executed statement $executed\n";
            } catch (PDOException $e) {
                // Show warnings but continue
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "⚠ Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n✓ Executed $executed SQL statements\n\n";
    
    // Verify results
    echo "Verification:\n";
    
    // Check customers
    $customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    echo "✓ Customers table: $customers records\n";
    
    // Check suppliers  
    $suppliers = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    echo "✓ Suppliers table: $suppliers records\n";
    
    // Check products with customer_id
    $products_linked = $pdo->query("SELECT COUNT(*) FROM products WHERE customer_id IS NOT NULL")->fetchColumn();
    echo "✓ Products linked to customers: $products_linked\n";
    
    // Check materials with supplier_id
    $materials_linked = $pdo->query("SELECT COUNT(*) FROM materials WHERE supplier_id IS NOT NULL")->fetchColumn();
    echo "✓ Materials linked to suppliers: $materials_linked\n";
    
    echo "\n🎉 Customers and suppliers normalization completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}