<?php
/**
 * Products Master Management Page
 * Phase 2: Manufacturing Operations - Products Master
 * Handles product codes, descriptions, customer information
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Check permissions - Admin, supervisor, and material handlers can manage products
$can_manage = $auth->hasRole(['admin', 'supervisor', 'material_handler']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    if (isset($_POST['add_product'])) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    product_code, 
                    product_description, 
                    customer_name, 
                    customer_part_number, 
                    product_category, 
                    status,
                    engineering_drawings_url,
                    specifications,
                    unit_of_measure,
                    quoted_monthly_volume,
                    quoted_annual_volume,
                    price_per_piece,
                    quote_date,
                    program_launch_date,
                    program_end_date,
                    volume_notes,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['product_code'],
                $_POST['product_description'],
                $_POST['customer_name'],
                $_POST['customer_part_number'],
                $_POST['product_category'],
                $_POST['status'],
                !empty($_POST['engineering_drawings_url']) ? $_POST['engineering_drawings_url'] : null,
                !empty($_POST['specifications']) ? $_POST['specifications'] : null,
                !empty($_POST['unit_of_measure']) ? $_POST['unit_of_measure'] : 'EA',
                !empty($_POST['quoted_monthly_volume']) ? (int)$_POST['quoted_monthly_volume'] : null,
                !empty($_POST['quoted_annual_volume']) ? (int)$_POST['quoted_annual_volume'] : null,
                !empty($_POST['price_per_piece']) ? (float)$_POST['price_per_piece'] : null,
                !empty($_POST['quote_date']) ? $_POST['quote_date'] : null,
                !empty($_POST['program_launch_date']) ? $_POST['program_launch_date'] : null,
                !empty($_POST['program_end_date']) ? $_POST['program_end_date'] : null,
                !empty($_POST['volume_notes']) ? $_POST['volume_notes'] : null,
                $current_user['id']
            ]);
            $message = 'Product added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error adding product: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['update_product'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products SET 
                    product_description = ?, 
                    customer_name = ?, 
                    customer_part_number = ?, 
                    product_category = ?, 
                    status = ?,
                    engineering_drawings_url = ?,
                    specifications = ?,
                    unit_of_measure = ?,
                    quoted_monthly_volume = ?,
                    quoted_annual_volume = ?,
                    price_per_piece = ?,
                    quote_date = ?,
                    program_launch_date = ?,
                    program_end_date = ?,
                    volume_notes = ?,
                    updated_by = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['product_description'],
                $_POST['customer_name'],
                $_POST['customer_part_number'],
                $_POST['product_category'],
                $_POST['status'],
                !empty($_POST['engineering_drawings_url']) ? $_POST['engineering_drawings_url'] : null,
                !empty($_POST['specifications']) ? $_POST['specifications'] : null,
                !empty($_POST['unit_of_measure']) ? $_POST['unit_of_measure'] : 'EA',
                !empty($_POST['quoted_monthly_volume']) ? (int)$_POST['quoted_monthly_volume'] : null,
                !empty($_POST['quoted_annual_volume']) ? (int)$_POST['quoted_annual_volume'] : null,
                !empty($_POST['price_per_piece']) ? (float)$_POST['price_per_piece'] : null,
                !empty($_POST['quote_date']) ? $_POST['quote_date'] : null,
                !empty($_POST['program_launch_date']) ? $_POST['program_launch_date'] : null,
                !empty($_POST['program_end_date']) ? $_POST['program_end_date'] : null,
                !empty($_POST['volume_notes']) ? $_POST['volume_notes'] : null,
                $current_user['id'],
                $_POST['product_id']
            ]);
            $message = 'Product updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating product: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all products
try {
    $products = $pdo->query("
        SELECT p.*, 
               u1.full_name as created_by_name,
               u2.full_name as updated_by_name
        FROM products p
        LEFT JOIN users u1 ON p.created_by = u1.id
        LEFT JOIN users u2 ON p.updated_by = u2.id
        WHERE p.is_active = 1
        ORDER BY p.product_code
    ")->fetchAll();
} catch (Exception $e) {
    $products = [];
    $message = 'Error loading products: ' . $e->getMessage();
    $message_type = 'error';
}

// Get product for editing if requested
$edit_product = null;
if (isset($_GET['edit']) && $can_manage) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$_GET['edit']]);
        $edit_product = $stmt->fetch();
    } catch (Exception $e) {
        $message = 'Error loading product for editing: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Products Master';
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
                    <p class="subtitle">Phase 2: Manufacturing Operations</p>
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
                    <li><a href="products.php" class="active">Products</a></li>
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
            <h2><?php echo $page_title; ?></h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($can_manage): ?>
            <div class="form-section">
                <h3><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
                <form method="POST" class="product-form">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_code">Product Code:</label>
                            <input type="text" id="product_code" name="product_code" required 
                                   value="<?php echo htmlspecialchars(isset($edit_product['product_code']) ? $edit_product['product_code'] : ''); ?>"
                                   placeholder="e.g., AUTO-BRACKET-001"
                                   <?php echo $edit_product ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label for="product_description">Product Description:</label>
                            <input type="text" id="product_description" name="product_description" required 
                                   value="<?php echo htmlspecialchars(isset($edit_product['product_description']) ? $edit_product['product_description'] : ''); ?>"
                                   placeholder="Descriptive product name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_name">Customer:</label>
                            <input type="text" id="customer_name" name="customer_name" 
                                   value="<?php echo htmlspecialchars(isset($edit_product['customer_name']) ? $edit_product['customer_name'] : ''); ?>"
                                   placeholder="Customer company name">
                        </div>
                        <div class="form-group">
                            <label for="customer_part_number">Customer Part Number:</label>
                            <input type="text" id="customer_part_number" name="customer_part_number" 
                                   value="<?php echo htmlspecialchars(isset($edit_product['customer_part_number']) ? $edit_product['customer_part_number'] : ''); ?>"
                                   placeholder="Customer's part number">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_category">Product Category:</label>
                            <select id="product_category" name="product_category" required>
                                <option value="">Select Category</option>
                                <option value="automotive_interior" <?php echo (isset($edit_product['product_category']) && $edit_product['product_category'] === 'automotive_interior') ? 'selected' : ''; ?>>Automotive Interior</option>
                                <option value="automotive_exterior" <?php echo (isset($edit_product['product_category']) && $edit_product['product_category'] === 'automotive_exterior') ? 'selected' : ''; ?>>Automotive Exterior</option>
                                <option value="consumer_products" <?php echo (isset($edit_product['product_category']) && $edit_product['product_category'] === 'consumer_products') ? 'selected' : ''; ?>>Consumer Products</option>
                                <option value="industrial" <?php echo (isset($edit_product['product_category']) && $edit_product['product_category'] === 'industrial') ? 'selected' : ''; ?>>Industrial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <option value="DEVELOPMENT" <?php echo (isset($edit_product['status']) && $edit_product['status'] === 'DEVELOPMENT') ? 'selected' : ''; ?>>Development</option>
                                <option value="ACTIVE" <?php echo (isset($edit_product['status']) && $edit_product['status'] === 'ACTIVE') ? 'selected' : ''; ?>>Active</option>
                                <option value="OBSOLETE" <?php echo (isset($edit_product['status']) && $edit_product['status'] === 'OBSOLETE') ? 'selected' : ''; ?>>Obsolete</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="unit_of_measure">Unit of Measure:</label>
                            <select id="unit_of_measure" name="unit_of_measure">
                                <option value="EA" <?php echo (!isset($edit_product['unit_of_measure']) || $edit_product['unit_of_measure'] === 'EA') ? 'selected' : ''; ?>>Each (EA)</option>
                                <option value="LB" <?php echo (isset($edit_product['unit_of_measure']) && $edit_product['unit_of_measure'] === 'LB') ? 'selected' : ''; ?>>Pounds (LB)</option>
                                <option value="KG" <?php echo (isset($edit_product['unit_of_measure']) && $edit_product['unit_of_measure'] === 'KG') ? 'selected' : ''; ?>>Kilograms (KG)</option>
                                <option value="SET" <?php echo (isset($edit_product['unit_of_measure']) && $edit_product['unit_of_measure'] === 'SET') ? 'selected' : ''; ?>>Set</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="engineering_drawings_url">Engineering Drawings URL:</label>
                            <input type="url" id="engineering_drawings_url" name="engineering_drawings_url" 
                                   value="<?php echo htmlspecialchars(isset($edit_product['engineering_drawings_url']) ? $edit_product['engineering_drawings_url'] : ''); ?>"
                                   placeholder="https://...">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="specifications">Product Specifications:</label>
                        <textarea id="specifications" name="specifications" rows="4" 
                                  placeholder="General product specifications, appearance requirements, and documentation notes"><?php echo htmlspecialchars(isset($edit_product['specifications']) ? $edit_product['specifications'] : ''); ?></textarea>
                    </div>
                    
                    <h4>Volume & Program Information</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quoted_monthly_volume">Quoted Monthly Volume:</label>
                            <input type="number" id="quoted_monthly_volume" name="quoted_monthly_volume" 
                                   value="<?php echo htmlspecialchars(isset($edit_product['quoted_monthly_volume']) ? $edit_product['quoted_monthly_volume'] : ''); ?>"
                                   placeholder="50000" min="0">
                        </div>
                        <div class="form-group">
                            <label for="quoted_annual_volume">Quoted Annual Volume: <small>(calculated from monthly × 12)</small></label>
                            <input type="number" id="quoted_annual_volume" name="quoted_annual_volume" 
                                   value="<?php echo htmlspecialchars(isset($edit_product['quoted_annual_volume']) ? $edit_product['quoted_annual_volume'] : ''); ?>"
                                   placeholder="600000" min="0" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price_per_piece">Price Per Piece ($):</label>
                            <input type="number" id="price_per_piece" name="price_per_piece" step="0.0001"
                                   value="<?php echo htmlspecialchars(isset($edit_product['price_per_piece']) ? $edit_product['price_per_piece'] : ''); ?>"
                                   placeholder="2.85" min="0">
                        </div>
                        <div class="form-group">
                            <label for="quote_date">Quote Date:</label>
                            <input type="date" id="quote_date" name="quote_date" 
                                   value="<?php echo htmlspecialchars(isset($edit_product['quote_date']) ? $edit_product['quote_date'] : ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="program_launch_date">Program Launch Date:</label>
                            <input type="date" id="program_launch_date" name="program_launch_date" 
                                   value="<?php echo htmlspecialchars(isset($edit_product['program_launch_date']) ? $edit_product['program_launch_date'] : ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="program_end_date">Program End Date:</label>
                            <input type="date" id="program_end_date" name="program_end_date" 
                                   value="<?php echo htmlspecialchars(isset($edit_product['program_end_date']) ? $edit_product['program_end_date'] : ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="volume_notes">Volume Notes:</label>
                        <textarea id="volume_notes" name="volume_notes" rows="3" 
                                  placeholder="Volume assumptions, market conditions, program notes"><?php echo htmlspecialchars(isset($edit_product['volume_notes']) ? $edit_product['volume_notes'] : ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" class="btn btn-primary">
                            <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                        </button>
                        <?php if ($edit_product): ?>
                            <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="products-list">
                <h3>Products List</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product Code</th>
                                <th>Description</th>
                                <th>Customer</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Monthly Vol.</th>
                                <th>Price/Pc</th>
                                <th>Launch Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="no-data">No products found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="product-code"><?php echo htmlspecialchars($product['product_code']); ?></td>
                                        <td class="product-description"><?php echo htmlspecialchars($product['product_description']); ?></td>
                                        <td><?php echo htmlspecialchars($product['customer_name'] ? $product['customer_name'] : 'N/A'); ?></td>
                                        <td class="product-category">
                                            <?php echo ucfirst(str_replace('_', ' ', $product['product_category'])); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($product['status']); ?>">
                                                <?php echo $product['status']; ?>
                                            </span>
                                        </td>
                                        <td class="volume"><?php echo $product['quoted_monthly_volume'] ? number_format($product['quoted_monthly_volume']) : 'N/A'; ?></td>
                                        <td class="price"><?php echo $product['price_per_piece'] ? '$' . number_format($product['price_per_piece'], 2) : 'N/A'; ?></td>
                                        <td><?php echo $product['program_launch_date'] ? date('Y-m-d', strtotime($product['program_launch_date'])) : 'N/A'; ?></td>
                                        <td class="actions">
                                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-small btn-primary">View</a>
                                            <?php if ($can_manage): ?>
                                                <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Mini ERP System - Phase 2: Manufacturing Operations</p>
        </footer>
    </div>

    <script>
        // Auto-calculate annual volume from monthly volume
        document.addEventListener('DOMContentLoaded', function() {
            const monthlyInput = document.getElementById('quoted_monthly_volume');
            const annualInput = document.getElementById('quoted_annual_volume');
            
            if (monthlyInput && annualInput) {
                console.log('Volume calculation loaded');
                
                // Simple calculation function
                function calculateAnnual() {
                    const monthly = parseFloat(monthlyInput.value) || 0;
                    const annual = monthly * 12;
                    
                    console.log('Monthly:', monthly, 'Annual:', annual);
                    
                    annualInput.value = annual > 0 ? annual : '';
                    
                    // Visual styling for calculated field
                    if (annual > 0) {
                        annualInput.style.backgroundColor = '#f0f8ff';
                        annualInput.title = monthly.toLocaleString() + ' × 12 = ' + annual.toLocaleString();
                    } else {
                        annualInput.style.backgroundColor = '';
                        annualInput.title = '';
                    }
                }
                
                // Update on every keystroke
                monthlyInput.addEventListener('input', calculateAnnual);
                monthlyInput.addEventListener('change', calculateAnnual);
                
                // Calculate on page load
                calculateAnnual();
            }
        });
    </script>
</body>
</html>