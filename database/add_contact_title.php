<?php
/**
 * Add contact title fields to suppliers and customers tables
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "Adding contact title fields...\n\n";
    
    // Add contact_title to suppliers table
    echo "1. Adding contact_title to suppliers table...\n";
    try {
        $pdo->exec("ALTER TABLE suppliers ADD COLUMN contact_title VARCHAR(100) AFTER contact_person");
        echo "✓ contact_title column added to suppliers\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ contact_title column already exists in suppliers\n";
        } else {
            throw $e;
        }
    }
    
    // Add contact_title to customers table
    echo "2. Adding contact_title to customers table...\n";
    try {
        $pdo->exec("ALTER TABLE customers ADD COLUMN contact_title VARCHAR(100) AFTER contact_person");
        echo "✓ contact_title column added to customers\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ contact_title column already exists in customers\n";
        } else {
            throw $e;
        }
    }
    
    // Verify the columns were added
    echo "\n3. Verifying suppliers table structure:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM suppliers WHERE Field LIKE 'contact%'")->fetchAll();
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n4. Verifying customers table structure:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM customers WHERE Field LIKE 'contact%'")->fetchAll();
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n🎉 Contact title fields added successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}