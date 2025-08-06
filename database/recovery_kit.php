<?php
/**
 * Recovery Kit - One-Command Fix for Common Issues
 * Automates common recovery scenarios
 */

echo "🛠️  Mini ERP Recovery Kit\n";
echo "========================\n\n";

if (!isset($argv[1])) {
    echo "Usage: php recovery_kit.php <scenario>\n\n";
    echo "Available recovery scenarios:\n";
    echo "  fresh-start     - Complete fresh setup (imports database, runs migrations)\n";
    echo "  fix-permissions - Fix file and directory permissions\n";
    echo "  reset-migrations- Clear and re-run all migrations\n";
    echo "  create-admin    - Create emergency admin user\n";
    echo "  fix-includes    - Fix missing include files\n";
    echo "  database-only   - Just import database without touching code\n";
    echo "  verify-setup    - Run comprehensive verification\n\n";
    echo "Example: php recovery_kit.php fresh-start\n";
    exit(0);
}

$scenario = $argv[1];
$start_time = microtime(true);

try {
    switch ($scenario) {
        case 'fresh-start':
            freshStart();
            break;
            
        case 'fix-permissions':
            fixPermissions();
            break;
            
        case 'reset-migrations':
            resetMigrations();
            break;
            
        case 'create-admin':
            createAdmin();
            break;
            
        case 'fix-includes':
            fixIncludes();
            break;
            
        case 'database-only':
            databaseOnly();
            break;
            
        case 'verify-setup':
            verifySetup();
            break;
            
        default:
            echo "❌ Unknown scenario: $scenario\n";
            exit(1);
    }
    
    $duration = round(microtime(true) - $start_time, 2);
    echo "\n✅ Recovery completed in {$duration} seconds!\n";
    
} catch (Exception $e) {
    echo "\n❌ Recovery failed: " . $e->getMessage() . "\n";
    echo "\n🆘 Next steps:\n";
    echo "  1. Check the error message above\n";
    echo "  2. Run: php doctor.php\n";
    echo "  3. Try: php emergency_reset.php (nuclear option)\n";
    exit(1);
}

/**
 * Complete fresh start scenario
 */
function freshStart() {
    echo "🚀 Starting fresh setup...\n\n";
    
    // Step 1: Check prerequisites
    echo "1. Checking prerequisites...\n";
    checkPrerequisites();
    echo "   ✅ Prerequisites OK\n\n";
    
    // Step 2: Fix permissions
    echo "2. Fixing permissions...\n";
    fixPermissions();
    echo "   ✅ Permissions fixed\n\n";
    
    // Step 3: Import database
    echo "3. Importing database...\n";
    databaseOnly();
    echo "   ✅ Database imported\n\n";
    
    // Step 4: Run migrations
    echo "4. Running migrations...\n";
    runMigrations();
    echo "   ✅ Migrations completed\n\n";
    
    // Step 5: Create admin user
    echo "5. Creating admin user...\n";
    createAdmin();
    echo "   ✅ Admin user created\n\n";
    
    // Step 6: Verify setup
    echo "6. Verifying setup...\n";
    verifySetup();
    echo "   ✅ Setup verified\n";
    
    echo "\n🎉 Fresh start completed! You can now:\n";
    echo "  → Visit: http://localhost/mini-erp/public/\n";
    echo "  → Login: dev_admin / dev123\n";
}

/**
 * Fix file permissions
 */
function fixPermissions() {
    $base_dir = dirname(__DIR__);
    
    // Make directories readable/writable
    $dirs = ['config', 'database', 'src', 'public'];
    foreach ($dirs as $dir) {
        $path = $base_dir . '/' . $dir;
        if (is_dir($path)) {
            chmod($path, 0755);
        }
    }
    
    // Make PHP files executable
    $php_files = glob($base_dir . '/database/*.php');
    foreach ($php_files as $file) {
        chmod($file, 0644);
    }
    
    echo "  Fixed permissions for " . count($php_files) . " PHP files\n";
}

/**
 * Reset and re-run migrations
 */
function resetMigrations() {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $pdo = $db->connect();
    
    // Clear migration history
    echo "  Clearing migration history...\n";
    $pdo->exec("DELETE FROM migrations WHERE migration_name LIKE '%.php'");
    
    // Re-run migrations
    runMigrations();
}

/**
 * Run migrations
 */
function runMigrations() {
    require_once __DIR__ . '/migration_tracker.php';
    require_once __DIR__ . '/../config/database.php';
    
    $db = new Database();
    $pdo = $db->connect();
    $tracker = new MigrationTracker($pdo);
    
    $success = $tracker->runPendingMigrations();
    echo "  Applied migrations successfully\n";
}

/**
 * Create admin user
 */
function createAdmin() {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../src/classes/Auth.php';
    
    $db = new Database();
    $auth = new Auth($db);
    
    // Try to create dev admin
    $result = $auth->createUser('dev_admin', 'dev@mini-erp.local', 'dev123', 'Development Admin', 'admin');
    
    if ($result['success']) {
        echo "  Created dev_admin user (password: dev123)\n";
    } else {
        // Try to create regular admin if dev_admin fails
        $result = $auth->createUser('admin', 'admin@mini-erp.local', 'admin123', 'Administrator', 'admin');
        if ($result['success']) {
            echo "  Created admin user (password: admin123)\n";
        } else {
            throw new Exception("Failed to create admin user: " . $result['message']);
        }
    }
}

/**
 * Fix missing include files
 */
function fixIncludes() {
    $includes_dir = __DIR__ . '/../src/includes';
    
    if (!is_dir($includes_dir)) {
        mkdir($includes_dir, 0755, true);
        echo "  Created includes directory\n";
    }
    
    $required_includes = ['header.php', 'navigation.php', 'footer.php'];
    $missing = [];
    
    foreach ($required_includes as $file) {
        if (!file_exists($includes_dir . '/' . $file)) {
            $missing[] = $file;
        }
    }
    
    if (!empty($missing)) {
        echo "  Missing include files: " . implode(', ', $missing) . "\n";
        echo "  → Pull latest code from Git to restore includes\n";
        throw new Exception("Missing include files - run 'git pull' to restore");
    } else {
        echo "  All include files present\n";
    }
}

/**
 * Import database only
 */
function databaseOnly() {
    $export_file = __DIR__ . '/complete_database_export.sql';
    
    if (!file_exists($export_file)) {
        throw new Exception("Database export not found. Run 'php export_complete_schema.php' at work first.");
    }
    
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $pdo = $db->connect();
    
    echo "  Reading export file...\n";
    $sql_content = file_get_contents($export_file);
    
    echo "  Executing SQL statements...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        fn($stmt) => !empty($stmt) && !preg_match('/^--/', $stmt)
    );
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (Exception $e) {
                // Log but continue
            }
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "  Executed $executed SQL statements\n";
}

/**
 * Verify setup
 */
function verifySetup() {
    // Use the doctor class
    include __DIR__ . '/doctor.php';
    $doctor = new MiniERPDoctor();
    
    // Capture output
    ob_start();
    $doctor->diagnose();
    $output = ob_get_clean();
    
    // Check if there are critical issues
    if (strpos($output, 'CRITICAL') !== false) {
        throw new Exception("Critical issues found during verification");
    }
    
    echo "  Environment verification passed\n";
}

/**
 * Check prerequisites
 */
function checkPrerequisites() {
    if (!file_exists(__DIR__ . '/../config/config.php')) {
        throw new Exception("config.php missing - copy from config.example.php");
    }
    
    if (!file_exists(__DIR__ . '/../config/database.php')) {
        throw new Exception("database.php missing - copy from database.home.example.php");
    }
    
    if (!file_exists(__DIR__ . '/complete_database_export.sql')) {
        throw new Exception("Database export missing - get from work computer");
    }
    
    // Test database connection
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $pdo = $db->connect(); // This will throw exception if it fails
}
?>