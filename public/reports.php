<?php
/**
 * Reports Page  
 * Customer certificates, audit reports, and operational metrics
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Check permissions - Only supervisors and admins can access reports
$auth->requireRole(['admin', 'supervisor']);

// Get database connection
$pdo = $db->connect();

$page_title = 'Reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Mini ERP</title>
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <div class="header-left">
                    <h1>Mini ERP - Manufacturing System</h1>
                    <p class="subtitle">Plastic Injection Molding Traceability</p>
                </div>
                <div class="header-right">
                    <span class="user-info">
                        Welcome, <strong><?php echo htmlspecialchars($current_user['full_name']); ?></strong> 
                        (<?php echo ucfirst(str_replace('_', ' ', $current_user['role'])); ?>)
                    </span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="materials.php">Materials</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="recipes.php">Recipes</a></li>
                    <li><a href="jobs.php">Production Jobs</a></li>
                    <li><a href="traceability.php">Traceability</a></li>
                    <li><a href="reports.php" class="active">Reports</a></li>
                    <?php if ($auth->hasRole(['admin'])): ?>
                    <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        
        <main>
            <h2><?php echo $page_title; ?> & Analytics</h2>
            
            <div class="reports-dashboard">
                <div class="report-categories">
                    
                    <!-- Customer Reports -->
                    <div class="report-category">
                        <h3>📋 Customer Reports</h3>
                        <p>Traceability certificates and compliance documentation</p>
                        
                        <div class="report-items">
                            <div class="report-item">
                                <h4>Traceability Certificate</h4>
                                <p>Generate customer traceability certificates for specific lots or shipments</p>
                                <form class="inline-form">
                                    <input type="text" placeholder="Job number or lot number" name="cert_search">
                                    <button type="submit" class="btn btn-primary">Generate Certificate</button>
                                </form>
                            </div>
                            
                            <div class="report-item">
                                <h4>Material Certificate of Analysis</h4>
                                <p>Material properties and quality data for customer shipments</p>
                                <button class="btn btn-secondary" onclick="alert('Feature coming soon')">Create COA</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ISO Compliance Reports -->
                    <div class="report-category">
                        <h3>🏅 ISO Compliance</h3>
                        <p>Audit trails and compliance documentation</p>
                        
                        <div class="report-items">
                            <div class="report-item">
                                <h4>Audit Log Report</h4>
                                <p>Complete audit trail of system changes and user activities</p>
                                <form class="inline-form">
                                    <input type="date" name="audit_start" title="Start Date">
                                    <input type="date" name="audit_end" title="End Date">
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </form>
                            </div>
                            
                            <div class="report-item">
                                <h4>Quality Control Report</h4>
                                <p>Recipe approvals, quality stops, and corrective actions</p>
                                <button class="btn btn-secondary" onclick="alert('Feature coming soon')">View QC Report</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Production Reports -->
                    <div class="report-category">
                        <h3>🏭 Production Analytics</h3>
                        <p>Production efficiency and material usage metrics</p>
                        
                        <div class="report-items">
                            <div class="report-item">
                                <h4>Material Usage Report</h4>
                                <p>Material consumption by part, job, and time period</p>
                                <form class="inline-form">
                                    <select name="material_filter">
                                        <option value="">All Materials</option>
                                        <option value="base_resin">Base Resins</option>
                                        <option value="color_concentrate">Color Concentrates</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </form>
                            </div>
                            
                            <div class="report-item">
                                <h4>Production Efficiency</h4>
                                <p>Job completion times, throughput, and performance metrics</p>
                                <button class="btn btn-secondary" onclick="alert('Feature coming soon')">View Metrics</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inventory Reports -->
                    <div class="report-category">
                        <h3>📦 Inventory Management</h3>
                        <p>Stock levels, aging, and reorder recommendations</p>
                        
                        <div class="report-items">
                            <div class="report-item">
                                <h4>Current Inventory Status</h4>
                                <p>Real-time inventory levels with FIFO aging</p>
                                <button class="btn btn-primary" onclick="window.location.href='inventory.php'">View Inventory</button>
                            </div>
                            
                            <div class="report-item">
                                <h4>Low Stock Alert</h4>
                                <p>Materials approaching minimum stock levels</p>
                                <button class="btn btn-secondary" onclick="alert('Feature coming soon')">Check Alerts</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats Dashboard -->
                <div class="stats-dashboard">
                    <h3>📊 System Overview</h3>
                    
                    <div class="stats-grid">
                        <?php
                        try {
                            // Get quick stats
                            $stats = [];
                            
                            // Material count
                            $stats['materials'] = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
                            
                            // Active inventory lots
                            $stats['inventory_lots'] = $pdo->query("SELECT COUNT(*) FROM inventory WHERE current_weight > 0")->fetchColumn();
                            
                            // Total inventory weight
                            $stats['total_inventory'] = $pdo->query("SELECT SUM(current_weight) FROM inventory WHERE current_weight > 0")->fetchColumn();
                            
                            // Active jobs
                            $stats['active_jobs'] = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status IN ('pending', 'in_progress')")->fetchColumn();
                            
                            // Approved recipes
                            $stats['recipes'] = $pdo->query("SELECT COUNT(*) FROM recipes WHERE status = 'approved'")->fetchColumn();
                            
                            // Total users
                            $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
                            
                        } catch (Exception $e) {
                            $stats = [
                                'materials' => 0,
                                'inventory_lots' => 0, 
                                'total_inventory' => 0,
                                'active_jobs' => 0,
                                'recipes' => 0,
                                'users' => 0
                            ];
                        }
                        ?>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['materials']); ?></div>
                            <div class="stat-label">Materials</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['inventory_lots']); ?></div>
                            <div class="stat-label">Active Lots</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['total_inventory'], 0); ?></div>
                            <div class="stat-label">Total Lbs</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['active_jobs']); ?></div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['recipes']); ?></div>
                            <div class="stat-label">Approved Recipes</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['users']); ?></div>
                            <div class="stat-label">Active Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Mini ERP System</p>
        </footer>
    </div>
    
    <script>
        // Handle form submissions for report generation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.inline-form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    alert('Report generation functionality will be implemented in Phase 1.4');
                });
            });
        });
    </script>
</body>
</html>