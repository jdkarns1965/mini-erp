<?php
/**
 * Database Setup Script for Manufacturing ERP
 * Run this once to create the database schema and default admin user
 */

// Load configuration
require_once '../config/config.php';

echo "Manufacturing ERP Database Setup\n";
echo "================================\n\n";

try {
    // Connect to MySQL server (not specific database)
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '" . DB_NAME . "' created/verified\n";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Read and execute the schema file
    $schema_file = __DIR__ . '/migrations/002_manufacturing_erp_schema.sql';
    
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: $schema_file");
    }
    
    $sql = file_get_contents($schema_file);
    
    // Remove USE statement since we're already connected to the database
    $sql = preg_replace('/USE\s+mini_erp;\s*/', '', $sql);
    
    // Split SQL into individual statements and execute them
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Skip errors for statements that might already exist
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ Executed $executed SQL statements\n";
    
    // Verify tables were created
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Created tables: " . implode(', ', $tables) . "\n";
    
    // Check if admin user exists
    $admin_exists = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
    
    if (!$admin_exists) {
        // Create default admin user
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role, is_active) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', 'admin@company.com', $password_hash, 'System Administrator', 'admin', 1]);
        echo "✓ Created default admin user (username: admin, password: admin123)\n";
    } else {
        echo "✓ Admin user already exists\n";
    }
    
    // Insert sample materials if they don't exist
    $material_count = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
    
    if ($material_count == 0) {
        $materials = [
            ['90006', 'Base Resin 90006', 'base_resin', 'Polymer Supplier Inc'],
            ['GRAY-ABC', 'Gray Concentrate ABC', 'color_concentrate', 'Color Master LLC'],
            ['BLACK-XYZ', 'Black Concentrate XYZ', 'color_concentrate', 'Color Master LLC'],
            ['REWORK-001', 'General Rework Material', 'rework', 'Internal']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO materials (material_code, material_name, material_type, supplier_name, created_by) 
            VALUES (?, ?, ?, ?, 1)
        ");
        
        foreach ($materials as $material) {
            $stmt->execute($material);
        }
        
        echo "✓ Inserted sample materials\n";
    } else {
        echo "✓ Materials already exist\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n\n";
    echo "Next steps:\n";
    echo "1. Visit: http://localhost/mini-erp/public/\n";
    echo "2. Login with: admin / admin123\n";
    echo "3. Change the default password\n";
    echo "4. Create additional users as needed\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    echo "Please check your database configuration in config/.env\n";
    exit(1);
}