<?php
/**
 * Complete Database Schema and Data Export
 * Creates a comprehensive SQL dump for home dev environment setup
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    $output_file = __DIR__ . '/complete_database_export.sql';
    $handle = fopen($output_file, 'w');
    
    if (!$handle) {
        throw new Exception("Cannot create export file: $output_file");
    }
    
    // Write header
    fwrite($handle, "-- Mini ERP Database Complete Export\n");
    fwrite($handle, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "-- Use this file to set up your home development environment\n\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Exporting database schema and data...\n\n";
    
    foreach ($tables as $table) {
        echo "Processing table: $table\n";
        
        // Get table structure
        fwrite($handle, "-- Table structure for table `$table`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        
        $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        fwrite($handle, $create_table['Create Table'] . ";\n\n");
        
        // Get table data
        $row_count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        
        if ($row_count > 0) {
            echo "  - Exporting $row_count rows\n";
            fwrite($handle, "-- Dumping data for table `$table`\n");
            
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                // Get column names
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';
                
                fwrite($handle, "INSERT INTO `$table` ($column_list) VALUES\n");
                
                $value_strings = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $value_strings[] = '(' . implode(', ', $values) . ')';
                }
                
                fwrite($handle, implode(",\n", $value_strings) . ";\n\n");
            }
        } else {
            echo "  - Table is empty\n";
            fwrite($handle, "-- No data to dump for table `$table`\n\n");
        }
    }
    
    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n\n");
    fwrite($handle, "-- Export completed successfully!\n");
    
    fclose($handle);
    
    echo "\n✅ Database export completed successfully!\n";
    echo "📁 Export saved to: $output_file\n";
    echo "📊 Tables exported: " . count($tables) . "\n";
    
    // Show file size
    $file_size = filesize($output_file);
    $file_size_mb = round($file_size / 1024 / 1024, 2);
    echo "📦 File size: {$file_size_mb} MB\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>