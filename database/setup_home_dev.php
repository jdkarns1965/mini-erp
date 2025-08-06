<?php
/**
 * Home Development Environment Setup Script
 * One-time setup for new home development environment
 */

echo "🏠 Mini ERP Home Development Environment Setup\n";
echo "================================================\n\n";

// Check if we're in the right directory
if (!file_exists(__DIR__ . '/../config/config.php')) {
    echo "❌ Error: Please run this script from the database/ directory.\n";
    echo "Expected path: /var/www/html/mini-erp/database/\n";
    exit(1);
}

// Check for required files
$required_files = [
    '../config/config.php',
    '../config/database.php',
    'complete_database_export.sql'
];

echo "🔍 Checking required files...\n";
foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "  ✅ $file\n";
    } else {
        echo "  ❌ $file (missing)\n";
        if ($file === 'complete_database_export.sql') {
            echo "     👉 Run 'php export_complete_schema.php' at work first\n";
        }
        $missing_files = true;
    }
}

if (isset($missing_files)) {
    echo "\n❌ Setup cannot continue - missing required files.\n";
    exit(1);
}

// Check environment configuration
echo "\n🔧 Checking environment configuration...\n";

require_once __DIR__ . '/../config/config.php';

if (defined('APP_ENV') && APP_ENV === 'development') {
    echo "  ✅ Environment: " . APP_ENV . "\n";
} else {
    echo "  ⚠️  Environment: " . (defined('APP_ENV') ? APP_ENV : 'undefined') . "\n";
    echo "     👉 Consider setting APP_ENV to 'development' in config.php\n";
}

if (defined('APP_DEBUG') && APP_DEBUG) {
    echo "  ✅ Debug mode: enabled\n";
} else {
    echo "  ⚠️  Debug mode: " . (defined('APP_DEBUG') ? 'disabled' : 'undefined') . "\n";
    echo "     👉 Consider enabling debug mode for development\n";
}

// Test database connection
echo "\n🗄️  Testing database connection...\n";

try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $pdo = $db->connect();
    echo "  ✅ Database connection successful\n";
    
    // Check if database is empty or has tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $table_count = count($tables);
    
    if ($table_count === 0) {
        echo "  📋 Database is empty - ready for import\n";
    } else {
        echo "  📋 Database has $table_count tables\n";
        echo "     ⚠️  Existing data will be replaced during import\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "     👉 Check your database configuration in config/database.php\n";
    exit(1);
}

// Show next steps
echo "\n📋 Setup Status: Ready to proceed\n";
echo "\n🚀 Next Steps:\n";
echo "1. Import database: php sync_to_home.php\n";
echo "2. Run migrations: php migration_tracker.php migrate\n";
echo "3. Test the application in your browser\n";

echo "\n📚 For detailed instructions, see: database/README_DEV_SYNC.md\n";

echo "\n✨ Happy coding!\n";
?>