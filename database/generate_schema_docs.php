<?php
/**
 * Database Schema Documentation Generator
 * Creates comprehensive documentation of the current database schema
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    $doc_file = __DIR__ . '/SCHEMA_DOCUMENTATION.md';
    $handle = fopen($doc_file, 'w');
    
    if (!$handle) {
        throw new Exception("Cannot create documentation file");
    }
    
    echo "📚 Generating schema documentation...\n\n";
    
    // Write header
    fwrite($handle, "# Mini ERP Database Schema Documentation\n\n");
    fwrite($handle, "Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "Environment: " . APP_ENV . "\n\n");
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    fwrite($handle, "## Database Overview\n\n");
    fwrite($handle, "Total tables: " . count($tables) . "\n\n");
    
    // Table of contents
    fwrite($handle, "## Tables\n\n");
    foreach ($tables as $table) {
        fwrite($handle, "- [$table](#$table)\n");
    }
    fwrite($handle, "\n");
    
    // Document each table
    foreach ($tables as $table) {
        echo "Documenting table: $table\n";
        
        fwrite($handle, "## $table\n\n");
        
        // Get table comment if exists
        $table_info = $pdo->query("SHOW TABLE STATUS LIKE '$table'")->fetch();
        if ($table_info && $table_info['Comment']) {
            fwrite($handle, "**Purpose:** " . $table_info['Comment'] . "\n\n");
        }
        
        // Get columns
        $columns = $pdo->query("SHOW FULL COLUMNS FROM `$table`")->fetchAll();
        
        fwrite($handle, "### Columns\n\n");
        fwrite($handle, "| Column | Type | Null | Default | Key | Comment |\n");
        fwrite($handle, "|--------|------|------|---------|-----|----------|\n");
        
        foreach ($columns as $column) {
            $null_text = $column['Null'] === 'YES' ? 'Yes' : 'No';
            $default = $column['Default'] ?? 'NULL';
            $key = $column['Key'] ?: '';
            $comment = $column['Comment'] ?: '';
            
            fwrite($handle, "| {$column['Field']} | {$column['Type']} | $null_text | $default | $key | $comment |\n");
        }
        
        // Get indexes
        $indexes = $pdo->query("SHOW INDEXES FROM `$table`")->fetchAll();
        if (!empty($indexes)) {
            fwrite($handle, "\n### Indexes\n\n");
            fwrite($handle, "| Key Name | Columns | Unique |\n");
            fwrite($handle, "|----------|---------|--------|\n");
            
            $index_groups = [];
            foreach ($indexes as $index) {
                $index_groups[$index['Key_name']][] = $index['Column_name'];
            }
            
            foreach ($index_groups as $key_name => $columns) {
                $unique = $indexes[0]['Non_unique'] ? 'No' : 'Yes';
                fwrite($handle, "| $key_name | " . implode(', ', $columns) . " | $unique |\n");
            }
        }
        
        // Get foreign keys
        $foreign_keys = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$table' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll();
        
        if (!empty($foreign_keys)) {
            fwrite($handle, "\n### Foreign Keys\n\n");
            fwrite($handle, "| Constraint | Column | References |\n");
            fwrite($handle, "|------------|--------|-----------|\n");
            
            foreach ($foreign_keys as $fk) {
                fwrite($handle, "| {$fk['CONSTRAINT_NAME']} | {$fk['COLUMN_NAME']} | {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']} |\n");
            }
        }
        
        // Get row count
        $row_count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        fwrite($handle, "\n**Current row count:** $row_count\n\n");
        
        fwrite($handle, "---\n\n");
    }
    
    fclose($handle);
    
    echo "\n✅ Schema documentation generated!\n";
    echo "📁 File: $doc_file\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>