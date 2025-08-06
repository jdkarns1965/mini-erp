<?php
/**
 * Product Details Page
 * Phase 2: Manufacturing Operations - Product Master Details View
 * Read-only view of complete product information
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

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$message = '';
$message_type = '';

if ($product_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   u1.full_name as created_by_name,
                   u2.full_name as updated_by_name
            FROM products p
            LEFT JOIN users u1 ON p.created_by = u1.id
            LEFT JOIN users u2 ON p.updated_by = u2.id
            WHERE p.id = ? AND p.is_active = 1
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $message = 'Product not found or has been deactivated.';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error loading product details: ' . $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = 'Invalid product ID.';
    $message_type = 'error';
}

$page_title = $product ? $product['product_code'] . ' Details' : 'Product Details';
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
            <div class="page-header">
                <h2><?php echo $page_title; ?></h2>
                <div class="page-actions">
                    <a href="products.php" class="btn btn-secondary">← Back to Products</a>
                    <?php if ($product && $auth->hasRole(['admin', 'supervisor', 'material_handler'])): ?>
                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-primary">Edit Product</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($product): ?>
                <div class="product-details">
                    <!-- Basic Product Information -->
                    <div class="details-section">
                        <h3>Product Information</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <label>Product Code:</label>
                                <span class="product-code"><?php echo htmlspecialchars($product['product_code']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Product Description:</label>
                                <span><?php echo htmlspecialchars($product['product_description']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Product Category:</label>
                                <span class="product-category">
                                    <?php echo ucfirst(str_replace('_', ' ', $product['product_category'])); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>Status:</label>
                                <span class="status-badge status-<?php echo strtolower($product['status']); ?>">
                                    <?php echo $product['status']; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>Unit of Measure:</label>
                                <span><?php echo htmlspecialchars($product['unit_of_measure']); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($product['product_image_path']): ?>
                            <div class="detail-item-full">
                                <label>Product Image:</label>
                                <div class="product-image-display">
                                    <img src="<?php echo htmlspecialchars($product['product_image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_description']); ?>" 
                                         class="product-detail-image">
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['specifications']): ?>
                            <div class="detail-item-full">
                                <label>Specifications:</label>
                                <div class="specifications-text"><?php echo nl2br(htmlspecialchars($product['specifications'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Customer Information -->
                    <div class="details-section">
                        <h3>Customer Information</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <label>Customer:</label>
                                <span><?php echo $product['customer_name'] ? htmlspecialchars($product['customer_name']) : 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Customer Part Number:</label>
                                <span><?php echo $product['customer_part_number'] ? htmlspecialchars($product['customer_part_number']) : 'N/A'; ?></span>
                            </div>
                            <?php if ($product['engineering_drawings_url']): ?>
                            <div class="detail-item">
                                <label>Engineering Drawings:</label>
                                <span><a href="<?php echo htmlspecialchars($product['engineering_drawings_url']); ?>" target="_blank" class="external-link">View Drawings</a></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Volume & Program Information -->
                    <?php if ($product['quoted_monthly_volume'] || $product['price_per_piece'] || $product['program_launch_date']): ?>
                    <div class="details-section">
                        <h3>Volume & Program Information</h3>
                        <div class="details-grid">
                            <?php if ($product['quoted_monthly_volume']): ?>
                            <div class="detail-item">
                                <label>Quoted Monthly Volume:</label>
                                <span class="volume"><?php echo number_format($product['quoted_monthly_volume']); ?> pieces</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($product['quoted_annual_volume']): ?>
                            <div class="detail-item">
                                <label>Quoted Annual Volume:</label>
                                <span class="volume"><?php echo number_format($product['quoted_annual_volume']); ?> pieces</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($product['price_per_piece']): ?>
                            <div class="detail-item">
                                <label>Price Per Piece:</label>
                                <span class="price"><?php echo '$' . number_format($product['price_per_piece'], 4); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($product['quote_date']): ?>
                            <div class="detail-item">
                                <label>Quote Date:</label>
                                <span><?php echo date('Y-m-d', strtotime($product['quote_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($product['program_launch_date']): ?>
                            <div class="detail-item">
                                <label>Program Launch Date:</label>
                                <span><?php echo date('Y-m-d', strtotime($product['program_launch_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($product['program_end_date']): ?>
                            <div class="detail-item">
                                <label>Program End Date:</label>
                                <span><?php echo date('Y-m-d', strtotime($product['program_end_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($product['volume_notes']): ?>
                            <div class="detail-item-full">
                                <label>Volume Notes:</label>
                                <div class="notes-text"><?php echo nl2br(htmlspecialchars($product['volume_notes'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- System Information -->
                    <div class="details-section system-info">
                        <h3>System Information</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <label>Created:</label>
                                <span><?php echo date('Y-m-d H:i', strtotime($product['created_at'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Created By:</label>
                                <span><?php echo htmlspecialchars($product['created_by_name'] ?? 'Unknown'); ?></span>
                            </div>
                            
                            <?php if ($product['updated_at'] && $product['updated_at'] !== $product['created_at']): ?>
                            <div class="detail-item">
                                <label>Last Updated:</label>
                                <span><?php echo date('Y-m-d H:i', strtotime($product['updated_at'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Updated By:</label>
                                <span><?php echo htmlspecialchars($product['updated_by_name'] ?? 'Unknown'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
        <footer>
            <p>&copy; 2025 Mini ERP System - Phase 2: Manufacturing Operations</p>
        </footer>
    </div>
</body>
</html>