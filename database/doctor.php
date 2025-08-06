<?php
/**
 * Mini ERP Doctor - Diagnostic and Recovery Tool
 * Diagnoses and fixes common home development environment issues
 */

require_once __DIR__ . '/../config/config.php';

class MiniERPDoctor {
    private $pdo = null;
    private $issues = [];
    private $fixes_available = [];
    
    public function __construct() {
        echo "🏥 Mini ERP Doctor - Environment Diagnostic Tool\n";
        echo "===============================================\n\n";
    }
    
    /**
     * Run complete diagnostic
     */
    public function diagnose() {
        echo "🔍 Running comprehensive diagnostics...\n\n";
        
        $this->checkEnvironmentConfig();
        $this->checkDatabaseConnection();
        $this->checkFilePermissions();
        $this->checkRequiredFiles();
        $this->checkDatabaseSchema();
        $this->checkMigrationStatus();
        $this->checkDataIntegrity();
        
        $this->reportResults();
    }
    
    /**
     * Check environment configuration
     */
    private function checkEnvironmentConfig() {
        echo "📋 Checking environment configuration...\n";
        
        // Check if config files exist
        if (!file_exists(__DIR__ . '/../config/config.php')) {
            $this->addIssue('CRITICAL', 'config.php missing', 'copy_config_template');
        } else {
            echo "  ✅ config.php exists\n";
        }
        
        if (!file_exists(__DIR__ . '/../config/database.php')) {
            $this->addIssue('CRITICAL', 'database.php missing', 'copy_database_template');
        } else {
            echo "  ✅ database.php exists\n";
        }
        
        // Check environment settings
        if (defined('APP_ENV')) {
            echo "  ✅ APP_ENV: " . APP_ENV . "\n";
            if (APP_ENV !== 'development') {
                $this->addIssue('WARNING', 'Not in development mode', 'set_dev_environment');
            }
        } else {
            $this->addIssue('WARNING', 'APP_ENV not defined', 'set_dev_environment');
        }
        
        echo "\n";
    }
    
    /**
     * Check database connection
     */
    private function checkDatabaseConnection() {
        echo "🗄️  Checking database connection...\n";
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $this->pdo = $db->connect();
            echo "  ✅ Database connection successful\n";
            
            // Check database name
            $db_name = $this->pdo->query("SELECT DATABASE()")->fetchColumn();
            echo "  ✅ Connected to database: $db_name\n";
            
        } catch (Exception $e) {
            $this->addIssue('CRITICAL', 'Database connection failed: ' . $e->getMessage(), 'fix_database_connection');
            echo "  ❌ Database connection failed\n";
        }
        
        echo "\n";
    }
    
    /**
     * Check file permissions
     */
    private function checkFilePermissions() {
        echo "🔒 Checking file permissions...\n";
        
        $paths_to_check = [
            __DIR__ . '/../config',
            __DIR__ . '/../database',
            __DIR__ . '/../src',
            __DIR__ . '/../public'
        ];
        
        foreach ($paths_to_check as $path) {
            if (is_readable($path) && is_writable($path)) {
                echo "  ✅ " . basename($path) . "/ - readable and writable\n";
            } else {
                $this->addIssue('ERROR', "Insufficient permissions on " . basename($path), 'fix_permissions');
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check required files
     */
    private function checkRequiredFiles() {
        echo "📁 Checking required files...\n";
        
        $required_files = [
            'database/complete_database_export.sql' => 'Database export from work',
            'src/includes/header.php' => 'Header component',
            'src/includes/navigation.php' => 'Navigation component',
            'src/includes/footer.php' => 'Footer component',
            'public/css/style.css' => 'Stylesheet'
        ];
        
        foreach ($required_files as $file => $description) {
            $full_path = __DIR__ . '/../' . $file;
            if (file_exists($full_path)) {
                echo "  ✅ $description\n";
            } else {
                $this->addIssue('ERROR', "$description missing ($file)", 'restore_missing_files');
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check database schema
     */
    private function checkDatabaseSchema() {
        echo "🏗️  Checking database schema...\n";
        
        if (!$this->pdo) {
            echo "  ⚠️  Skipping schema check (no database connection)\n\n";
            return;
        }
        
        $expected_tables = [
            'users', 'materials', 'suppliers', 'customers', 'inventory', 
            'products', 'recipes', 'jobs', 'migrations', 'audit_log'
        ];
        
        $existing_tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $missing_tables = array_diff($expected_tables, $existing_tables);
        $extra_tables = array_diff($existing_tables, $expected_tables);
        
        if (empty($missing_tables)) {
            echo "  ✅ All expected tables present (" . count($existing_tables) . " tables)\n";
        } else {
            $this->addIssue('ERROR', 'Missing tables: ' . implode(', ', $missing_tables), 'restore_database_schema');
        }
        
        if (!empty($extra_tables)) {
            echo "  ℹ️  Extra tables found: " . implode(', ', $extra_tables) . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Check migration status
     */
    private function checkMigrationStatus() {
        echo "🔄 Checking migration status...\n";
        
        if (!$this->pdo) {
            echo "  ⚠️  Skipping migration check (no database connection)\n\n";
            return;
        }
        
        try {
            if (!class_exists('MigrationTracker')) {
                require_once __DIR__ . '/migration_tracker.php';
            }
            $tracker = new MigrationTracker($this->pdo);
            
            $pending = $tracker->getPendingMigrations();
            $executed = $tracker->getExecutedMigrations();
            
            echo "  ✅ Migration system operational\n";
            echo "  📊 Executed migrations: " . count($executed) . "\n";
            
            if (!empty($pending)) {
                echo "  ⚠️  Pending migrations: " . count($pending) . "\n";
                $this->addIssue('WARNING', 'Migrations pending', 'run_pending_migrations');
            } else {
                echo "  ✅ No pending migrations\n";
            }
            
        } catch (Exception $e) {
            $this->addIssue('ERROR', 'Migration system error: ' . $e->getMessage(), 'fix_migration_system');
        }
        
        echo "\n";
    }
    
    /**
     * Check data integrity
     */
    private function checkDataIntegrity() {
        echo "🔍 Checking data integrity...\n";
        
        if (!$this->pdo) {
            echo "  ⚠️  Skipping data integrity check (no database connection)\n\n";
            return;
        }
        
        try {
            // Check for orphaned records
            $orphaned_materials = $this->pdo->query("
                SELECT COUNT(*) FROM materials m 
                LEFT JOIN suppliers s ON m.supplier_id = s.id 
                WHERE m.supplier_id IS NOT NULL AND s.id IS NULL
            ")->fetchColumn();
            
            if ($orphaned_materials > 0) {
                $this->addIssue('WARNING', "$orphaned_materials materials with invalid supplier references", 'fix_orphaned_records');
            } else {
                echo "  ✅ Material-supplier references valid\n";
            }
            
            // Check user count
            $user_count = $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            if ($user_count == 0) {
                $this->addIssue('CRITICAL', 'No users in system - cannot log in!', 'create_admin_user');
            } else {
                echo "  ✅ Users present: $user_count\n";
            }
            
            echo "  ✅ Data integrity check completed\n";
            
        } catch (Exception $e) {
            $this->addIssue('ERROR', 'Data integrity check failed: ' . $e->getMessage(), 'restore_from_backup');
        }
        
        echo "\n";
    }
    
    /**
     * Add issue to list
     */
    private function addIssue($severity, $description, $fix_function = null) {
        $this->issues[] = [
            'severity' => $severity,
            'description' => $description,
            'fix' => $fix_function
        ];
        
        if ($fix_function) {
            $this->fixes_available[] = $fix_function;
        }
    }
    
    /**
     * Report diagnostic results
     */
    private function reportResults() {
        echo "📋 DIAGNOSTIC RESULTS\n";
        echo "====================\n\n";
        
        if (empty($this->issues)) {
            echo "🎉 EXCELLENT! No issues found.\n";
            echo "Your home development environment is ready to use!\n\n";
            return;
        }
        
        // Group by severity
        $critical = array_filter($this->issues, fn($i) => $i['severity'] === 'CRITICAL');
        $errors = array_filter($this->issues, fn($i) => $i['severity'] === 'ERROR');
        $warnings = array_filter($this->issues, fn($i) => $i['severity'] === 'WARNING');
        
        if (!empty($critical)) {
            echo "🚨 CRITICAL ISSUES (" . count($critical) . "):\n";
            foreach ($critical as $issue) {
                echo "  ❌ " . $issue['description'] . "\n";
            }
            echo "\n";
        }
        
        if (!empty($errors)) {
            echo "⚠️  ERRORS (" . count($errors) . "):\n";
            foreach ($errors as $issue) {
                echo "  🔴 " . $issue['description'] . "\n";
            }
            echo "\n";
        }
        
        if (!empty($warnings)) {
            echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
            foreach ($warnings as $issue) {
                echo "  🟡 " . $issue['description'] . "\n";
            }
            echo "\n";
        }
        
        // Show available fixes
        if (!empty($this->fixes_available)) {
            echo "🔧 AVAILABLE AUTOMATIC FIXES:\n";
            echo "Run: php doctor.php --fix\n\n";
        }
        
        echo "💡 For detailed troubleshooting, see database/TROUBLESHOOTING.md\n";
    }
    
    /**
     * Apply automatic fixes
     */
    public function applyFixes() {
        echo "🔧 Applying automatic fixes...\n\n";
        
        foreach ($this->issues as $issue) {
            if ($issue['fix'] && method_exists($this, $issue['fix'])) {
                echo "Fixing: " . $issue['description'] . "\n";
                try {
                    $this->{$issue['fix']}();
                    echo "  ✅ Fixed successfully\n\n";
                } catch (Exception $e) {
                    echo "  ❌ Fix failed: " . $e->getMessage() . "\n\n";
                }
            }
        }
    }
    
    // Automatic fix functions
    private function copy_config_template() {
        copy(__DIR__ . '/../config/config.example.php', __DIR__ . '/../config/config.php');
    }
    
    private function copy_database_template() {
        copy(__DIR__ . '/../config/database.home.example.php', __DIR__ . '/../config/database.php');
        echo "  📝 Please edit config/database.php with your database settings\n";
    }
    
    private function run_pending_migrations() {
        if (!class_exists('MigrationTracker')) {
            include_once __DIR__ . '/migration_tracker.php';
        }
        $tracker = new MigrationTracker($this->pdo);
        $tracker->runPendingMigrations();
    }
    
    private function create_admin_user() {
        require_once __DIR__ . '/../src/classes/Auth.php';
        $auth = new Auth(new Database());
        $result = $auth->createUser('admin', 'admin@mini-erp.local', 'admin123', 'System Administrator', 'admin');
        if ($result['success']) {
            echo "  👤 Created admin user (username: admin, password: admin123)\n";
        }
    }
}

// Command line usage
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $doctor = new MiniERPDoctor();
    
    if (isset($argv[1]) && $argv[1] === '--fix') {
        $doctor->diagnose();
        $doctor->applyFixes();
    } else {
        $doctor->diagnose();
    }
}
?>