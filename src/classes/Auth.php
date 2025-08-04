<?php
/**
 * Authentication Class for Manufacturing ERP
 * Handles user login, logout, and role-based access control
 */

class Auth {
    private $db;
    private $session_timeout = 3600; // 1 hour
    
    public function __construct(Database $database) {
        $this->db = $database->connect();
        $this->session_timeout = SESSION_LIFETIME;
        
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session timeout
        $this->checkSessionTimeout();
    }
    
    /**
     * Authenticate user login
     */
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, password_hash, full_name, role, is_active 
                FROM users 
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Log successful login
                $this->logAuditEvent('users', $user['id'], 'LOGIN', null, [
                    'login_time' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'full_name' => $user['full_name'],
                        'role' => $user['role']
                    ]
                ];
            }
            
            // Log failed login attempt
            $this->logAuditEvent('users', 0, 'LOGIN_FAILED', null, [
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return ['success' => false, 'message' => 'Invalid credentials'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logAuditEvent('users', $_SESSION['user_id'], 'LOGOUT', null, [
                'logout_time' => date('Y-m-d H:i:s')
            ]);
        }
        
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']);
    }
    
    /**
     * Get current user information
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }
    
    /**
     * Check if user has required role
     */
    public function hasRole($required_roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user_role = $_SESSION['role'];
        
        // Convert single role to array
        if (!is_array($required_roles)) {
            $required_roles = [$required_roles];
        }
        
        // Admin has access to everything
        if ($user_role === 'admin') {
            return true;
        }
        
        return in_array($user_role, $required_roles);
    }
    
    /**
     * Require authentication (redirect if not logged in)
     */
    public function requireAuth($redirect_to = '/login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: $redirect_to");
            exit;
        }
    }
    
    /**
     * Require specific role (show error if insufficient permissions)
     */
    public function requireRole($required_roles, $error_message = 'Insufficient permissions') {
        $this->requireAuth();
        
        if (!$this->hasRole($required_roles)) {
            throw new Exception($error_message);
        }
    }
    
    /**
     * Check session timeout
     */
    private function checkSessionTimeout() {
        if ($this->isLoggedIn()) {
            $last_activity = $_SESSION['last_activity'] ?? 0;
            
            if ((time() - $last_activity) > $this->session_timeout) {
                $this->logout();
                return false;
            }
            
            $_SESSION['last_activity'] = time();
        }
        
        return true;
    }
    
    /**
     * Update user's last login timestamp
     */
    private function updateLastLogin($user_id) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    
    /**
     * Create new user (admin only)
     */
    public function createUser($username, $email, $password, $full_name, $role = 'viewer') {
        $this->requireRole(['admin']);
        
        try {
            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Create password hash
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, full_name, role, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $email, $password_hash, $full_name, $role, $_SESSION['user_id']]);
            $new_user_id = $this->db->lastInsertId();
            
            // Log user creation
            $this->logAuditEvent('users', $new_user_id, 'INSERT', null, [
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'role' => $role
            ]);
            
            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $new_user_id];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Log audit events for ISO compliance
     */
    private function logAuditEvent($table_name, $record_id, $action, $old_values = null, $new_values = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, user_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $user_id = $_SESSION['user_id'] ?? 0;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt->execute([
                $table_name,
                $record_id,
                $action,
                $old_values ? json_encode($old_values) : null,
                $new_values ? json_encode($new_values) : null,
                $user_id,
                $ip_address,
                substr($user_agent, 0, 500) // Truncate long user agents
            ]);
            
        } catch (Exception $e) {
            // Don't throw exception for audit logging failures
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get user roles for dropdowns
     */
    public static function getUserRoles() {
        return [
            'admin' => 'Administrator',
            'supervisor' => 'Supervisor',
            'material_handler' => 'Material Handler', 
            'quality_inspector' => 'Quality Inspector',
            'viewer' => 'Viewer'
        ];
    }
}