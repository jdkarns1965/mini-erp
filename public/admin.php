<?php
/**
 * Administration Page
 * User management, system configuration, and audit logs
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Check permissions - Only admins can access admin panel
$auth->requireRole(['admin']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        try {
            $result = $auth->createUser(
                $_POST['username'],
                $_POST['email'], 
                $_POST['password'],
                $_POST['full_name'],
                $_POST['role']
            );
            
            if ($result['success']) {
                $message = $result['message'];
                $message_type = 'success';
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'Error creating user: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['deactivate_user'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $message = 'User deactivated successfully';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error deactivating user: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['activate_user'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $message = 'User activated successfully';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error activating user: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get current section
$section = $_GET['section'] ?? 'users';

// Get users
$users = [];
try {
    $users = $pdo->query("
        SELECT u.*, creator.full_name as created_by_name
        FROM users u
        LEFT JOIN users creator ON u.created_by = creator.id
        ORDER BY u.created_date DESC
    ")->fetchAll();
} catch (Exception $e) {
    $message = 'Error loading users: ' . $e->getMessage();
    $message_type = 'error';
}

// Get audit log
$audit_logs = [];
if ($section === 'audit') {
    try {
        $audit_logs = $pdo->query("
            SELECT a.*, u.full_name as user_name
            FROM audit_log a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_date DESC
            LIMIT 100
        ")->fetchAll();
    } catch (Exception $e) {
        $message = 'Error loading audit logs: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Administration';

// Include header component
include '../src/includes/header.php';
?>
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
                    
                    <li class="nav-dropdown">
                        <a href="#">Materials & Inventory</a>
                        <ul class="dropdown-menu">
                            <li><a href="materials.php">Materials Master</a></li>
                            <li><a href="inventory.php">Inventory Management</a></li>
                            <li><a href="recipes.php">Recipes</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-dropdown">
                        <a href="#">Production</a>
                        <ul class="dropdown-menu">
                            <li><a href="products.php">Products</a></li>
                            <li><a href="jobs.php">Production Jobs</a></li>
                            <li><a href="traceability.php">Traceability</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-dropdown">
                        <a href="#">Business Partners</a>
                        <ul class="dropdown-menu">
                            <li><a href="suppliers.php">Suppliers</a></li>
                            <li><a href="customers.php">Customers</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-dropdown">
                        <a href="#">System</a>
                        <ul class="dropdown-menu">
                            <li><a href="reports.php">Reports</a></li>
                            <li><a href="admin.php" class="active">Administration</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <h2><?php echo $page_title; ?></h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Admin Navigation -->
            <div class="admin-nav">
                <a href="?section=users" class="admin-nav-btn <?php echo $section === 'users' ? 'active' : ''; ?>">
                    👥 User Management
                </a>
                <a href="?section=audit" class="admin-nav-btn <?php echo $section === 'audit' ? 'active' : ''; ?>">
                    📋 Audit Log
                </a>
                <a href="?section=system" class="admin-nav-btn <?php echo $section === 'system' ? 'active' : ''; ?>">
                    ⚙️ System Settings
                </a>
            </div>
            
            <?php if ($section === 'users'): ?>
                <!-- User Management Section -->
                <div class="admin-section">
                    <h3>User Management</h3>
                    
                    <!-- Create User Form -->
                    <div class="form-section">
                        <h4>Create New User</h4>
                        <form method="POST" class="user-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username">Username:</label>
                                    <input type="text" id="username" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name">Full Name:</label>
                                    <input type="text" id="full_name" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password:</label>
                                    <input type="password" id="password" name="password" required minlength="6">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="role">Role:</label>
                                    <select id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        <?php foreach (Auth::getUserRoles() as $role_key => $role_name): ?>
                                            <option value="<?php echo $role_key; ?>"><?php echo $role_name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                        </form>
                    </div>
                    
                    <!-- Users List -->
                    <div class="users-list">
                        <h4>Existing Users</h4>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="username"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="role"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></td>
                                            <td class="status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_date'])); ?></td>
                                            <td>
                                                <?php if ($user['last_login']): ?>
                                                    <?php echo date('M j, Y', strtotime($user['last_login'])); ?>
                                                <?php else: ?>
                                                    <em>Never</em>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <?php if ($user['id'] !== $current_user['id']): ?>
                                                    <?php if ($user['is_active']): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="deactivate_user" class="btn btn-small btn-danger" 
                                                                    onclick="return confirm('Deactivate this user?')">Deactivate</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="activate_user" class="btn btn-small">Activate</button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <em>Current User</em>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($section === 'audit'): ?>
                <!-- Audit Log Section -->
                <div class="admin-section">
                    <h3>Audit Log</h3>
                    <p>Complete audit trail of system changes for ISO compliance</p>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($audit_logs)): ?>
                                    <tr>
                                        <td colspan="7" class="no-data">No audit logs found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($audit_logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y H:i:s', strtotime($log['created_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                                            <td class="action <?php echo strtolower($log['action']); ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                                            <td><?php echo htmlspecialchars($log['record_id']); ?></td>
                                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            <td class="details">
                                                <?php if ($log['new_values']): ?>
                                                    <button class="btn btn-small" onclick="showDetails('<?php echo htmlspecialchars($log['id']); ?>')">
                                                        View
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($section === 'system'): ?>
                <!-- System Settings Section -->
                <div class="admin-section">
                    <h3>System Settings</h3>
                    <p>Configuration and system maintenance</p>
                    
                    <div class="settings-grid">
                        <div class="setting-card">
                            <h4>📊 Database Status</h4>
                            <p>Monitor database performance and connections</p>
                            <button class="btn btn-secondary" onclick="alert('Database monitoring coming soon')">Check Status</button>
                        </div>
                        
                        <div class="setting-card">
                            <h4>🔄 System Backup</h4>
                            <p>Create database backup for disaster recovery</p>
                            <button class="btn btn-secondary" onclick="alert('Backup functionality coming soon')">Create Backup</button>
                        </div>
                        
                        <div class="setting-card">
                            <h4>🔧 Configuration</h4>
                            <p>Update system settings and parameters</p>
                            <div class="config-info">
                                <p><strong>Environment:</strong> <?php echo APP_ENV; ?></p>
                                <p><strong>Debug Mode:</strong> <?php echo APP_DEBUG ? 'Enabled' : 'Disabled'; ?></p>
                                <p><strong>Session Lifetime:</strong> <?php echo SESSION_LIFETIME; ?> seconds</p>
                            </div>
                        </div>
                        
                        <div class="setting-card">
                            <h4>📈 System Stats</h4>
                            <p>View system usage and performance metrics</p>
                            <div class="stats-mini">
                                <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                                <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                <p><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <script>
        function showDetails(logId) {
            alert('Audit log details viewer will be implemented in Phase 1.4');
        }
    </script>
<?php
// Include footer component
include '../src/includes/footer.php';
?>