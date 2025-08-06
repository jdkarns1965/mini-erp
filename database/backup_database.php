<?php
/**
 * Database Backup System
 * Creates timestamped backups before major changes
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = __DIR__ . "/backups/backup_{$timestamp}.sql";
    
    // Create backups directory if it doesn't exist
    $backup_dir = __DIR__ . '/backups';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    echo "💾 Creating database backup...\n";
    echo "📁 Backup file: $backup_file\n\n";
    
    $handle = fopen($backup_file, 'w');
    if (!$handle) {
        throw new Exception("Cannot create backup file");
    }
    
    // Write header
    fwrite($handle, "-- Mini ERP Database Backup\n");
    fwrite($handle, "-- Created: $timestamp\n");
    fwrite($handle, "-- Environment: " . APP_ENV . "\n\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "Backing up table: $table\n";
        
        // Table structure
        fwrite($handle, "-- Table: $table\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        
        $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        fwrite($handle, $create_table['Create Table'] . ";\n\n");
        
        // Table data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $column_list = '`' . implode('`, `', $columns) . '`';
            
            fwrite($handle, "INSERT INTO `$table` ($column_list) VALUES\n");
            
            $value_strings = [];
            foreach ($rows as $row) {
                $values = array_map(function($value) {
                    return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                }, $row);
                $value_strings[] = '(' . implode(', ', $values) . ')';
            }
            
            fwrite($handle, implode(",\n", $value_strings) . ";\n\n");
        }
    }
    
    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($handle);
    
    $file_size = round(filesize($backup_file) / 1024, 2);
    echo "\n✅ Backup completed successfully!\n";
    echo "📦 File size: {$file_size} KB\n";
    echo "📁 Location: $backup_file\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>