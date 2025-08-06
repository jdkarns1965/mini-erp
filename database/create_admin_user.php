<?php
/**
 * Create Initial Admin User for Production Deployment
 * Run this script once after database migration to create the first admin user
 */

require_once '../config/config.php';
require_once '../config/database.php';

$db = new Database();

try {
    // Check if any users exist
    $userCount = $db->fetch("SELECT COUNT(*) as count FROM users");
    
    if ($userCount['count'] > 0) {
        echo "<h2>Admin User Creation</h2>";
        echo "<p style='color: orange;'>Users already exist in the system. Admin user creation skipped.</p>";
        echo "<p><a href='../public/index.php'>Go to Login Page</a></p>";
        exit;
    }
    
    // Create admin user
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, password, email, full_name, role, status, created_at, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    
    $params = [
        'admin',
        $hashedPassword,
        'admin@company.com',
        'System Administrator',
        'admin',
        'active',
        'system'
    ];
    
    $db->execute($sql, $params);
    
    echo "<h2>Admin User Created Successfully!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>Default Admin Credentials:</strong><br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>admin123</strong><br>";
    echo "<br><span style='color: #721c24; background: #f8d7da; padding: 5px; border-radius: 3px;'>";
    echo "⚠️ SECURITY WARNING: Change this password immediately after first login!</span>";
    echo "</div>";
    echo "<p><a href='../public/index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    
    // Log the admin user creation
    $auditSql = "INSERT INTO audit_log (user_id, action, table_name, record_id, changes, timestamp) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
    
    $adminId = $db->lastInsertId();
    $auditParams = [
        $adminId,
        'CREATE',
        'users',
        $adminId,
        'Initial admin user created during deployment'
    ];
    
    $db->execute($auditSql, $auditParams);
    
    echo "<p><small>Admin user creation logged for audit compliance.</small></p>";
    
} catch (Exception $e) {
    echo "<h2>Error Creating Admin User</h2>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and ensure the migration has been run.</p>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 600px; 
    margin: 50px auto; 
    padding: 20px;
    background: #f8f9fa;
}
h2 { color: #333; }
a { display: inline-block; margin-top: 10px; }
</style>