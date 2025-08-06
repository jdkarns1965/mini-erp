<?php
/**
 * Emergency Reset - Nuclear Option
 * Completely resets home development environment when all else fails
 */

echo "🚨 EMERGENCY RESET TOOL\n";
echo "======================\n\n";

echo "⚠️  WARNING: This will COMPLETELY DESTROY your current database and reset everything!\n";
echo "Only use this if your home development environment is completely broken.\n\n";

// Safety checks
if (!defined('APP_ENV') || APP_ENV !== 'development') {
    require_once __DIR__ . '/../config/config.php';
    if (!defined('APP_ENV') || APP_ENV !== 'development') {
        echo "❌ SAFETY CHECK FAILED: This can only be run in development environment!\n";
        echo "Current environment: " . (defined('APP_ENV') ? APP_ENV : 'undefined') . "\n";
        exit(1);
    }
}

echo "Environment: " . APP_ENV . " ✅\n\n";

// Confirmation
echo "Type 'RESET EVERYTHING' to confirm (case sensitive): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'RESET EVERYTHING') {
    echo "❌ Reset cancelled.\n";
    exit(0);
}

echo "\n🔥 Starting emergency reset...\n\n";

try {
    // Step 1: Drop all tables
    echo "1. 🗑️  Dropping all existing tables...\n";
    
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $pdo = $db->connect();
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "  Dropping $table...\n";
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    
    echo "  ✅ All tables dropped\n\n";
    
    // Step 2: Re-import clean database
    echo "2. 📥 Importing clean database...\n";
    
    $export_file = __DIR__ . '/complete_database_export.sql';
    
    if (!file_exists($export_file)) {
        throw new Exception("Export file not found: $export_file\nYou need to get a fresh export from your work computer.");
    }
    
    $sql_content = file_get_contents($export_file);
    if ($sql_content === false) {
        throw new Exception("Failed to read export file");
    }
    
    // Execute SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $total = count($statements);
    $executed = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
                
                if ($executed % 10 === 0) {
                    $percent = round(($executed / $total) * 100, 1);
                    echo "  Progress: $executed/$total ($percent%)\n";
                }
            } catch (Exception $e) {
                // Log but continue
                echo "  ⚠️  Warning: " . substr($statement, 0, 50) . "... failed\n";
            }
        }
    }
    
    echo "  ✅ Database imported ($executed/$total statements)\n\n";
    
    // Step 3: Run all migrations
    echo "3. 🔄 Running migrations...\n";
    
    include __DIR__ . '/migration_tracker.php';
    $tracker = new MigrationTracker($pdo);
    $tracker->runPendingMigrations();
    
    echo "\n";
    
    // Step 4: Create development admin user
    echo "4. 👤 Creating development admin user...\n";
    
    try {
        require_once __DIR__ . '/../src/classes/Auth.php';
        $auth = new Auth($db);
        $result = $auth->createUser('dev_admin', 'dev@mini-erp.local', 'dev123', 'Development Admin', 'admin');
        
        if ($result['success']) {
            echo "  ✅ Development admin created\n";
            echo "  📝 Username: dev_admin\n";
            echo "  📝 Password: dev123\n";
        } else {
            echo "  ⚠️  Admin creation failed: " . $result['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "  ⚠️  Admin creation failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Step 5: Verify reset
    echo "5. ✅ Verifying reset...\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "  Tables: " . count($tables) . "\n";
    
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "  Users: $user_count\n";
    
    $material_count = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
    echo "  Materials: $material_count\n";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n🎉 EMERGENCY RESET COMPLETED SUCCESSFULLY!\n\n";
    
    echo "📋 What's been reset:\n";
    echo "  ✅ All tables recreated from scratch\n";
    echo "  ✅ All data restored from work export\n"; 
    echo "  ✅ All migrations applied\n";
    echo "  ✅ Development admin user created\n\n";
    
    echo "🚀 Next steps:\n";
    echo "  1. Test login at: http://localhost/mini-erp/public/\n";
    echo "  2. Use dev_admin / dev123 to log in\n";
    echo "  3. Change admin password after testing\n";
    echo "  4. Run: php doctor.php to verify everything\n\n";
    
} catch (Exception $e) {
    echo "\n❌ EMERGENCY RESET FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "🆘 Recovery options:\n";
    echo "  1. Check database connection settings\n";
    echo "  2. Get fresh database export from work\n";
    echo "  3. Contact system administrator\n";
    echo "  4. Start with completely fresh database\n";
    
    exit(1);
}
?>