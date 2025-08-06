<?php
/**
 * Add container_type column to inventory table
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "Adding container_type column to inventory table...\n\n";
    
    // Add container_type column
    $pdo->exec("
        ALTER TABLE inventory 
        ADD COLUMN container_type VARCHAR(50) DEFAULT 'gaylord' 
        AFTER quantity_available
    ");
    
    echo "✓ container_type column added successfully\n";
    
    // Verify the column was added
    $columns = $pdo->query("SHOW COLUMNS FROM inventory")->fetchAll();
    echo "\nInventory table columns:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\n🎉 Database update completed!\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✓ container_type column already exists\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}