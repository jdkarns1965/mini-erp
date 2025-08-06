<?php
/**
 * Database Migration Tracker
 * Tracks which migrations have been applied and runs pending ones
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class MigrationTracker {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureMigrationsTable();
    }
    
    /**
     * Create migrations tracking table if it doesn't exist
     */
    private function ensureMigrationsTable() {
        // Check if migrations table exists with old structure
        try {
            $columns = $this->pdo->query("SHOW COLUMNS FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
            
            // If table exists but has old column names, use them
            if (in_array('migration_name', $columns)) {
                // Table exists with old structure - no changes needed
                return;
            }
        } catch (Exception $e) {
            // Table doesn't exist, create it
        }
        
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_migration_name (migration_name)
            )
        ";
        $this->pdo->exec($sql);
    }
    
    /**
     * Get all available migration files
     */
    public function getAvailableMigrations() {
        $migration_files = [];
        $files = glob(__DIR__ . '/*.php');
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Skip system files
            if (in_array($filename, [
                'migration_tracker.php',
                'export_complete_schema.php', 
                'sync_to_home.php',
                'create_tables.php',
                'migrate.php'
            ])) {
                continue;
            }
            
            // Only include migration files (those that start with add_, fix_, create_, etc.)
            if (preg_match('/^(add_|fix_|create_|alter_|drop_)/', $filename)) {
                $migration_files[] = $filename;
            }
        }
        
        sort($migration_files);
        return $migration_files;
    }
    
    /**
     * Get executed migrations
     */
    public function getExecutedMigrations() {
        try {
            $stmt = $this->pdo->query("SELECT migration_name FROM migrations ORDER BY applied_at");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get pending migrations
     */
    public function getPendingMigrations() {
        $available = $this->getAvailableMigrations();
        $executed = $this->getExecutedMigrations();
        
        return array_diff($available, $executed);
    }
    
    /**
     * Execute a single migration
     */
    public function executeMigration($filename) {
        $filepath = __DIR__ . '/' . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Migration file not found: $filename");
        }
        
        echo "Executing migration: $filename\n";
        
        // Capture output
        ob_start();
        include $filepath;
        $output = ob_get_clean();
        
        echo $output;
        
        // Record migration as executed
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO migrations (migration_name) VALUES (?)");
        $stmt->execute([$filename]);
        
        return true;
    }
    
    /**
     * Run all pending migrations
     */
    public function runPendingMigrations() {
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            echo "✅ No pending migrations.\n";
            return 0;
        }
        
        echo "📋 Found " . count($pending) . " pending migrations:\n";
        foreach ($pending as $migration) {
            echo "  - $migration\n";
        }
        
        echo "\n🚀 Executing migrations...\n\n";
        
        $success_count = 0;
        foreach ($pending as $migration) {
            try {
                $this->executeMigration($migration);
                $success_count++;
                echo "✅ $migration completed\n\n";
            } catch (Exception $e) {
                echo "❌ $migration failed: " . $e->getMessage() . "\n\n";
            }
        }
        
        echo "🎉 Migrations complete: $success_count/" . count($pending) . " successful\n";
        return $success_count;
    }
    
    /**
     * Show migration status
     */
    public function showStatus() {
        $available = $this->getAvailableMigrations();
        $executed = $this->getExecutedMigrations();
        $pending = $this->getPendingMigrations();
        
        echo "📊 Migration Status:\n";
        echo "  Available: " . count($available) . "\n";
        echo "  Executed: " . count($executed) . "\n";
        echo "  Pending: " . count($pending) . "\n\n";
        
        if (!empty($executed)) {
            echo "✅ Executed Migrations:\n";
            foreach ($executed as $migration) {
                echo "  ✓ $migration\n";
            }
            echo "\n";
        }
        
        if (!empty($pending)) {
            echo "⏳ Pending Migrations:\n";
            foreach ($pending as $migration) {
                echo "  ⏳ $migration\n";
            }
            echo "\n";
        }
    }
}

// If called directly, show status and optionally run migrations
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    try {
        $db = new Database();
        $pdo = $db->connect();
        $tracker = new MigrationTracker($pdo);
        
        $command = $argv[1] ?? 'status';
        
        switch ($command) {
            case 'status':
                $tracker->showStatus();
                break;
                
            case 'migrate':
                $tracker->runPendingMigrations();
                break;
                
            default:
                echo "Usage: php migration_tracker.php [status|migrate]\n";
                echo "  status  - Show migration status (default)\n";
                echo "  migrate - Run all pending migrations\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>