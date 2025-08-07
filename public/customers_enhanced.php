<?php
/**
 * Enhanced Customer Management Page with Multiple Contacts
 * Uses normalized contact structure
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';
require_once '../src/classes/Contact.php';

$db = new Database();
$auth = new Auth($db);
$contact = new Contact($db);

// Require authentication
$auth->requireAuth('login.php');
$current_user = $auth->getCurrentUser();

// Check permissions - Admin, supervisor, and material handlers can manage customers
$can_manage = $auth->hasRole(['admin', 'supervisor', 'material_handler']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    if (isset($_POST['add_customer'])) {
        try {
            $pdo->beginTransaction();
            
            // Generate customer code
            $latest_customer = $pdo->query("SELECT customer_code FROM customers WHERE customer_code LIKE 'CUST%' ORDER BY customer_code DESC LIMIT 1")->fetch();
            if ($latest_customer) {
                $num = (int)substr($latest_customer['customer_code'], 4) + 1;
            } else {
                $num = 1;
            }
            $customer_code = 'CUST' . str_pad($num, 3, '0', STR_PAD_LEFT);
            
            // Create customer (without old contact fields)
            $stmt = $pdo->prepare("
                INSERT INTO customers (customer_code, customer_name, address_line1, address_line2, 
                                     city, state, zip_code, country, payment_terms, credit_limit, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $customer_code,
                $_POST['customer_name'],
                $_POST['address_line1'] ?: null,
                $_POST['address_line2'] ?: null,
                $_POST['city'] ?: null,
                $_POST['state'] ?: null,
                $_POST['zip_code'] ?: null,
                $_POST['country'] ?: 'USA',
                $_POST['payment_terms'] ?: null,
                $_POST['credit_limit'] ?: 0.00,
                $_POST['notes'] ?: null,
                $current_user['id']
            ]);
            
            $customer_id = $pdo->lastInsertId();
            
            // Create primary contact if provided
            if (!empty($_POST['contact_first_name'])) {
                $contact_data = [
                    'first_name' => $_POST['contact_first_name'],
                    'last_name' => $_POST['contact_last_name'] ?: '',
                    'email' => $_POST['contact_email'] ?: null,
                    'phone' => $_POST['contact_phone'] ?: null,
                    'phone_ext' => $_POST['contact_phone_ext'] ?: null,
                    'mobile_phone' => $_POST['contact_mobile_phone'] ?: null,
                    'job_title' => $_POST['contact_job_title'] ?: null,
                    'department' => $_POST['contact_department'] ?: null,
                    'notes' => null
                ];
                
                $contact_id = $contact->createContact($contact_data, $current_user['id']);
                $contact->linkToCustomer($contact_id, $customer_id, 'Primary', true);
            }
            
            $pdo->commit();
            $message = "Customer added successfully! Code: $customer_code";
            $message_type = 'success';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error adding customer: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['edit_customer'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE customers SET 
                    customer_name = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, 
                    zip_code = ?, country = ?, payment_terms = ?, credit_limit = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['customer_name'],
                $_POST['address_line1'] ?: null,
                $_POST['address_line2'] ?: null,
                $_POST['city'] ?: null,
                $_POST['state'] ?: null,
                $_POST['zip_code'] ?: null,
                $_POST['country'] ?: 'USA',
                $_POST['payment_terms'] ?: null,
                $_POST['credit_limit'] ?: 0.00,
                $_POST['notes'] ?: null,
                $_POST['customer_id']
            ]);
            $message = 'Customer updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating customer: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['add_contact'])) {
        try {
            $contact_data = [
                'first_name' => $_POST['contact_first_name'],
                'last_name' => $_POST['contact_last_name'] ?: '',
                'email' => $_POST['contact_email'] ?: null,
                'phone' => $_POST['contact_phone'] ?: null,
                'phone_ext' => $_POST['contact_phone_ext'] ?: null,
                'mobile_phone' => $_POST['contact_mobile_phone'] ?: null,
                'job_title' => $_POST['contact_job_title'] ?: null,
                'department' => $_POST['contact_department'] ?: null,
                'notes' => $_POST['contact_notes'] ?: null
            ];
            
            $contact_id = $contact->createContact($contact_data, $current_user['id']);
            $is_primary = isset($_POST['is_primary']) && $_POST['is_primary'] == '1';
            $contact->linkToCustomer($contact_id, $_POST['customer_id'], $_POST['contact_role'], $is_primary);
            
            $message = 'Contact added successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error adding contact: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['toggle_active'])) {
        try {
            $stmt = $pdo->prepare("UPDATE customers SET is_active = !is_active WHERE id = ?");
            $stmt->execute([$_POST['customer_id']]);
            $message = 'Customer status updated successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating customer status: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all customers with primary contact info
try {
    $customers = $pdo->query("
        SELECT c.*, u.full_name as created_by_name,
               (SELECT COUNT(*) FROM products WHERE customer_id = c.id AND is_active = 1) as product_count,
               pc.full_name as primary_contact_name,
               pc.email as primary_contact_email,
               pc.phone as primary_contact_phone,
               pc.job_title as primary_contact_title
        FROM customers c
        LEFT JOIN users u ON c.created_by = u.id
        LEFT JOIN customer_contacts cc ON c.id = cc.customer_id AND cc.is_primary = TRUE
        LEFT JOIN contacts pc ON cc.contact_id = pc.id
        ORDER BY c.customer_name
    ")->fetchAll();
} catch (Exception $e) {
    $customers = [];
    $message = 'Error loading customers: ' . $e->getMessage();
    $message_type = 'error';
}

// Get customer for editing if requested
$edit_customer = null;
$customer_contacts = [];
if (isset($_GET['edit']) && $can_manage) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_customer = $stmt->fetch();
        
        if ($edit_customer) {
            $customer_contacts = $contact->getCustomerContacts($edit_customer['id']);
        }
    } catch (Exception $e) {
        $message = 'Error loading customer for editing: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get customer contacts if viewing contacts
$view_contacts_for = null;
$view_contacts = [];
if (isset($_GET['view_contacts'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$_GET['view_contacts']]);
        $view_contacts_for = $stmt->fetch();
        
        if ($view_contacts_for) {
            $view_contacts = $contact->getCustomerContacts($view_contacts_for['id']);
        }
    } catch (Exception $e) {
        $message = 'Error loading contacts: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Enhanced Customer Management';

// Include header component
include '../src/includes/header.php';
?>
            <h2><?php echo $page_title; ?></h2>
            <p><small>Now supporting multiple contacts per customer with roles</small></p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($view_contacts_for): ?>
            <div class="contacts-section">
                <h3>Contacts for <?php echo htmlspecialchars($view_contacts_for['customer_name']); ?></h3>
                
                <?php if ($can_manage): ?>
                <div class="form-section">
                    <h4>Add New Contact</h4>
                    <form method="POST" class="contact-form">
                        <input type="hidden" name="customer_id" value="<?php echo $view_contacts_for['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_first_name">First Name:</label>
                                <input type="text" id="contact_first_name" name="contact_first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_last_name">Last Name:</label>
                                <input type="text" id="contact_last_name" name="contact_last_name">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_email">Email:</label>
                                <input type="email" id="contact_email" name="contact_email">
                            </div>
                            <div class="form-group">
                                <label for="contact_phone">Phone:</label>
                                <input type="tel" id="contact_phone" name="contact_phone">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_phone_ext">Extension:</label>
                                <input type="text" id="contact_phone_ext" name="contact_phone_ext">
                            </div>
                            <div class="form-group">
                                <label for="contact_mobile_phone">Mobile:</label>
                                <input type="tel" id="contact_mobile_phone" name="contact_mobile_phone">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_job_title">Job Title:</label>
                                <input type="text" id="contact_job_title" name="contact_job_title">
                            </div>
                            <div class="form-group">
                                <label for="contact_department">Department:</label>
                                <input type="text" id="contact_department" name="contact_department">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_role">Role:</label>
                                <select id="contact_role" name="contact_role" required>
                                    <?php foreach (Contact::getCustomerRoles() as $key => $label): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_primary" value="1">
                                    Primary Contact
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_notes">Notes:</label>
                            <textarea id="contact_notes" name="contact_notes" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" name="add_contact" class="btn btn-primary">Add Contact</button>
                        <a href="customers_enhanced.php" class="btn btn-secondary">Back to Customers</a>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="contacts-list">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Contact Info</th>
                                <th>Job Title</th>
                                <th>Department</th>
                                <th>Primary</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($view_contacts)): ?>
                                <tr>
                                    <td colspan="6" class="no-data">No contacts found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($view_contacts as $cnt): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cnt['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cnt['role']); ?></td>
                                        <td>
                                            <?php if ($cnt['email']): ?>
                                                <div><?php echo htmlspecialchars($cnt['email']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($cnt['phone']): ?>
                                                <div>
                                                    <?php echo htmlspecialchars($cnt['phone']); ?>
                                                    <?php if ($cnt['phone_ext']): ?>
                                                        ext. <?php echo htmlspecialchars($cnt['phone_ext']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($cnt['mobile_phone']): ?>
                                                <div><small>Mobile: <?php echo htmlspecialchars($cnt['mobile_phone']); ?></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($cnt['job_title'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cnt['department'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if ($cnt['is_primary']): ?>
                                                <span class="status-badge status-active">Primary</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="customers_enhanced.php" class="btn btn-secondary">Back to Customers</a>
                </div>
            </div>
            
            <?php elseif ($can_manage && ($edit_customer || !isset($_GET['edit']))): ?>
            <div class="form-section">
                <h3><?php echo $edit_customer ? 'Edit Customer' : 'Add New Customer'; ?></h3>
                <form method="POST" class="customer-form">
                    <?php if ($edit_customer): ?>
                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                    <?php endif; ?>
                    
                    <h4>Company Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_name">Customer Name:</label>
                            <input type="text" id="customer_name" name="customer_name" required 
                                   value="<?php echo htmlspecialchars($edit_customer['customer_name'] ?? ''); ?>"
                                   placeholder="Company name">
                        </div>
                    </div>
                    
                    <?php if (!$edit_customer): ?>
                    <h4>Primary Contact (Optional)</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_first_name">First Name:</label>
                            <input type="text" id="contact_first_name" name="contact_first_name" 
                                   placeholder="Contact first name">
                        </div>
                        <div class="form-group">
                            <label for="contact_last_name">Last Name:</label>
                            <input type="text" id="contact_last_name" name="contact_last_name" 
                                   placeholder="Contact last name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_email">Email:</label>
                            <input type="email" id="contact_email" name="contact_email" 
                                   placeholder="contact@customer.com">
                        </div>
                        <div class="form-group">
                            <label for="contact_phone">Phone:</label>
                            <input type="tel" id="contact_phone" name="contact_phone" 
                                   placeholder="(555) 123-4567">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_phone_ext">Extension:</label>
                            <input type="text" id="contact_phone_ext" name="contact_phone_ext" 
                                   placeholder="ext. 1234">
                        </div>
                        <div class="form-group">
                            <label for="contact_mobile_phone">Mobile:</label>
                            <input type="tel" id="contact_mobile_phone" name="contact_mobile_phone" 
                                   placeholder="(555) 123-4567">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_job_title">Job Title:</label>
                            <input type="text" id="contact_job_title" name="contact_job_title" 
                                   placeholder="e.g., Purchasing Manager">
                        </div>
                        <div class="form-group">
                            <label for="contact_department">Department:</label>
                            <input type="text" id="contact_department" name="contact_department" 
                                   placeholder="e.g., Purchasing">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <h4>Address Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="address_line1">Address Line 1:</label>
                            <input type="text" id="address_line1" name="address_line1" 
                                   value="<?php echo htmlspecialchars($edit_customer['address_line1'] ?? ''); ?>"
                                   placeholder="Street address">
                        </div>
                        <div class="form-group">
                            <label for="address_line2">Address Line 2:</label>
                            <input type="text" id="address_line2" name="address_line2" 
                                   value="<?php echo htmlspecialchars($edit_customer['address_line2'] ?? ''); ?>"
                                   placeholder="Suite, unit, etc. (optional)">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City:</label>
                            <input type="text" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($edit_customer['city'] ?? ''); ?>"
                                   placeholder="City">
                        </div>
                        <div class="form-group">
                            <label for="state">State:</label>
                            <input type="text" id="state" name="state" 
                                   value="<?php echo htmlspecialchars($edit_customer['state'] ?? ''); ?>"
                                   placeholder="State/Province">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code">ZIP Code:</label>
                            <input type="text" id="zip_code" name="zip_code" 
                                   value="<?php echo htmlspecialchars($edit_customer['zip_code'] ?? ''); ?>"
                                   placeholder="ZIP/Postal code">
                        </div>
                        <div class="form-group">
                            <label for="country">Country:</label>
                            <input type="text" id="country" name="country" 
                                   value="<?php echo htmlspecialchars($edit_customer['country'] ?? 'USA'); ?>"
                                   placeholder="Country">
                        </div>
                    </div>
                    
                    <h4>Business Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_terms">Payment Terms:</label>
                            <input type="text" id="payment_terms" name="payment_terms" 
                                   value="<?php echo htmlspecialchars($edit_customer['payment_terms'] ?? ''); ?>"
                                   placeholder="e.g., Net 30, COD">
                        </div>
                        <div class="form-group">
                            <label for="credit_limit">Credit Limit ($):</label>
                            <input type="number" id="credit_limit" name="credit_limit" step="0.01" min="0" 
                                   value="<?php echo htmlspecialchars($edit_customer['credit_limit'] ?? '0.00'); ?>"
                                   placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="Additional notes about this customer"><?php echo htmlspecialchars($edit_customer['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="<?php echo $edit_customer ? 'edit_customer' : 'add_customer'; ?>" class="btn btn-primary">
                            <?php echo $edit_customer ? 'Update Customer' : 'Add Customer'; ?>
                        </button>
                        <?php if ($edit_customer): ?>
                            <a href="customers_enhanced.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <?php if ($edit_customer && !empty($customer_contacts)): ?>
                <div class="existing-contacts">
                    <h4>Existing Contacts</h4>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Contact Info</th>
                                <th>Primary</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer_contacts as $cnt): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cnt['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cnt['role']); ?></td>
                                    <td>
                                        <?php if ($cnt['email']): ?>
                                            <div><?php echo htmlspecialchars($cnt['email']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($cnt['phone']): ?>
                                            <div><?php echo htmlspecialchars($cnt['phone']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($cnt['is_primary']): ?>
                                            <span class="status-badge status-active">Primary</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><a href="customers_enhanced.php?view_contacts=<?php echo $edit_customer['id']; ?>" class="btn btn-secondary">Manage Contacts</a></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="customers-list">
                <h3>Customers List</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Primary Contact</th>
                                <th>Location</th>
                                <th>Products</th>
                                <th>Credit Limit</th>
                                <th>Status</th>
                                <?php if ($can_manage): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="<?php echo $can_manage ? '8' : '7'; ?>" class="no-data">No customers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr class="<?php echo !$customer['is_active'] ? 'inactive' : ''; ?>">
                                        <td class="customer-code"><?php echo htmlspecialchars($customer['customer_code']); ?></td>
                                        <td class="customer-name">
                                            <strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($customer['primary_contact_name']): ?>
                                                <div><strong><?php echo htmlspecialchars($customer['primary_contact_name']); ?></strong></div>
                                                <?php if ($customer['primary_contact_title']): ?>
                                                    <div><small><?php echo htmlspecialchars($customer['primary_contact_title']); ?></small></div>
                                                <?php endif; ?>
                                                <?php if ($customer['primary_contact_email']): ?>
                                                    <div><small><?php echo htmlspecialchars($customer['primary_contact_email']); ?></small></div>
                                                <?php endif; ?>
                                                <?php if ($customer['primary_contact_phone']): ?>
                                                    <div><small><?php echo htmlspecialchars($customer['primary_contact_phone']); ?></small></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <em>No primary contact</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $location = array_filter([$customer['city'], $customer['state']]);
                                            echo htmlspecialchars(implode(', ', $location) ?: 'N/A');
                                            ?>
                                        </td>
                                        <td class="product-count"><?php echo $customer['product_count']; ?></td>
                                        <td>$<?php echo number_format($customer['credit_limit'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $customer['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <?php if ($can_manage): ?>
                                        <td class="actions">
                                            <a href="customers_enhanced.php?edit=<?php echo $customer['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                            <a href="customers_enhanced.php?view_contacts=<?php echo $customer['id']; ?>" class="btn btn-small btn-info">Contacts</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                <button type="submit" name="toggle_active" class="btn btn-small btn-<?php echo $customer['is_active'] ? 'warning' : 'success'; ?>">
                                                    <?php echo $customer['is_active'] ? 'Deactivate' : 'Activate'; ?>
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