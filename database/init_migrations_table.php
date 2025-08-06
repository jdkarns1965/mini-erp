<?php
/**
 * Initialize migrations table
 * Creates the migrations tracking table if it doesn't exist
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "🗄️  Initializing migrations table...\n";
    
    // Create migrations table
    $sql = "
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_filename (filename)
        )
    ";
    
    $pdo->exec($sql);
    echo "✅ Migrations table created successfully\n";
    
    // Mark existing changes as already executed since they're in the current database
    $executed_migrations = [
        'add_contact_title.php',
        'add_container_type.php', 
        'add_phone_extension.php'
    ];
    
    echo "\n📝 Marking existing changes as executed...\n";
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (migration_name) VALUES (?)");
    
    foreach ($executed_migrations as $migration) {
        $stmt->execute([$migration]);
        echo "  ✓ $migration\n";
    }
    
    // Check current constraint status
    echo "\n🔍 Checking current database state...\n";
    
    // Check if unique_material_lot constraint exists
    $constraints = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'inventory' 
        AND CONSTRAINT_TYPE = 'UNIQUE'
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('unique_material_lot', $constraints)) {
        echo "  ⚠️  unique_material_lot constraint still exists - needs fixing\n";
    } else {
        echo "  ✅ unique_material_lot constraint already removed\n";
        // Mark as executed since it's already done
        $stmt->execute(['fix_lot_constraint.php']);
    }
    
    echo "\n🎉 Migration system initialized!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>