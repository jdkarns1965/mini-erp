<?php
/**
 * Database Sync to Home Development Environment
 * Imports the complete database export and applies any pending migrations
 */

require_once __DIR__ . '/../config/config.php';

// Check if we're in development environment
if (APP_ENV !== 'development') {
    echo "❌ This script should only be run in development environment!\n";
    echo "Current environment: " . APP_ENV . "\n";
    exit(1);
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    echo "🏠 Setting up home development environment...\n\n";
    
    // Check if complete export file exists
    $export_file = __DIR__ . '/complete_database_export.sql';
    
    if (!file_exists($export_file)) {
        echo "❌ Export file not found: $export_file\n";
        echo "Please run export_complete_schema.php first from your work environment.\n";
        exit(1);
    }
    
    echo "📁 Found export file: $export_file\n";
    
    // Get file info
    $file_size = filesize($export_file);
    $file_size_mb = round($file_size / 1024 / 1024, 2);
    $file_date = date('Y-m-d H:i:s', filemtime($export_file));
    
    echo "📦 File size: {$file_size_mb} MB\n";
    echo "📅 Export date: $file_date\n\n";
    
    // Confirm import
    echo "⚠️  This will completely replace your local database!\n";
    echo "Are you sure you want to continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) !== 'yes') {
        echo "❌ Import cancelled.\n";
        exit(1);
    }
    
    echo "\n🗄️  Importing database...\n";
    
    // Read and execute SQL file
    $sql_content = file_get_contents($export_file);
    
    if ($sql_content === false) {
        throw new Exception("Failed to read export file");
    }
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $total_statements = count($statements);
    $executed = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
                
                // Show progress every 10 statements
                if ($executed % 10 === 0) {
                    $percent = round(($executed / $total_statements) * 100, 1);
                    echo "  Progress: $executed/$total_statements ($percent%)\n";
                }
            } catch (Exception $e) {
                echo "⚠️  Warning: Failed to execute statement: " . substr($statement, 0, 100) . "...\n";
                echo "   Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Database import completed!\n";
    echo "📊 Executed $executed/$total_statements SQL statements\n";
    
    // Verify import by checking key tables
    echo "\n🔍 Verifying import...\n";
    
    $key_tables = ['users', 'materials', 'suppliers', 'customers', 'inventory'];
    
    foreach ($key_tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "  ✅ $table: $count records\n";
        } catch (Exception $e) {
            echo "  ❌ $table: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Home development environment setup complete!\n";
    echo "You can now work with the same data as your work environment.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>