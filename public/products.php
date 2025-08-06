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
            // Handle image upload
            $product_image_path = null;
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/products/';
                $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $filename = uniqid('product_') . '.' . $file_extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        $product_image_path = $upload_path;
                    } else {
                        $message = 'Product saved but image upload failed.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Invalid file type. Please use JPG, PNG, GIF, or WebP format.';
                    $message_type = 'error';
                }
            } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $error_messages = [
                    UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds server limit)',
                    UPLOAD_ERR_FORM_SIZE => 'File is too large (exceeds form limit)',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                ];
                $message = 'Upload error: ' . ($error_messages[$_FILES['product_image']['error']] ?? 'Unknown error');
                $message_type = 'error';
            }
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    product_code, 
                    product_description, 
                    customer_id, 
                    customer_part_number, 
                    product_category, 
                    status,
                    engineering_drawings_url,
                    product_image_path,
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
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['product_code'],
                $_POST['product_description'],
                $_POST['customer_id'],
                $_POST['customer_part_number'],
                $_POST['product_category'],
                $_POST['status'],
                !empty($_POST['engineering_drawings_url']) ? $_POST['engineering_drawings_url'] : null,
                $product_image_path,
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
            $message = 'Product added successfully!' . ($product_image_path ? ' Image uploaded.' : '');
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error adding product: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['update_product'])) {
        try {
            // Handle image upload for update
            $product_image_path = $_POST['existing_image_path'] ?? null; // Keep existing image if no new upload
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/products/';
                $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $filename = uniqid('product_') . '.' . $file_extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        // Delete old image if it exists
                        if ($product_image_path && file_exists($product_image_path)) {
                            unlink($product_image_path);
                        }
                        $product_image_path = $upload_path;
                    } else {
                        $message = 'Error uploading image file.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Invalid file type. Please use JPG, PNG, GIF, or WebP format.';
                    $message_type = 'error';
                }
            } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $error_messages = [
                    UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds server limit)',
                    UPLOAD_ERR_FORM_SIZE => 'File is too large (exceeds form limit)',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
                ];
                $message = 'Upload error: ' . ($error_messages[$_FILES['product_image']['error']] ?? 'Unknown error');
                $message_type = 'error';
            }
            $stmt = $pdo->prepare("
                UPDATE products SET 
                    product_description = ?, 
                    customer_id = ?, 
                    customer_part_number = ?, 
                    product_category = ?, 
                    status = ?,
                    engineering_drawings_url = ?,
                    product_image_path = ?,
                    specifications = ?,
                    unit_of_measure = ?,
                    quoted_monthly_volume = ?,
                    quoted_annual_volume = ?,
                    price_per_piece = ?,
                    quote_date = ?,
                    program_launch_date = ?,
                    program_end_date = ?,
                    volume_notes = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['product_description'],
                $_POST['customer_id'],
                $_POST['customer_part_number'],
                $_POST['product_category'],
                $_POST['status'],
                !empty($_POST['engineering_drawings_url']) ? $_POST['engineering_drawings_url'] : null,
                $product_image_path,
                !empty($_POST['specifications']) ? $_POST['specifications'] : null,
                !empty($_POST['unit_of_measure']) ? $_POST['unit_of_measure'] : 'EA',
                !empty($_POST['quoted_monthly_volume']) ? (int)$_POST['quoted_monthly_volume'] : null,
                !empty($_POST['quoted_annual_volume']) ? (int)$_POST['quoted_annual_volume'] : null,
                !empty($_POST['price_per_piece']) ? (float)$_POST['price_per_piece'] : null,
                !empty($_POST['quote_date']) ? $_POST['quote_date'] : null,
                !empty($_POST['program_launch_date']) ? $_POST['program_launch_date'] : null,
                !empty($_POST['program_end_date']) ? $_POST['program_end_date'] : null,
                !empty($_POST['volume_notes']) ? $_POST['volume_notes'] : null,
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
               c.customer_name, c.customer_code
        FROM products p
        LEFT JOIN users u1 ON p.created_by = u1.id
        LEFT JOIN customers c ON p.customer_id = c.id
        WHERE p.is_active = 1
        ORDER BY p.product_code
    ")->fetchAll();
} catch (Exception $e) {
    $products = [];
    $message = 'Error loading products: ' . $e->getMessage();
    $message_type = 'error';
}

// Get customers for dropdown
$customers = [];
try {
    $customers = $pdo->query("
        SELECT id, customer_name, customer_code 
        FROM customers 
        WHERE is_active = 1 
        ORDER BY customer_name
    ")->fetchAll();
} catch (Exception $e) {
    // Handle error silently for now
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

// Include header component
include '../src/includes/header.php';
?>
            <h2><?php echo $page_title; ?></h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($can_manage): ?>
            <div class="form-section">
                <h3><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
                <form method="POST" class="product-form" enctype="multipart/form-data">
                    <input type="hidden" name="MAX_FILE_SIZE" value="2097152"> <!-- 2MB in bytes -->
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
                            <label for="customer_id">Customer:</label>
                            <select id="customer_id" name="customer_id">
                                <option value="">Select Customer (Optional)</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>" 
                                            <?php echo (isset($edit_product['customer_id']) && $edit_product['customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($customer['customer_name'] . ' (' . $customer['customer_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($customers)): ?>
                                <small class="form-help">No customers found. Customers are optional for products.</small>
                            <?php endif; ?>
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
                                <option value="ACTIVE" <?php echo (isset($edit_product['status']) && $edit_product['status'] === 'ACTIVE') ? 'selected' : ''; ?>>Active</option>
                                <option value="BUILD-OUT" <?php echo (isset($edit_product['status']) && $edit_product['status'] === 'BUILD-OUT') ? 'selected' : ''; ?>>Build-Out</option>
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
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_image">Product Image:</label>
                            <input type="file" id="product_image" name="product_image" 
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <small>JPG, PNG, GIF, or WebP formats. Max 2MB. Current upload will show error messages above if it fails.</small>
                        </div>
                        <?php if ($edit_product && $edit_product['product_image_path']): ?>
                        <div class="form-group">
                            <label>Current Image:</label>
                            <div class="current-image-preview">
                                <img src="<?php echo htmlspecialchars($edit_product['product_image_path']); ?>" 
                                     alt="Current product image" style="max-width: 150px; max-height: 150px; border-radius: 5px;">
                            </div>
                            <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($edit_product['product_image_path']); ?>">
                        </div>
                        <?php endif; ?>
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
                                        <td><?php echo htmlspecialchars($product['customer_name'] ? $product['customer_name'] . ' (' . $product['customer_code'] . ')' : 'N/A'); ?></td>
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
<?php
// Include footer component
include '../src/includes/footer.php';
?>