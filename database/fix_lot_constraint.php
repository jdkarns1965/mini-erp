<?php
/**
 * Fix unique lot constraint to allow multiple containers with same lot number
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "Fixing lot number constraint...\n\n";
    
    // Drop the existing unique constraint that blocks same lot numbers
    echo "1. Dropping unique_material_lot constraint...\n";
    $pdo->exec("ALTER TABLE inventory DROP INDEX unique_material_lot");
    echo "✓ Unique constraint removed\n";
    
    // Add a composite index for performance but not unique
    echo "2. Adding non-unique index for performance...\n";
    $pdo->exec("
        CREATE INDEX idx_material_lot_lookup 
        ON inventory (material_id, lot_number, received_date)
    ");
    echo "✓ Performance index added\n";
    
    // Verify the constraints
    echo "\n3. Verifying current indexes:\n";
    $indexes = $pdo->query("SHOW INDEX FROM inventory")->fetchAll();
    foreach ($indexes as $index) {
        if (in_array($index['Key_name'], ['unique_material_lot', 'idx_material_lot_lookup'])) {
            $unique = $index['Non_unique'] == 0 ? 'UNIQUE' : 'NON-UNIQUE';
            echo "  - {$index['Key_name']}: {$unique} on {$index['Column_name']}\n";
        }
    }
    
    echo "\n🎉 Lot constraint fix completed!\n";
    echo "✓ Multiple containers can now have the same lot number\n";
    echo "✓ FIFO ordering will still work correctly by received_date\n";
    echo "✓ Performance is maintained with lookup index\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}