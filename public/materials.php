<?php
/**
 * Materials Management Page
 * Handles material types, receiving, and basic material operations
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Check permissions - Material handlers and admins can manage materials
$can_manage = $auth->hasRole(['admin', 'material_handler']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    if (isset($_POST['add_material'])) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO materials (material_code, material_name, material_type, supplier_id, created_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['material_code'],
                $_POST['material_name'],
                $_POST['material_type'],
                $_POST['supplier_id'],
                $current_user['id']
            ]);
            $message = 'Material added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error adding material: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['edit_material'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE materials SET 
                    material_name = ?, 
                    material_type = ?, 
                    supplier_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['material_name'],
                $_POST['material_type'],
                $_POST['supplier_id'],
                $_POST['material_id']
            ]);
            $message = 'Material updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating material: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['delete_material'])) {
        try {
            // Check if material is used in inventory or recipes
            $usage_check = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM inventory WHERE material_id = ?) as inventory_count,
                    (SELECT COUNT(*) FROM recipes WHERE base_material_id = ? OR concentrate_material_id = ?) as recipe_count
            ");
            $usage_check->execute([$_POST['material_id'], $_POST['material_id'], $_POST['material_id']]);
            $usage = $usage_check->fetch();
            
            if ($usage['inventory_count'] > 0 || $usage['recipe_count'] > 0) {
                // Soft delete by setting is_active = false
                $stmt = $pdo->prepare("UPDATE materials SET is_active = 0 WHERE id = ?");
                $stmt->execute([$_POST['material_id']]);
                $message = 'Material deactivated (still used in inventory/recipes)';
                $message_type = 'success';
            } else {
                // Hard delete if not used
                $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
                $stmt->execute([$_POST['material_id']]);
                $message = 'Material deleted successfully!';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error deleting material: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all materials
try {
    $materials = $pdo->query("
        SELECT m.*, u.full_name as created_by_name, s.supplier_name, s.supplier_code
        FROM materials m
        LEFT JOIN users u ON m.created_by = u.id
        LEFT JOIN suppliers s ON m.supplier_id = s.id
        WHERE m.is_active = 1
        ORDER BY m.material_code
    ")->fetchAll();
} catch (Exception $e) {
    $materials = [];
    $message = 'Error loading materials: ' . $e->getMessage();
    $message_type = 'error';
}

// Get suppliers for dropdown
$suppliers = [];
try {
    $suppliers = $pdo->query("
        SELECT id, supplier_name, supplier_code 
        FROM suppliers 
        WHERE is_active = 1 
        ORDER BY supplier_name
    ")->fetchAll();
} catch (Exception $e) {
    // Handle error silently for now
}

// Get material for editing if requested
$edit_material = null;
if (isset($_GET['edit']) && $can_manage) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ? AND is_active = 1");
        $stmt->execute([$_GET['edit']]);
        $edit_material = $stmt->fetch();
    } catch (Exception $e) {
        $message = 'Error loading material for editing: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Materials';

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
                <h3><?php echo $edit_material ? 'Edit Material' : 'Add New Material'; ?></h3>
                <form method="POST" class="material-form">
                    <?php if ($edit_material): ?>
                        <input type="hidden" name="material_id" value="<?php echo $edit_material['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="material_code">Material Code:</label>
                            <input type="text" id="material_code" name="material_code" required 
                                   value="<?php echo htmlspecialchars($edit_material['material_code'] ?? ''); ?>"
                                   placeholder="e.g., 90006, GRAY-ABC"
                                   <?php echo $edit_material ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label for="material_name">Material Name:</label>
                            <input type="text" id="material_name" name="material_name" required 
                                   value="<?php echo htmlspecialchars($edit_material['material_name'] ?? ''); ?>"
                                   placeholder="Descriptive name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="material_type">Material Type:</label>
                            <select id="material_type" name="material_type" required>
                                <option value="">Select Type</option>
                                <option value="base_resin" <?php echo (isset($edit_material['material_type']) && $edit_material['material_type'] === 'base_resin') ? 'selected' : ''; ?>>Base Resin</option>
                                <option value="color_concentrate" <?php echo (isset($edit_material['material_type']) && $edit_material['material_type'] === 'color_concentrate') ? 'selected' : ''; ?>>Color Concentrate</option>
                                <option value="rework" <?php echo (isset($edit_material['material_type']) && $edit_material['material_type'] === 'rework') ? 'selected' : ''; ?>>Rework Material</option>
                                <option value="additive" <?php echo (isset($edit_material['material_type']) && $edit_material['material_type'] === 'additive') ? 'selected' : ''; ?>>Additive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="supplier_id">Supplier:</label>
                            <select id="supplier_id" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" 
                                            <?php echo (isset($edit_material['supplier_id']) && $edit_material['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['supplier_name'] . ' (' . $supplier['supplier_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($suppliers)): ?>
                                <small class="form-help">No suppliers found. Please add suppliers first.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="<?php echo $edit_material ? 'edit_material' : 'add_material'; ?>" class="btn btn-primary">
                            <?php echo $edit_material ? 'Update Material' : 'Add Material'; ?>
                        </button>
                        <?php if ($edit_material): ?>
                            <a href="materials.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="materials-list">
                <h3>Material List</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Supplier</th>
                                <th>Created</th>
                                <th>Created By</th>
                                <?php if ($can_manage): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($materials)): ?>
                                <tr>
                                    <td colspan="<?php echo $can_manage ? '7' : '6'; ?>" class="no-data">No materials found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($materials as $material): ?>
                                    <tr>
                                        <td class="material-code"><?php echo htmlspecialchars($material['material_code']); ?></td>
                                        <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                                        <td class="material-type">
                                            <?php echo ucfirst(str_replace('_', ' ', $material['material_type'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($material['supplier_name'] ? $material['supplier_name'] . ' (' . $material['supplier_code'] . ')' : 'No Supplier'); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($material['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($material['created_by_name'] ?? 'Unknown'); ?></td>
                                        <?php if ($can_manage): ?>
                                        <td class="actions">
                                            <a href="materials.php?edit=<?php echo $material['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this material? This action cannot be undone.');">
                                                <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                                                <button type="submit" name="delete_material" class="btn btn-small btn-danger">Delete</button>
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