<?php
/**
 * Inventory Management Page
 * Shows current inventory levels, FIFO status, and material receiving
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Check permissions
$can_receive = $auth->hasRole(['admin', 'material_handler']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_receive) {
    if (isset($_POST['receive_material'])) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO inventory (material_id, lot_number, original_weight, current_weight, container_type, 
                                     supplier_lot_number, received_date, received_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['material_id'],
                $_POST['lot_number'],
                $_POST['weight_lbs'],
                $_POST['weight_lbs'], // current_weight = original_weight initially
                $_POST['container_type'],
                $_POST['supplier_lot'],
                $_POST['date_received'],
                $current_user['id']
            ]);
            $message = 'Material received successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error receiving material: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get inventory with material details
try {
    $inventory = $pdo->query("
        SELECT i.*, m.material_code, m.material_name, m.material_type,
               u.full_name as received_by_name,
               CASE WHEN i.current_weight > 0 THEN 'Available' ELSE 'Empty' END as status
        FROM inventory i
        JOIN materials m ON i.material_id = m.id
        LEFT JOIN users u ON i.received_by = u.id
        WHERE i.current_weight > 0
        ORDER BY i.received_date ASC, i.id ASC
    ")->fetchAll();
} catch (Exception $e) {
    $inventory = [];
    $message = 'Error loading inventory: ' . $e->getMessage();
    $message_type = 'error';
}

// Get materials for dropdown
try {
    $materials = $pdo->query("
        SELECT id, material_code, material_name, material_type
        FROM materials 
        ORDER BY material_code
    ")->fetchAll();
} catch (Exception $e) {
    $materials = [];
}

$page_title = 'Inventory';
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
                    <li><a href="inventory.php" class="active">Inventory</a></li>
                    <li><a href="recipes.php">Recipes</a></li>
                    <li><a href="jobs.php">Production Jobs</a></li>
                    <li><a href="traceability.php">Traceability</a></li>
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
            <h2><?php echo $page_title; ?> Management</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($can_receive): ?>
            <div class="form-section">
                <h3>Receive Material</h3>
                <form method="POST" class="receive-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="material_id">Material:</label>
                            <select id="material_id" name="material_id" required>
                                <option value="">Select Material</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?php echo $material['id']; ?>">
                                        <?php echo htmlspecialchars($material['material_code'] . ' - ' . $material['material_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lot_number">Internal Lot Number:</label>
                            <input type="text" id="lot_number" name="lot_number" required 
                                   placeholder="Internal tracking lot">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="supplier_lot">Supplier Lot:</label>
                            <input type="text" id="supplier_lot" name="supplier_lot" required 
                                   placeholder="Supplier's lot number">
                        </div>
                        <div class="form-group">
                            <label for="weight_lbs">Weight (lbs):</label>
                            <input type="number" id="weight_lbs" name="weight_lbs" step="0.1" required 
                                   placeholder="Weight in pounds">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="container_type">Container Type:</label>
                            <select id="container_type" name="container_type" required>
                                <option value="">Select Container</option>
                                <option value="gaylord">Gaylord Box (1320-2200 lbs)</option>
                                <option value="bag_skid">Bag Skid (30-40 bags)</option>
                                <option value="concentrate_box">Concentrate Box (50 lbs)</option>
                                <option value="partial">Partial Container</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_received">Date Received:</label>
                            <input type="date" id="date_received" name="date_received" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <button type="submit" name="receive_material" class="btn btn-primary">Receive Material</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="inventory-summary">
                <h3>Current Inventory (FIFO Order)</h3>
                <p class="fifo-note">🔄 Materials are automatically ordered by receive date for FIFO (First In, First Out) usage</p>
                
                <!-- Search and Filter Section -->
                <div class="inventory-filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search_materials">Search Materials:</label>
                            <input type="text" id="search_materials" placeholder="Search by material code, name, or lot number..." class="search-input">
                        </div>
                        <div class="filter-group">
                            <label for="filter_type">Material Type:</label>
                            <select id="filter_type" class="filter-select">
                                <option value="">All Types</option>
                                <option value="base_resin">Base Resin</option>
                                <option value="color_concentrate">Color Concentrate</option>
                                <option value="rework">Rework Material</option>
                                <option value="additive">Additive</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="filter_status">Status:</label>
                            <select id="filter_status" class="filter-select">
                                <option value="">All Status</option>
                                <option value="Available">Available</option>
                                <option value="Low">Low Stock</option>
                            </select>
                        </div>
                        <button type="button" id="clear_filters" class="btn btn-secondary">Clear Filters</button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Internal Lot</th>
                                <th>Supplier Lot</th>
                                <th>Container</th>
                                <th>Received</th>
                                <th>Original (lbs)</th>
                                <th>Remaining (lbs)</th>
                                <th>Status</th>
                                <th>FIFO Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">No inventory found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory as $index => $item): ?>
                                    <tr class="<?php echo $item['current_weight'] <= 0 ? 'empty' : ''; ?>" 
                                        data-material-type="<?php echo htmlspecialchars($item['material_type']); ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['material_code']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($item['material_name']); ?></small>
                                        </td>
                                        <td class="lot-number"><?php echo htmlspecialchars($item['lot_number']); ?></td>
                                        <td><?php echo htmlspecialchars($item['supplier_lot_number']); ?></td>
                                        <td class="container-type">
                                            <?php echo ucfirst(str_replace('_', ' ', $item['container_type'])); ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($item['received_date'])); ?></td>
                                        <td class="weight"><?php echo number_format($item['original_weight'], 1); ?></td>
                                        <td class="weight remaining">
                                            <?php echo number_format($item['current_weight'], 1); ?>
                                        </td>
                                        <td class="status <?php echo strtolower($item['status']); ?>">
                                            <?php echo $item['status']; ?>
                                        </td>
                                        <td class="fifo-order">#<?php echo $index + 1; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Mini ERP System</p>
        </footer>
    </div>

    <script>
        // Inventory search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search_materials');
            const typeFilter = document.getElementById('filter_type');
            const statusFilter = document.getElementById('filter_status');
            const clearButton = document.getElementById('clear_filters');
            const tableRows = document.querySelectorAll('.data-table tbody tr:not(.no-data)');

            function filterInventory() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedType = typeFilter.value.toLowerCase();
                const selectedStatus = statusFilter.value;
                
                let visibleCount = 0;

                tableRows.forEach(row => {
                    const materialCode = row.querySelector('td:nth-child(1) strong').textContent.toLowerCase();
                    const materialName = row.querySelector('td:nth-child(1) small').textContent.toLowerCase();
                    const lotNumber = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const supplierLot = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const status = row.querySelector('.status').textContent.trim();
                    
                    // Get material type from the row (we'll need to add this as a data attribute)
                    const materialType = row.getAttribute('data-material-type') || '';

                    let matchesSearch = searchTerm === '' || 
                        materialCode.includes(searchTerm) || 
                        materialName.includes(searchTerm) || 
                        lotNumber.includes(searchTerm) ||
                        supplierLot.includes(searchTerm);

                    let matchesType = selectedType === '' || materialType === selectedType;
                    let matchesStatus = selectedStatus === '' || status === selectedStatus;

                    if (matchesSearch && matchesType && matchesStatus) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update FIFO order numbers for visible rows
                let fifoOrder = 1;
                tableRows.forEach(row => {
                    if (row.style.display !== 'none') {
                        const fifoCell = row.querySelector('.fifo-order');
                        if (fifoCell) {
                            fifoCell.textContent = '#' + fifoOrder++;
                        }
                    }
                });

                // Show/hide no results message
                const noDataRow = document.querySelector('.no-data');
                if (visibleCount === 0 && tableRows.length > 0) {
                    if (!document.querySelector('.no-results')) {
                        const tbody = document.querySelector('.data-table tbody');
                        const noResultsRow = document.createElement('tr');
                        noResultsRow.className = 'no-results';
                        noResultsRow.innerHTML = '<td colspan="9" class="no-data">No materials match your search criteria</td>';
                        tbody.appendChild(noResultsRow);
                    }
                } else {
                    const noResultsRow = document.querySelector('.no-results');
                    if (noResultsRow) {
                        noResultsRow.remove();
                    }
                }
            }

            function clearFilters() {
                searchInput.value = '';
                typeFilter.value = '';
                statusFilter.value = '';
                filterInventory();
            }

            // Event listeners
            searchInput.addEventListener('input', filterInventory);
            typeFilter.addEventListener('change', filterInventory);
            statusFilter.addEventListener('change', filterInventory);
            clearButton.addEventListener('click', clearFilters);

            // Add keyboard shortcut for search (Ctrl+F)
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    searchInput.focus();
                }
            });
        });
    </script>
</body>
</html>