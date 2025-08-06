<?php
/**
 * Mini ERP Manufacturing Inventory Tracking System
 * Main entry point with authentication
 */

// Load configuration and classes
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

// Initialize authentication
$db = new Database();
$auth = new Auth($db);

// Require authentication for main system
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/mini-erp/public', '', $path);

$page_title = 'Dashboard';

// Include header component
include '../src/includes/header.php';
?>
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
                
                if ($path === '/' || $path === '' || $path === '/index.php') {
                    echo "<h2>Dashboard - Manufacturing Overview</h2>";
                    
                    // Role-specific quick actions
                    echo "<div class='role-info'>";
                    echo "<p><strong>Your Role:</strong> " . ucfirst(str_replace('_', ' ', $current_user['role'])) . "</p>";
                    echo "</div>";
                    
                    echo "<div class='dashboard-widgets'>";
                    
                    // Material Handler actions
                    if ($auth->hasRole(['admin', 'material_handler'])) {
                        echo "<div class='widget'>";
                        echo "<h3>Material Management</h3>";
                        echo "<a href='inventory.php' class='btn'>Receive Materials</a>";
                        echo "<a href='inventory.php' class='btn'>View Inventory</a>";
                        echo "<a href='materials.php' class='btn'>Add New Materials</a>";
                        echo "</div>";
                    }
                    
                    // Supervisor/Quality actions
                    if ($auth->hasRole(['admin', 'supervisor', 'quality_inspector'])) {
                        echo "<div class='widget'>";
                        echo "<h3>Production Control</h3>";
                        echo "<a href='jobs.php' class='btn'>Production Jobs</a>";
                        echo "<a href='recipes.php' class='btn'>Recipe Management</a>";
                        echo "</div>";
                    }
                    
                    // Products Master for authorized users
                    if ($auth->hasRole(['admin', 'supervisor', 'material_handler'])) {
                        echo "<div class='widget'>";
                        echo "<h3>Products Master</h3>";
                        echo "<a href='products.php' class='btn'>View Products</a>";
                        echo "<a href='products.php?action=add' class='btn'>Add New Product</a>";
                        echo "</div>";
                    }
                    
                    // Traceability for all roles
                    echo "<div class='widget'>";
                    echo "<h3>Traceability</h3>";
                    echo "<a href='traceability.php' class='btn'>Lot Lookup</a>";
                    echo "<a href='traceability.php?type=forward' class='btn'>Forward Trace</a>";
                    echo "<a href='traceability.php?type=backward' class='btn'>Backward Trace</a>";
                    echo "</div>";
                    
                    // Admin actions
                    if ($auth->hasRole(['admin'])) {
                        echo "<div class='widget'>";
                        echo "<h3>Administration</h3>";
                        echo "<a href='admin.php?section=users' class='btn'>User Management</a>";
                        echo "<a href='admin.php?section=audit' class='btn'>Audit Log</a>";
                        echo "</div>";
                    }
                    
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='alert alert-error'>Database connection failed: " . $e->getMessage() . "</div>";
                echo "<p>Please ensure your database is configured correctly in config/.env</p>";
            }
            ?>
<?php
// Include footer component
include '../src/includes/footer.php';
?>