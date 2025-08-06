<?php
/**
 * Traceability Page
 * Forward and backward traceability for lot tracking and customer certificates
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Get database connection
$pdo = $db->connect();

// Handle search
$search_results = [];
$search_type = $_GET['type'] ?? 'lookup';
$search_query = $_GET['search'] ?? '';
$message = '';
$message_type = '';

if ($search_query) {
    try {
        if ($search_type === 'forward') {
            // Forward traceability: From material lot to finished parts
            $stmt = $pdo->prepare("
                SELECT DISTINCT j.job_number, j.part_number, j.quantity_required, j.start_date, j.completion_date,
                       r.part_name, i.lot_number as material_lot, i.supplier_lot,
                       m.material_code, m.material_name, mu.quantity_used,
                       u.full_name as produced_by
                FROM material_usage mu
                JOIN inventory i ON mu.inventory_id = i.id
                JOIN materials m ON i.material_id = m.id
                JOIN jobs j ON mu.job_id = j.id
                LEFT JOIN recipes r ON j.recipe_id = r.id
                LEFT JOIN users u ON j.started_by = u.id
                WHERE i.lot_number LIKE ? OR i.supplier_lot LIKE ?
                ORDER BY j.start_date DESC
            ");
            $stmt->execute(["%$search_query%", "%$search_query%"]);
            $search_results = $stmt->fetchAll();
            
        } elseif ($search_type === 'backward') {
            // Backward traceability: From finished parts to material lots
            $stmt = $pdo->prepare("
                SELECT DISTINCT i.lot_number as material_lot, i.supplier_lot, i.date_received,
                       m.material_code, m.material_name, m.material_type, m.supplier_name,
                       mu.quantity_used, j.job_number, j.start_date,
                       u.full_name as received_by
                FROM jobs j
                JOIN material_usage mu ON j.id = mu.job_id
                JOIN inventory i ON mu.inventory_id = i.id
                JOIN materials m ON i.material_id = m.id
                LEFT JOIN users u ON i.received_by = u.id
                WHERE j.job_number LIKE ? OR j.part_number LIKE ?
                ORDER BY i.date_received ASC
            ");
            $stmt->execute(["%$search_query%", "%$search_query%"]);
            $search_results = $stmt->fetchAll();
            
        } else {
            // General lookup: Search across lots, jobs, and parts
            $stmt = $pdo->prepare("
                SELECT 'inventory' as source, i.id, i.lot_number, i.supplier_lot_number as supplier_lot, i.received_date as date_received,
                       m.material_code, m.material_name, m.material_type, i.current_weight as weight_remaining,
                       NULL as job_number, NULL as part_number, NULL as quantity_required
                FROM inventory i
                JOIN materials m ON i.material_id = m.id
                WHERE i.lot_number LIKE ? OR i.supplier_lot_number LIKE ? OR m.material_code LIKE ?
                
                UNION ALL
                
                SELECT 'job' as source, j.id, NULL as lot_number, NULL as supplier_lot, j.created_date as date_received,
                       NULL as material_code, r.part_name as material_name, 'production' as material_type, 
                       j.quantity_required as weight_remaining,
                       j.job_number, j.part_number, j.quantity_required
                FROM jobs j
                LEFT JOIN recipes r ON j.recipe_id = r.id
                WHERE j.job_number LIKE ? OR j.part_number LIKE ? OR r.part_name LIKE ?
                
                ORDER BY date_received DESC
            ");
            $search_params = array_fill(0, 6, "%$search_query%");
            $stmt->execute($search_params);
            $search_results = $stmt->fetchAll();
        }
        
        if (empty($search_results)) {
            $message = "No results found for: " . htmlspecialchars($search_query);
            $message_type = 'warning';
        }
        
    } catch (Exception $e) {
        $message = 'Search error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Traceability';
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
                    <li><a href="traceability.php" class="active">Traceability</a></li>
                    <?php if ($auth->hasRole(['admin', 'supervisor'])): ?>
                    <li><a href="reports.php">Reports</a></li>
                    <?php endif; ?>
                    <?php if ($auth->hasRole(['admin'])): ?>
                    <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        
        <main>
            <h2><?php echo $page_title; ?> System</h2>
            <p class="traceability-intro">
                Fast lot and part traceability for customer inquiries and ISO compliance. 
                Full forward and backward traceability from raw materials through finished goods.
            </p>
            
            <div class="search-section">
                <div class="search-types">
                    <h3>Search Type</h3>
                    <div class="search-type-buttons">
                        <a href="?type=lookup" class="search-type-btn <?php echo $search_type === 'lookup' ? 'active' : ''; ?>">
                            🔍 General Lookup
                        </a>
                        <a href="?type=forward" class="search-type-btn <?php echo $search_type === 'forward' ? 'active' : ''; ?>">
                            ➡️ Forward Trace
                        </a>
                        <a href="?type=backward" class="search-type-btn <?php echo $search_type === 'backward' ? 'active' : ''; ?>">
                            ⬅️ Backward Trace
                        </a>
                    </div>
                </div>
                
                <div class="search-form">
                    <form method="GET" class="trace-search">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($search_type); ?>">
                        
                        <?php if ($search_type === 'lookup'): ?>
                            <h4>🔍 General Lookup</h4>
                            <p>Search for any lot number, job number, or part number</p>
                            <input type="text" name="search" placeholder="Enter lot number, job number, or part..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                                   
                        <?php elseif ($search_type === 'forward'): ?>
                            <h4>➡️ Forward Traceability</h4>
                            <p>From material lot → show all parts produced</p>
                            <input type="text" name="search" placeholder="Enter material lot number or supplier lot..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                                   
                        <?php elseif ($search_type === 'backward'): ?>
                            <h4>⬅️ Backward Traceability</h4>
                            <p>From finished parts → show all material lots used</p>
                            <input type="text" name="search" placeholder="Enter job number or part number..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="traceability.php" class="btn btn-secondary">Clear</a>
                    </form>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($search_query && !empty($search_results)): ?>
                <div class="search-results">
                    <h3>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h3>
                    
                    <?php if ($search_type === 'forward'): ?>
                        <div class="forward-results">
                            <h4>Parts Produced from Material Lot</h4>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Job Number</th>
                                            <th>Part</th>
                                            <th>Material Used</th>
                                            <th>Quantity Used</th>
                                            <th>Production Date</th>
                                            <th>Produced By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($search_results as $result): ?>
                                            <tr>
                                                <td class="job-number"><?php echo htmlspecialchars($result['job_number']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['part_number']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($result['part_name'] ?? 'Unknown'); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['material_code']); ?></strong><br>
                                                    <small>Lot: <?php echo htmlspecialchars($result['material_lot']); ?></small><br>
                                                    <small>Supplier: <?php echo htmlspecialchars($result['supplier_lot']); ?></small>
                                                </td>
                                                <td><?php echo number_format($result['quantity_used'], 1); ?> lbs</td>
                                                <td><?php echo $result['start_date'] ? date('M j, Y', strtotime($result['start_date'])) : 'Not started'; ?></td>
                                                <td><?php echo htmlspecialchars($result['produced_by'] ?? 'Unknown'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                    <?php elseif ($search_type === 'backward'): ?>
                        <div class="backward-results">
                            <h4>Material Lots Used in Production</h4>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th>Internal Lot</th>
                                            <th>Supplier Lot</th>
                                            <th>Supplier</th>
                                            <th>Date Received</th>
                                            <th>Quantity Used</th>
                                            <th>Received By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($search_results as $result): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['material_code']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($result['material_name']); ?></small>
                                                </td>
                                                <td class="lot-number"><?php echo htmlspecialchars($result['material_lot']); ?></td>
                                                <td><?php echo htmlspecialchars($result['supplier_lot']); ?></td>
                                                <td><?php echo htmlspecialchars($result['supplier_name']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($result['date_received'])); ?></td>
                                                <td><?php echo number_format($result['quantity_used'], 1); ?> lbs</td>
                                                <td><?php echo htmlspecialchars($result['received_by'] ?? 'Unknown'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <div class="general-results">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Item</th>
                                            <th>Details</th>
                                            <th>Date</th>
                                            <th>Status/Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($search_results as $result): ?>
                                            <tr>
                                                <td class="source-type">
                                                    <?php echo $result['source'] === 'inventory' ? '📦 Material' : '🏭 Production'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($result['source'] === 'inventory'): ?>
                                                        <strong><?php echo htmlspecialchars($result['material_code']); ?></strong><br>
                                                        <small><?php echo htmlspecialchars($result['material_name']); ?></small>
                                                    <?php else: ?>
                                                        <strong><?php echo htmlspecialchars($result['job_number']); ?></strong><br>
                                                        <small><?php echo htmlspecialchars($result['part_number']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($result['source'] === 'inventory'): ?>
                                                        <strong>Lot:</strong> <?php echo htmlspecialchars($result['lot_number']); ?><br>
                                                        <strong>Supplier:</strong> <?php echo htmlspecialchars($result['supplier_lot']); ?>
                                                    <?php else: ?>
                                                        <strong>Part:</strong> <?php echo htmlspecialchars($result['material_name']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($result['date_received'])); ?></td>
                                                <td>
                                                    <?php if ($result['source'] === 'inventory'): ?>
                                                        <?php echo number_format($result['weight_remaining'], 1); ?> lbs remaining
                                                    <?php else: ?>
                                                        <?php echo number_format($result['quantity_required']); ?> parts
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['admin', 'supervisor'])): ?>
                        <div class="export-options">
                            <h4>Export Options</h4>
                            <button class="btn btn-secondary" onclick="printResults()">🖨️ Print Report</button>
                            <button class="btn btn-secondary" onclick="exportCSV()">📊 Export CSV</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="traceability-help">
                <h3>Traceability Help</h3>
                <div class="help-sections">
                    <div class="help-section">
                        <h4>🔍 General Lookup</h4>
                        <p>Search for any lot number, job number, or part number to find related information quickly.</p>
                    </div>
                    <div class="help-section">
                        <h4>➡️ Forward Traceability</h4>
                        <p>Start with a material lot number to see all finished parts that were produced using that material.</p>
                        <p><strong>Use for:</strong> Material recalls, quality investigations</p>
                    </div>
                    <div class="help-section">  
                        <h4>⬅️ Backward Traceability</h4>
                        <p>Start with a job or part number to see all material lots that were used in production.</p>
                        <p><strong>Use for:</strong> Customer complaints, root cause analysis</p>
                    </div>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Mini ERP System</p>
        </footer>
    </div>
    
    <script>
        function printResults() {
            window.print();
        }
        
        function exportCSV() {
            // Basic CSV export functionality
            alert('CSV export functionality would be implemented here');
        }
        
        // Auto-focus search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>