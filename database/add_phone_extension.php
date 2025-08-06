<?php
/**
 * Add phone extension fields to suppliers and customers tables
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "Adding phone extension fields...\n\n";
    
    // Add phone_ext to suppliers table
    echo "1. Adding phone_ext to suppliers table...\n";
    try {
        $pdo->exec("ALTER TABLE suppliers ADD COLUMN phone_ext VARCHAR(20) AFTER phone");
        echo "✓ phone_ext column added to suppliers\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ phone_ext column already exists in suppliers\n";
        } else {
            throw $e;
        }
    }
    
    // Add phone_ext to customers table
    echo "2. Adding phone_ext to customers table...\n";
    try {
        $pdo->exec("ALTER TABLE customers ADD COLUMN phone_ext VARCHAR(20) AFTER phone");
        echo "✓ phone_ext column added to customers\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ phone_ext column already exists in customers\n";
        } else {
            throw $e;
        }
    }
    
    // Verify the columns were added
    echo "\n3. Verifying suppliers table structure:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM suppliers WHERE Field LIKE 'phone%'")->fetchAll();
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n4. Verifying customers table structure:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM customers WHERE Field LIKE 'phone%'")->fetchAll();
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n🎉 Phone extension fields added successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}