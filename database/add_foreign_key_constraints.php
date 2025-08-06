<?php
/**
 * Add Foreign Key Constraints
 * Creates proper foreign key relationships between tables
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "🔗 Adding foreign key constraints...\n\n";
    
    // Add foreign key constraints
    $constraints = [
        [
            'table' => 'materials',
            'column' => 'supplier_id',
            'references' => 'suppliers(id)',
            'name' => 'fk_materials_supplier'
        ],
        [
            'table' => 'products', 
            'column' => 'customer_id',
            'references' => 'customers(id)',
            'name' => 'fk_products_customer'
        ],
        [
            'table' => 'inventory',
            'column' => 'material_id', 
            'references' => 'materials(id)',
            'name' => 'fk_inventory_material'
        ],
        [
            'table' => 'inventory',
            'column' => 'received_by',
            'references' => 'users(id)', 
            'name' => 'fk_inventory_user'
        ]
    ];
    
    foreach ($constraints as $constraint) {
        echo "Adding {$constraint['name']} ({$constraint['table']}.{$constraint['column']} -> {$constraint['references']})...\n";
        
        try {
            $sql = "ALTER TABLE {$constraint['table']} 
                    ADD CONSTRAINT {$constraint['name']} 
                    FOREIGN KEY ({$constraint['column']}) 
                    REFERENCES {$constraint['references']} 
                    ON DELETE RESTRICT ON UPDATE CASCADE";
                    
            $pdo->exec($sql);
            echo "  ✅ Success\n";
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "  ✅ Already exists\n";
            } else {
                echo "  ⚠️  Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n🎉 Foreign key constraints completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>