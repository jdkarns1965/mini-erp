<?php
/**
 * Production Jobs Page
 * Handles job creation, material assignment, and production tracking
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
$can_create = $auth->hasRole(['admin', 'supervisor', 'material_handler']);
$can_approve = $auth->hasRole(['admin', 'supervisor']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_job']) && $can_create) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO jobs (job_number, part_number, quantity_required, recipe_id, 
                                priority, created_by, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $_POST['job_number'],
                $_POST['part_number'],
                $_POST['quantity_required'],
                $_POST['recipe_id'],
                $_POST['priority'],
                $current_user['id']
            ]);
            $message = 'Production job created successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error creating job: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['start_job']) && $can_approve) {
        try {
            $stmt = $pdo->prepare("
                UPDATE jobs 
                SET status = 'in_progress', started_by = ?, start_date = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$current_user['id'], $_POST['job_id']]);
            $message = 'Job started successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error starting job: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get jobs with recipe and part details
try {
    $jobs = $pdo->query("
        SELECT j.*, 
               r.part_name, r.base_material_id, r.concentrate_material_id, r.concentrate_percentage,
               bm.material_code as base_code, bm.material_name as base_name,
               cm.material_code as concentrate_code, cm.material_name as concentrate_name,
               u1.full_name as created_by_name,
               u2.full_name as started_by_name
        FROM jobs j
        LEFT JOIN recipes r ON j.recipe_id = r.id
        LEFT JOIN materials bm ON r.base_material_id = bm.id
        LEFT JOIN materials cm ON r.concentrate_material_id = cm.id
        LEFT JOIN users u1 ON j.created_by = u1.id
        LEFT JOIN users u2 ON j.started_by = u2.id
        ORDER BY j.priority DESC, j.created_date DESC
    ")->fetchAll();
} catch (Exception $e) {
    $jobs = [];
    $message = 'Error loading jobs: ' . $e->getMessage();
    $message_type = 'error';
}

// Get approved recipes for dropdown
try {
    $recipes = $pdo->query("
        SELECT r.id, r.part_number, r.part_name, r.version,
               bm.material_code as base_code,
               cm.material_code as concentrate_code, r.concentrate_percentage
        FROM recipes r
        JOIN materials bm ON r.base_material_id = bm.id
        LEFT JOIN materials cm ON r.concentrate_material_id = cm.id
        WHERE r.status = 'approved'
        ORDER BY r.part_number
    ")->fetchAll();
} catch (Exception $e) {
    $recipes = [];
}

$page_title = 'Production Jobs';
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
                    <li><a href="jobs.php" class="active">Production Jobs</a></li>
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
            
            <?php if ($can_create): ?>
            <div class="form-section">
                <h3>Create Production Job</h3>
                <div class="job-workflow">
                    <p><strong>Job Creation Workflow:</strong></p>
                    <ol>
                        <li>Select approved recipe for the part</li>
                        <li>System will auto-suggest materials per FIFO</li>
                        <li>Material handler confirms material selection</li>
                        <li>Job ready for production start</li>
                    </ol>
                </div>
                
                <form method="POST" class="job-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="job_number">Job Number:</label>
                            <input type="text" id="job_number" name="job_number" required 
                                   placeholder="e.g., JOB-001-2025">
                        </div>
                        <div class="form-group">
                            <label for="recipe_id">Part & Recipe:</label>
                            <select id="recipe_id" name="recipe_id" required onchange="updatePartNumber(this)">
                                <option value="">Select Approved Recipe</option>
                                <?php foreach ($recipes as $recipe): ?>
                                    <option value="<?php echo $recipe['id']; ?>" 
                                            data-part="<?php echo htmlspecialchars($recipe['part_number']); ?>">
                                        <?php echo htmlspecialchars($recipe['part_number'] . ' - ' . $recipe['part_name'] . ' (v' . $recipe['version'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="part_number">Part Number:</label>
                            <input type="text" id="part_number" name="part_number" readonly>
                        </div>
                        <div class="form-group">
                            <label for="quantity_required">Quantity Required:</label>
                            <input type="number" id="quantity_required" name="quantity_required" required 
                                   min="1" placeholder="Number of parts">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="priority">Priority:</label>
                            <select id="priority" name="priority" required>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="create_job" class="btn btn-primary">Create Job</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="jobs-list">
                <h3>Production Jobs</h3>
                <div class="jobs-filter">
                    <button class="filter-btn active" onclick="filterJobs('all')">All Jobs</button>
                    <button class="filter-btn" onclick="filterJobs('pending')">Pending</button>
                    <button class="filter-btn" onclick="filterJobs('in_progress')">In Progress</button>
                    <button class="filter-btn" onclick="filterJobs('completed')">Completed</button>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Job Number</th>
                                <th>Part</th>
                                <th>Recipe</th>
                                <th>Quantity</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jobs)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">No production jobs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($jobs as $job): ?>
                                    <tr class="job-row" data-status="<?php echo $job['status']; ?>">
                                        <td class="job-number"><?php echo htmlspecialchars($job['job_number']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($job['part_number']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($job['part_name'] ?? 'No recipe'); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($job['base_code']): ?>
                                                <strong>Base:</strong> <?php echo htmlspecialchars($job['base_code']); ?><br>
                                                <?php if ($job['concentrate_code']): ?>
                                                    <strong>Color:</strong> <?php echo htmlspecialchars($job['concentrate_code']); ?> 
                                                    (<?php echo number_format($job['concentrate_percentage'], 1); ?>%)
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <em>No recipe assigned</em>
                                            <?php endif; ?>
                                        </td>
                                        <td class="quantity"><?php echo number_format($job['quantity_required']); ?></td>
                                        <td class="priority <?php echo $job['priority']; ?>">
                                            <?php echo ucfirst($job['priority']); ?>
                                        </td>
                                        <td class="status <?php echo $job['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($job['created_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($job['created_by_name'] ?? 'Unknown'); ?></td>
                                        <td class="actions">
                                            <?php if ($job['status'] === 'pending' && $can_approve): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                    <button type="submit" name="start_job" class="btn btn-small">Start</button>
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
        </main>
        
        <footer>
            <p>&copy; 2025 Mini ERP System</p>
        </footer>
    </div>
    
    <script>
        function updatePartNumber(select) {
            const selectedOption = select.options[select.selectedIndex];
            const partNumber = selectedOption.getAttribute('data-part');
            document.getElementById('part_number').value = partNumber || '';
        }
        
        function filterJobs(status) {
            const rows = document.querySelectorAll('.job-row');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update button states
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter rows
            rows.forEach(row => {
                if (status === 'all' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>