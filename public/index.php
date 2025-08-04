<?php
/**
 * Mini ERP Inventory Tracking System
 * Main entry point
 */

// Load configuration
require_once '../config/config.php';
require_once '../config/database.php';

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/mini-erp/public', '', $path);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini ERP - Inventory Tracking System</title>
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <h1>Mini ERP - Inventory Tracking System</h1>
            <nav>
                <ul>
                    <li><a href="/">Dashboard</a></li>
                    <li><a href="/products">Products</a></li>
                    <li><a href="/categories">Categories</a></li>
                    <li><a href="/suppliers">Suppliers</a></li>
                    <li><a href="/stock">Stock Movements</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <?php
            try {
                // Test database connection
                $db = new Database();
                $pdo = $db->connect();
                echo "<div class='alert alert-success'>✓ Database connection successful</div>";
                
                // Show current environment
                echo "<div class='info-panel'>";
                echo "<h2>System Status</h2>";
                echo "<p><strong>Environment:</strong> " . APP_ENV . "</p>";
                echo "<p><strong>Debug Mode:</strong> " . (APP_DEBUG ? 'Enabled' : 'Disabled') . "</p>";
                echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
                echo "<p><strong>Current Path:</strong> " . $path . "</p>";
                echo "</div>";
                
                if ($path === '/' || $path === '') {
                    echo "<h2>Welcome to Mini ERP</h2>";
                    echo "<p>Your inventory tracking system is ready!</p>";
                    echo "<div class='quick-actions'>";
                    echo "<a href='/products' class='btn'>Manage Products</a>";
                    echo "<a href='/stock' class='btn'>Stock Movements</a>";
                    echo "<a href='/reports' class='btn'>Reports</a>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='alert alert-error'>Database connection failed: " . $e->getMessage() . "</div>";
                echo "<p>Please ensure your database is configured correctly in config/.env</p>";
            }
            ?>
        </main>
        
        <footer>
            <p>&copy; 2025 Mini ERP System</p>
        </footer>
    </div>
</body>
</html>