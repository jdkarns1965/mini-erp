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
                INSERT INTO materials (material_code, material_name, material_type, supplier_name, created_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['material_code'],
                $_POST['material_name'],
                $_POST['material_type'],
                $_POST['supplier_name'],
                $current_user['id']
            ]);
            $message = 'Material added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error adding material: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all materials
try {
    $materials = $pdo->query("
        SELECT m.*, u.full_name as created_by_name
        FROM materials m
        LEFT JOIN users u ON m.created_by = u.id
        ORDER BY m.material_code
    ")->fetchAll();
} catch (Exception $e) {
    $materials = [];
    $message = 'Error loading materials: ' . $e->getMessage();
    $message_type = 'error';
}

$page_title = 'Materials';
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
                    <li><a href="materials.php" class="active">Materials</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="recipes.php">Recipes</a></li>
                    <li><a href="products.php">Products</a></li>
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
            
            <?php if ($can_manage): ?>
            <div class="form-section">
                <h3>Add New Material</h3>
                <form method="POST" class="material-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="material_code">Material Code:</label>
                            <input type="text" id="material_code" name="material_code" required 
                                   placeholder="e.g., 90006, GRAY-ABC">
                        </div>
                        <div class="form-group">
                            <label for="material_name">Material Name:</label>
                            <input type="text" id="material_name" name="material_name" required 
                                   placeholder="Descriptive name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="material_type">Material Type:</label>
                            <select id="material_type" name="material_type" required>
                                <option value="">Select Type</option>
                                <option value="base_resin">Base Resin</option>
                                <option value="color_concentrate">Color Concentrate</option>
                                <option value="rework">Rework Material</option>
                                <option value="additive">Additive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="supplier_name">Supplier:</label>
                            <input type="text" id="supplier_name" name="supplier_name" required 
                                   placeholder="Supplier name">
                        </div>
                    </div>
                    <button type="submit" name="add_material" class="btn btn-primary">Add Material</button>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($materials)): ?>
                                <tr>
                                    <td colspan="6" class="no-data">No materials found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($materials as $material): ?>
                                    <tr>
                                        <td class="material-code"><?php echo htmlspecialchars($material['material_code']); ?></td>
                                        <td><?php echo htmlspecialchars($material['material_name']); ?></td>
                                        <td class="material-type">
                                            <?php echo ucfirst(str_replace('_', ' ', $material['material_type'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($material['supplier_name']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($material['created_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($material['created_by_name'] ?? 'Unknown'); ?></td>
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
</body>
</html>