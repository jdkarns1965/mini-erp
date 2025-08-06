<?php
/**
 * Database Migration Tool
 * Tracks and applies database schema changes
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Create migrations tracking table
function createMigrationsTable($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

// Get applied migrations
function getAppliedMigrations($pdo) {
    try {
        $stmt = $pdo->query("SELECT migration_name FROM migrations ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

// Apply a migration
function applyMigration($pdo, $migrationFile) {
    $migrationName = basename($migrationFile, '.sql');
    
    echo "Applying migration: $migrationName\n";
    
    $sql = file_get_contents($migrationFile);
    
    // Remove comments and split into statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $pdo->beginTransaction();
    try {
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Record migration as applied
        $stmt = $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
        $stmt->execute([$migrationName]);
        
        $pdo->commit();
        echo "✓ Migration $migrationName applied successfully\n";
    } catch (Exception $e) {
        $pdo->rollback();
        echo "✗ Migration $migrationName failed: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// Main migration runner
function runMigrations() {
    try {
        $db = new Database();
        $pdo = $db->connect();
        
        echo "Mini ERP Database Migration Tool\n";
        echo "=================================\n\n";
        
        // Create migrations table if it doesn't exist
        createMigrationsTable($pdo);
        
        // Get list of applied migrations
        $appliedMigrations = getAppliedMigrations($pdo);
        
        // Get all migration files
        $migrationFiles = glob(__DIR__ . '/migrations/*.sql');
        sort($migrationFiles);
        
        $newMigrations = 0;
        
        foreach ($migrationFiles as $migrationFile) {
            $migrationName = basename($migrationFile, '.sql');
            
            if (!in_array($migrationName, $appliedMigrations)) {
                applyMigration($pdo, $migrationFile);
                $newMigrations++;
            }
        }
        
        if ($newMigrations === 0) {
            echo "✓ Database is up to date. No new migrations to apply.\n";
        } else {
            echo "\n🎉 Applied $newMigrations new migration(s) successfully!\n";
        }
        
    } catch (Exception $e) {
        echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Run migrations if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    runMigrations();
}