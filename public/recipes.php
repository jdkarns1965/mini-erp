<?php
/**
 * Recipe Management Page
 * Handles part-specific material formulations and recipe approvals
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
$can_create = $auth->hasRole(['admin', 'supervisor']);
$can_approve = $auth->hasRole(['admin', 'supervisor', 'quality_inspector']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_recipe']) && $can_create) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO recipes (part_number, part_name, base_material_id, concentrate_material_id, 
                                   concentrate_percentage, version, created_by, status) 
                VALUES (?, ?, ?, ?, ?, 1, ?, 'pending_approval')
            ");
            $stmt->execute([
                $_POST['part_number'],
                $_POST['part_name'],
                $_POST['base_material_id'],
                $_POST['concentrate_material_id'],
                $_POST['concentrate_percentage'],
                $current_user['id']
            ]);
            $message = 'Recipe created successfully! Awaiting approval.';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error creating recipe: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['approve_recipe']) && $can_approve) {
        try {
            $stmt = $pdo->prepare("
                UPDATE recipes 
                SET status = 'approved', approved_by = ?, approved_date = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$current_user['id'], $_POST['recipe_id']]);
            $message = 'Recipe approved successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error approving recipe: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get recipes with material details
try {
    $recipes = $pdo->query("
        SELECT r.*, 
               bm.material_code as base_code, bm.material_name as base_name,
               cm.material_code as concentrate_code, cm.material_name as concentrate_name,
               u1.full_name as created_by_name,
               u2.full_name as approved_by_name
        FROM recipes r
        JOIN materials bm ON r.base_material_id = bm.id
        LEFT JOIN materials cm ON r.concentrate_material_id = cm.id
        LEFT JOIN users u1 ON r.created_by = u1.id
        LEFT JOIN users u2 ON r.approved_by = u2.id
        ORDER BY r.part_number, r.version DESC
    ")->fetchAll();
} catch (Exception $e) {
    $recipes = [];
    $message = 'Error loading recipes: ' . $e->getMessage();
    $message_type = 'error';
}

// Get materials for dropdowns
try {
    $base_materials = $pdo->query("
        SELECT id, material_code, material_name
        FROM materials 
        WHERE material_type = 'base_resin'
        ORDER BY material_code
    ")->fetchAll();
    
    $concentrates = $pdo->query("
        SELECT id, material_code, material_name
        FROM materials 
        WHERE material_type = 'color_concentrate'
        ORDER BY material_code
    ")->fetchAll();
} catch (Exception $e) {
    $base_materials = [];
    $concentrates = [];
}

$page_title = 'Recipes';

// Include header component
include '../src/includes/header.php';
?>
            <h2><?php echo $page_title; ?> Management</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($can_create): ?>
            <div class="form-section">
                <h3>Create New Recipe</h3>
                <div class="recipe-workflow">
                    <p><strong>Recipe Development Workflow:</strong></p>
                    <ol>
                        <li>Enter part details and initial material formulation</li>
                        <li>Recipe requires approval from Quality Inspector + Supervisor</li>
                        <li>Approved recipes become standard for production</li>
                        <li>Recipe changes during production require dual approval</li>
                    </ol>
                </div>
                
                <form method="POST" class="recipe-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="part_number">Part Number:</label>
                            <input type="text" id="part_number" name="part_number" required 
                                   placeholder="e.g., 12345">
                        </div>
                        <div class="form-group">
                            <label for="part_name">Part Name:</label>
                            <input type="text" id="part_name" name="part_name" required 
                                   placeholder="Descriptive part name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="base_material_id">Base Material:</label>
                            <select id="base_material_id" name="base_material_id" required>
                                <option value="">Select Base Resin</option>
                                <?php foreach ($base_materials as $material): ?>
                                    <option value="<?php echo $material['id']; ?>">
                                        <?php echo htmlspecialchars($material['material_code'] . ' - ' . $material['material_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="concentrate_material_id">Color Concentrate:</label>
                            <select id="concentrate_material_id" name="concentrate_material_id">
                                <option value="">Select Concentrate (Optional)</option>
                                <?php foreach ($concentrates as $concentrate): ?>
                                    <option value="<?php echo $concentrate['id']; ?>">
                                        <?php echo htmlspecialchars($concentrate['material_code'] . ' - ' . $concentrate['material_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="concentrate_percentage">Concentrate Percentage:</label>
                            <input type="number" id="concentrate_percentage" name="concentrate_percentage" 
                                   step="0.1" min="0" max="50" placeholder="e.g., 3.0">
                            <small>Typical range: 1-10% for most applications</small>
                        </div>
                    </div>
                    <button type="submit" name="create_recipe" class="btn btn-primary">Create Recipe</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="recipes-list">
                <h3>Recipe List</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Part Number</th>
                                <th>Part Name</th>
                                <th>Base Material</th>
                                <th>Concentrate</th>
                                <th>%</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Approved By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recipes)): ?>
                                <tr>
                                    <td colspan="10" class="no-data">No recipes found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recipes as $recipe): ?>
                                    <tr>
                                        <td class="part-number"><?php echo htmlspecialchars($recipe['part_number']); ?></td>
                                        <td><?php echo htmlspecialchars($recipe['part_name']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($recipe['base_code']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($recipe['base_name']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($recipe['concentrate_code']): ?>
                                                <strong><?php echo htmlspecialchars($recipe['concentrate_code']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($recipe['concentrate_name']); ?></small>
                                            <?php else: ?>
                                                <em>None</em>
                                            <?php endif; ?>
                                        </td>
                                        <td class="percentage">
                                            <?php echo $recipe['concentrate_percentage'] ? number_format($recipe['concentrate_percentage'], 1) . '%' : '-'; ?>
                                        </td>
                                        <td class="version">v<?php echo $recipe['version']; ?></td>
                                        <td class="status <?php echo $recipe['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $recipe['status'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($recipe['created_by_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($recipe['approved_by_name'] ?? '-'); ?></td>
                                        <td class="actions">
                                            <?php if ($recipe['status'] === 'pending_approval' && $can_approve): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                                    <button type="submit" name="approve_recipe" class="btn btn-small">Approve</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
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