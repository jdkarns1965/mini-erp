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
            $pdo->beginTransaction();
            
            $entries_count = (int)($_POST['entries_count'] ?? 1);
            $processed_entries = 0;
            
            $stmt = $pdo->prepare("
                INSERT INTO inventory (material_id, lot_number, quantity_received, quantity_available, 
                                     supplier_lot_number, location, received_date, received_by, container_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            for ($i = 1; $i <= $entries_count; $i++) {
                // Skip empty entries
                if (empty($_POST["weight_lbs_$i"])) {
                    continue;
                }
                
                // Determine lot number - manufacturer assigned only
                $lot_number = '';
                if (!empty($_POST['use_same_lot']) && !empty($_POST['base_lot_number'])) {
                    // All containers have the same manufacturer lot number
                    $lot_number = $_POST['base_lot_number'];
                } else {
                    // Each container has its own manufacturer lot number
                    $lot_number = $_POST["lot_number_$i"] ?? '';
                }
                
                // Skip if no lot number is determined
                if (empty($lot_number)) {
                    continue;
                }
                
                $stmt->execute([
                    $_POST['material_id'],
                    $lot_number,
                    $_POST["weight_lbs_$i"],
                    $_POST["weight_lbs_$i"], // quantity_available = quantity_received initially
                    $_POST["supplier_lot_$i"] ?? $_POST['supplier_lot_base'],
                    $_POST["location_$i"] ?? $_POST['location_base'],
                    $_POST['date_received'],
                    $current_user['id'],
                    $_POST["container_type_$i"] ?? 'gaylord'
                ]);
                $processed_entries++;
            }
            
            $pdo->commit();
            $message = "Successfully received $processed_entries entries of material!";
            $message_type = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
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
               CASE WHEN i.quantity_available > 0 THEN 'Available' ELSE 'Empty' END as status
        FROM inventory i
        JOIN materials m ON i.material_id = m.id
        LEFT JOIN users u ON i.received_by = u.id
        WHERE i.quantity_available > 0
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

// Include header component
include '../src/includes/header.php';
?>
            <h2><?php echo $page_title; ?> Management</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($can_receive): ?>
            <div class="form-section">
                <h3>Receive Material</h3>
                <form method="POST" class="receive-form" id="receive-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="material_code_input">Material Code (Type or Scan):</label>
                            <input type="text" id="material_code_input" name="material_code_input" 
                                   placeholder="e.g., 90006" 
                                   title="Type or scan material code">
                            <small>Type or scan the material code to auto-select</small>
                        </div>
                        <div class="form-group">
                            <label for="material_id">Material:</label>
                            <select id="material_id" name="material_id" required>
                                <option value="">Select Material</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?php echo $material['id']; ?>" data-code="<?php echo strtoupper($material['material_code']); ?>">
                                        <?php echo htmlspecialchars($material['material_code'] . ' - ' . $material['material_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lot_number">Manufacturer Lot Number:</label>
                            <input type="text" id="lot_number" name="lot_number" required 
                                   placeholder="Lot number from container/bag label">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="supplier_lot_base">Supplier Lot (Base):</label>
                            <input type="text" id="supplier_lot_base" name="supplier_lot_base" 
                                   placeholder="Base supplier lot number">
                            <small>Will be used for all entries unless overridden</small>
                        </div>
                        <div class="form-group">
                            <label for="location_base">Location (Base):</label>
                            <input type="text" id="location_base" name="location_base" 
                                   placeholder="Base storage location">
                            <small>Will be used for all entries unless overridden</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_received">Date Received:</label>
                            <input type="date" id="date_received" name="date_received" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="entries_count">Number of Entries:</label>
                            <select id="entries_count" name="entries_count" onchange="updateEntryFields()">
                                <option value="1">1 Entry</option>
                                <option value="2">2 Entries</option>
                                <option value="3">3 Entries</option>
                                <option value="4">4 Entries</option>
                                <option value="5">5 Entries</option>
                                <option value="6">6 Entries</option>
                                <option value="7">7 Entries</option>
                                <option value="8">8 Entries</option>
                                <option value="9">9 Entries</option>
                                <option value="10">10 Entries</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row" id="multi-entry-options" style="display: none;">
                        <div class="form-group">
                            <label for="base_lot_number">Manufacturer Lot Number:</label>
                            <input type="text" id="base_lot_number" name="base_lot_number" 
                                   placeholder="Lot number from manufacturer label">
                            <small class="form-help">Enter the lot number printed/labeled on containers by the manufacturer</small>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" id="use_same_lot" name="use_same_lot" value="1" onchange="toggleLotNumberMode()">
                                    All containers have the same manufacturer lot number
                                </label>
                                <small class="form-help">Check this if all containers/bags have identical manufacturer lot numbers</small>
                            </div>
                        </div>
                    </div>
                    
                    <div id="entries-container">
                        <!-- Dynamic entry fields will be inserted here -->
                    </div>
                    
                    <button type="submit" name="receive_material" class="btn btn-primary">Receive All Entries</button>
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
                                <th>Location</th>
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
                                    <tr class="<?php echo $item['quantity_available'] <= 0 ? 'empty' : ''; ?>" 
                                        data-material-type="<?php echo htmlspecialchars($item['material_type']); ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['material_code']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($item['material_name']); ?></small>
                                        </td>
                                        <td class="lot-number"><?php echo htmlspecialchars($item['lot_number']); ?></td>
                                        <td><?php echo htmlspecialchars($item['supplier_lot_number']); ?></td>
                                        <td class="location">
                                            <?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($item['received_date'])); ?></td>
                                        <td class="weight"><?php echo number_format($item['quantity_received'], 1); ?></td>
                                        <td class="weight remaining">
                                            <?php echo number_format($item['quantity_available'], 1); ?>
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

        // Dynamic Entry Fields Management
        function updateEntryFields() {
            const entriesCount = parseInt(document.getElementById('entries_count').value);
            const container = document.getElementById('entries-container');
            const multiOptions = document.getElementById('multi-entry-options');
            
            // Show/hide multi-entry options
            if (entriesCount > 1) {
                multiOptions.style.display = 'block';
            } else {
                multiOptions.style.display = 'none';
                // Reset checkboxes when going back to single entry
                document.getElementById('use_same_lot').checked = false;
                document.getElementById('base_lot_number').value = '';
            }
            
            container.innerHTML = '';
            
            for (let i = 1; i <= entriesCount; i++) {
                const entryDiv = document.createElement('div');
                entryDiv.className = 'entry-section';
                entryDiv.innerHTML = `
                    <h4>Entry #${i}</h4>
                    <div class="form-row">
                        <div class="form-group lot-number-group">
                            <label for="lot_number_${i}">Manufacturer Lot Number:</label>
                            <input type="text" id="lot_number_${i}" name="lot_number_${i}" 
                                   placeholder="Lot number from container/bag label">
                            <small class="lot-help">Enter the lot number printed on this specific container/bag</small>
                        </div>
                        <div class="form-group">
                            <label for="supplier_lot_${i}">Supplier Lot (Override):</label>
                            <input type="text" id="supplier_lot_${i}" name="supplier_lot_${i}" 
                                   placeholder="Leave empty to use base">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight_lbs_${i}">Weight (lbs):</label>
                            <input type="number" id="weight_lbs_${i}" name="weight_lbs_${i}" step="0.1" required 
                                   placeholder="Weight in pounds">
                        </div>
                        <div class="form-group">
                            <label for="container_type_${i}">Container Type:</label>
                            <select id="container_type_${i}" name="container_type_${i}">
                                <option value="gaylord">Gaylord Box</option>
                                <option value="bag_skid">Bag Skid</option>
                                <option value="partial">Partial Container</option>
                                <option value="box_50lb">50lb Box</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location_${i}">Location (Override):</label>
                            <input type="text" id="location_${i}" name="location_${i}" 
                                   placeholder="Leave empty to use base">
                        </div>
                        <div class="form-group">
                            <!-- Spacer -->
                        </div>
                    </div>
                `;
                container.appendChild(entryDiv);
            }
            
            // Auto-focus appropriate field
            setTimeout(() => {
                const baseLotField = document.getElementById('base_lot_number');
                const firstWeightField = document.getElementById('weight_lbs_1');
                const firstLotField = document.getElementById('lot_number_1');
                
                if (entriesCount > 1 && baseLotField && baseLotField.offsetParent !== null) {
                    baseLotField.focus();
                } else if (entriesCount === 1 && firstLotField) {
                    firstLotField.focus();
                } else if (firstWeightField) {
                    firstWeightField.focus();
                }
            }, 100);
        }
        
        function toggleLotNumberMode() {
            const useSameLot = document.getElementById('use_same_lot').checked;
            const baseLotField = document.getElementById('base_lot_number');
            const lotGroups = document.querySelectorAll('.lot-number-group');
            const lotHelps = document.querySelectorAll('.lot-help');
            
            if (useSameLot) {
                baseLotField.required = true;
                baseLotField.style.backgroundColor = '#fff3cd';
                baseLotField.style.borderColor = '#ffc107';
                
                // Update help text
                lotHelps.forEach(help => {
                    help.textContent = 'Individual lot fields will be ignored - all entries will use the shared manufacturer lot';
                    help.style.color = '#856404';
                });
                
                // Disable individual lot fields
                lotGroups.forEach((group, index) => {
                    const input = group.querySelector('input');
                    input.style.backgroundColor = '#f8f9fa';
                    input.style.color = '#6c757d';
                    input.disabled = true;
                    input.required = false;
                });
                
            } else {
                baseLotField.required = false;
                baseLotField.style.backgroundColor = '';
                baseLotField.style.borderColor = '';
                
                // Reset help text
                lotHelps.forEach((help, index) => {
                    const baseLot = baseLotField.value.trim();
                    if (baseLot) {
                        help.textContent = `Will use manufacturer lot: ${baseLot}`;
                        help.style.color = '#28a745';
                    } else {
                        help.textContent = 'Enter the lot number printed on this specific container/bag';
                        help.style.color = '#6c757d';
                    }
                });
                
                // Enable individual lot fields
                lotGroups.forEach(group => {
                    const input = group.querySelector('input');
                    input.style.backgroundColor = '';
                    input.style.color = '';
                    input.disabled = false;
                    input.required = false; // Will be handled by PHP logic
                });
            }
        }
        
        // Update lot preview when manufacturer lot changes
        function updateLotPreview() {
            const manufacturerLot = document.getElementById('base_lot_number').value.trim();
            const useSameLot = document.getElementById('use_same_lot').checked;
            const lotHelps = document.querySelectorAll('.lot-help');
            
            if (!useSameLot && manufacturerLot) {
                lotHelps.forEach((help, index) => {
                    help.textContent = `Will use manufacturer lot: ${manufacturerLot}`;
                    help.style.color = '#28a745';
                });
            } else if (!useSameLot) {
                lotHelps.forEach(help => {
                    help.textContent = 'Enter the lot number printed on this specific container/bag';
                    help.style.color = '#6c757d';
                });
            }
        }
        
        // Initialize with 1 entry
        document.addEventListener('DOMContentLoaded', function() {
            updateEntryFields();
            
            // Add event listener for base lot number changes
            const baseLotField = document.getElementById('base_lot_number');
            if (baseLotField) {
                baseLotField.addEventListener('input', updateLotPreview);
            }
        });
        
        // Material Code Auto-Selection
        document.addEventListener('DOMContentLoaded', function() {
            const materialCodeInput = document.getElementById('material_code_input');
            const materialSelect = document.getElementById('material_id');
            
            if (materialCodeInput && materialSelect) {
                
                function selectMaterialByCode(code) {
                    const upperCode = code.toUpperCase().trim();
                    
                    // Find matching option by data-code attribute
                    const options = materialSelect.querySelectorAll('option[data-code]');
                    let found = false;
                    
                    for (let option of options) {
                        if (option.getAttribute('data-code') === upperCode) {
                            materialSelect.value = option.value;
                            found = true;
                            
                            // Visual feedback - highlight the select briefly
                            materialSelect.style.backgroundColor = '#d4edda';
                            materialSelect.style.borderColor = '#28a745';
                            
                            setTimeout(() => {
                                materialSelect.style.backgroundColor = '';
                                materialSelect.style.borderColor = '';
                            }, 1000);
                            
                            break;
                        }
                    }
                    
                    if (!found && code.trim() !== '') {
                        // Visual feedback - show not found
                        materialCodeInput.style.backgroundColor = '#f8d7da';
                        materialCodeInput.style.borderColor = '#dc3545';
                        materialSelect.value = '';
                        
                        setTimeout(() => {
                            materialCodeInput.style.backgroundColor = '';
                            materialCodeInput.style.borderColor = '';
                        }, 1000);
                    }
                    
                    return found;
                }
                
                // Auto-select on input (with debouncing for barcode scanners)
                let timeout;
                materialCodeInput.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        selectMaterialByCode(this.value);
                    }, 300); // 300ms delay to handle fast barcode scanner input
                });
                
                // Also trigger on Enter key (common for barcode scanners)
                materialCodeInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(timeout);
                        selectMaterialByCode(this.value);
                        
                        // Move focus to next field
                        document.getElementById('lot_number').focus();
                    }
                });
                
                // Handle manual selection - update code input
                materialSelect.addEventListener('change', function() {
                    const selectedOption = materialSelect.options[materialSelect.selectedIndex];
                    if (selectedOption && selectedOption.hasAttribute('data-code')) {
                        materialCodeInput.value = selectedOption.getAttribute('data-code');
                    } else if (this.value === '') {
                        materialCodeInput.value = '';
                    }
                });
                
                // Clear both when code input is cleared
                materialCodeInput.addEventListener('input', function() {
                    if (this.value.trim() === '') {
                        materialSelect.value = '';
                    }
                });
            }
        });
    </script>
<?php
// Include footer component
include '../src/includes/footer.php';
?>