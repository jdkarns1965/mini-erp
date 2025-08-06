<?php
/**
 * Supplier Management Page
 * Handles supplier creation, editing, and management
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Check permissions - Admin and material handlers can manage suppliers
$can_manage = $auth->hasRole(['admin', 'material_handler']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    if (isset($_POST['add_supplier'])) {
        try {
            // Generate supplier code
            $latest_supplier = $pdo->query("SELECT supplier_code FROM suppliers WHERE supplier_code LIKE 'SUPP%' ORDER BY supplier_code DESC LIMIT 1")->fetch();
            if ($latest_supplier) {
                $num = (int)substr($latest_supplier['supplier_code'], 4) + 1;
            } else {
                $num = 1;
            }
            $supplier_code = 'SUPP' . str_pad($num, 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO suppliers (supplier_code, supplier_name, contact_person, contact_title, email, phone, phone_ext, 
                                     address_line1, address_line2, city, state, zip_code, country,
                                     payment_terms, lead_time_days, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $supplier_code,
                $_POST['supplier_name'],
                $_POST['contact_person'] ?: null,
                $_POST['contact_title'] ?: null,
                $_POST['email'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['phone_ext'] ?: null,
                $_POST['address_line1'] ?: null,
                $_POST['address_line2'] ?: null,
                $_POST['city'] ?: null,
                $_POST['state'] ?: null,
                $_POST['zip_code'] ?: null,
                $_POST['country'] ?: 'USA',
                $_POST['payment_terms'] ?: null,
                $_POST['lead_time_days'] ?: 0,
                $_POST['notes'] ?: null,
                $current_user['id']
            ]);
            $message = "Supplier added successfully! Code: $supplier_code";
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error adding supplier: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['edit_supplier'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE suppliers SET 
                    supplier_name = ?, contact_person = ?, contact_title = ?, email = ?, phone = ?, phone_ext = ?,
                    address_line1 = ?, address_line2 = ?, city = ?, state = ?, zip_code = ?, country = ?,
                    payment_terms = ?, lead_time_days = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['supplier_name'],
                $_POST['contact_person'] ?: null,
                $_POST['contact_title'] ?: null,
                $_POST['email'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['phone_ext'] ?: null,
                $_POST['address_line1'] ?: null,
                $_POST['address_line2'] ?: null,
                $_POST['city'] ?: null,
                $_POST['state'] ?: null,
                $_POST['zip_code'] ?: null,
                $_POST['country'] ?: 'USA',
                $_POST['payment_terms'] ?: null,
                $_POST['lead_time_days'] ?: 0,
                $_POST['notes'] ?: null,
                $_POST['supplier_id']
            ]);
            $message = 'Supplier updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating supplier: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['toggle_active'])) {
        try {
            $stmt = $pdo->prepare("UPDATE suppliers SET is_active = !is_active WHERE id = ?");
            $stmt->execute([$_POST['supplier_id']]);
            $message = 'Supplier status updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating supplier status: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all suppliers
try {
    $suppliers = $pdo->query("
        SELECT s.*, u.full_name as created_by_name,
               (SELECT COUNT(*) FROM materials WHERE supplier_id = s.id AND is_active = 1) as material_count
        FROM suppliers s
        LEFT JOIN users u ON s.created_by = u.id
        ORDER BY s.supplier_name
    ")->fetchAll();
} catch (Exception $e) {
    $suppliers = [];
    $message = 'Error loading suppliers: ' . $e->getMessage();
    $message_type = 'error';
}

// Get supplier for editing if requested
$edit_supplier = null;
if (isset($_GET['edit']) && $can_manage) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_supplier = $stmt->fetch();
    } catch (Exception $e) {
        $message = 'Error loading supplier for editing: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Suppliers';

// Include header component
include '../src/includes/header.php';
?>
            <h2><?php echo $page_title; ?> Management</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($can_manage): ?>
            <div class="form-section">
                <h3><?php echo $edit_supplier ? 'Edit Supplier' : 'Add New Supplier'; ?></h3>
                <form method="POST" class="supplier-form">
                    <?php if ($edit_supplier): ?>
                        <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="supplier_name">Supplier Name:</label>
                            <input type="text" id="supplier_name" name="supplier_name" required 
                                   value="<?php echo htmlspecialchars($edit_supplier['supplier_name'] ?? ''); ?>"
                                   placeholder="Company name">
                        </div>
                        <div class="form-group">
                            <label for="contact_person">Contact Person:</label>
                            <input type="text" id="contact_person" name="contact_person" 
                                   value="<?php echo htmlspecialchars($edit_supplier['contact_person'] ?? ''); ?>"
                                   placeholder="Primary contact name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_title">Contact Title:</label>
                            <input type="text" id="contact_title" name="contact_title" 
                                   value="<?php echo htmlspecialchars($edit_supplier['contact_title'] ?? ''); ?>"
                                   placeholder="e.g., Sales Manager, Account Rep">
                        </div>
                        <div class="form-group">
                            <!-- Spacer for layout -->
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($edit_supplier['email'] ?? ''); ?>"
                                   placeholder="contact@supplier.com">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($edit_supplier['phone'] ?? ''); ?>"
                                   placeholder="(555) 123-4567">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone_ext">Phone Extension:</label>
                            <input type="text" id="phone_ext" name="phone_ext" 
                                   value="<?php echo htmlspecialchars($edit_supplier['phone_ext'] ?? ''); ?>"
                                   placeholder="ext. 1234">
                        </div>
                        <div class="form-group">
                            <!-- Spacer for layout -->
                        </div>
                    </div>
                    
                    <h4>Address Information</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="address_line1">Address Line 1:</label>
                            <input type="text" id="address_line1" name="address_line1" 
                                   value="<?php echo htmlspecialchars($edit_supplier['address_line1'] ?? ''); ?>"
                                   placeholder="Street address">
                        </div>
                        <div class="form-group">
                            <label for="address_line2">Address Line 2:</label>
                            <input type="text" id="address_line2" name="address_line2" 
                                   value="<?php echo htmlspecialchars($edit_supplier['address_line2'] ?? ''); ?>"
                                   placeholder="Suite, unit, etc. (optional)">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City:</label>
                            <input type="text" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($edit_supplier['city'] ?? ''); ?>"
                                   placeholder="City">
                        </div>
                        <div class="form-group">
                            <label for="state">State:</label>
                            <input type="text" id="state" name="state" 
                                   value="<?php echo htmlspecialchars($edit_supplier['state'] ?? ''); ?>"
                                   placeholder="State/Province">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code">ZIP Code:</label>
                            <input type="text" id="zip_code" name="zip_code" 
                                   value="<?php echo htmlspecialchars($edit_supplier['zip_code'] ?? ''); ?>"
                                   placeholder="ZIP/Postal code">
                        </div>
                        <div class="form-group">
                            <label for="country">Country:</label>
                            <input type="text" id="country" name="country" 
                                   value="<?php echo htmlspecialchars($edit_supplier['country'] ?? 'USA'); ?>"
                                   placeholder="Country">
                        </div>
                    </div>
                    
                    <h4>Business Information</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_terms">Payment Terms:</label>
                            <input type="text" id="payment_terms" name="payment_terms" 
                                   value="<?php echo htmlspecialchars($edit_supplier['payment_terms'] ?? ''); ?>"
                                   placeholder="e.g., Net 30, COD">
                        </div>
                        <div class="form-group">
                            <label for="lead_time_days">Lead Time (Days):</label>
                            <input type="number" id="lead_time_days" name="lead_time_days" min="0" 
                                   value="<?php echo htmlspecialchars($edit_supplier['lead_time_days'] ?? '0'); ?>"
                                   placeholder="Typical lead time in days">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="Additional notes about this supplier"><?php echo htmlspecialchars($edit_supplier['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="<?php echo $edit_supplier ? 'edit_supplier' : 'add_supplier'; ?>" class="btn btn-primary">
                            <?php echo $edit_supplier ? 'Update Supplier' : 'Add Supplier'; ?>
                        </button>
                        <?php if ($edit_supplier): ?>
                            <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="suppliers-list">
                <h3>Suppliers List</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Materials</th>
                                <th>Lead Time</th>
                                <th>Status</th>
                                <?php if ($can_manage): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($suppliers)): ?>
                                <tr>
                                    <td colspan="<?php echo $can_manage ? '8' : '7'; ?>" class="no-data">No suppliers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <tr class="<?php echo !$supplier['is_active'] ? 'inactive' : ''; ?>">
                                        <td class="supplier-code"><?php echo htmlspecialchars($supplier['supplier_code']); ?></td>
                                        <td class="supplier-name">
                                            <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                                            <?php if ($supplier['contact_person']): ?>
                                                <br><small>
                                                    <?php echo htmlspecialchars($supplier['contact_person']); ?>
                                                    <?php if ($supplier['contact_title']): ?>
                                                        <br><em><?php echo htmlspecialchars($supplier['contact_title']); ?></em>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($supplier['phone']): ?>
                                                <div>
                                                    <?php echo htmlspecialchars($supplier['phone']); ?>
                                                    <?php if ($supplier['phone_ext']): ?>
                                                        ext. <?php echo htmlspecialchars($supplier['phone_ext']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($supplier['email']): ?>
                                                <div><small><?php echo htmlspecialchars($supplier['email']); ?></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $location = array_filter([$supplier['city'], $supplier['state']]);
                                            echo htmlspecialchars(implode(', ', $location) ?: 'N/A');
                                            ?>
                                        </td>
                                        <td class="material-count"><?php echo $supplier['material_count']; ?></td>
                                        <td><?php echo $supplier['lead_time_days']; ?> days</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $supplier['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $supplier['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <?php if ($can_manage): ?>
                                        <td class="actions">
                                            <a href="suppliers.php?edit=<?php echo $supplier['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                                <button type="submit" name="toggle_active" class="btn btn-small btn-<?php echo $supplier['is_active'] ? 'warning' : 'success'; ?>">
                                                    <?php echo $supplier['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php
// Include footer component
include '../src/includes/footer.php';
?>