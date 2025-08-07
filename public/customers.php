<?php
/**
 * Customer Management Page
 * Handles customer creation, editing, and management
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';
require_once '../src/classes/Contact.php';
require_once '../src/classes/Email.php';

$db = new Database();
$auth = new Auth($db);
$contact = new Contact($db);
$email = new Email($db);

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
            
            $stmt = $pdo->prepare("
                INSERT INTO customers (customer_code, customer_name, contact_person, contact_title, email, phone, phone_ext, 
                                     address_line1, address_line2, city, state, zip_code, country,
                                     payment_terms, credit_limit, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $customer_code,
                $_POST['customer_name'],
                $_POST['contact_person'] ?: null,
                $_POST['contact_title'] ?: null,
                $_POST['email'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['phone_ext'] ?: null,
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
            
            // Add primary email to email system if provided
            if (!empty($_POST['email'])) {
                $email_type = $_POST['email_type'] ?: 'contact';
                $email->addCustomerEmail($customer_id, $_POST['email'], $email_type, 'Primary contact email', $current_user['id']);
            }
            
            // Add additional emails if provided
            if (!empty($_POST['additional_emails'])) {
                $additional_emails = array_filter(array_map('trim', explode("\n", $_POST['additional_emails'])));
                foreach ($additional_emails as $add_email) {
                    if (Email::validateEmail($add_email) && !$email->customerEmailExists($customer_id, $add_email)) {
                        $email->addCustomerEmail($customer_id, $add_email, 'department', 'Additional email', $current_user['id']);
                    }
                }
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
                    customer_name = ?, contact_person = ?, contact_title = ?, email = ?, phone = ?, phone_ext = ?,
                    address_line1 = ?, address_line2 = ?, city = ?, state = ?, zip_code = ?, country = ?,
                    payment_terms = ?, credit_limit = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['customer_name'],
                $_POST['contact_person'] ?: null,
                $_POST['contact_title'] ?: null,
                $_POST['email'] ?: null,
                $_POST['phone'] ?: null,
                $_POST['phone_ext'] ?: null,
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

// Get all customers
try {
    $customers = $pdo->query("
        SELECT c.*, u.full_name as created_by_name,
               (SELECT COUNT(*) FROM products WHERE customer_id = c.id AND is_active = 1) as product_count,
               (SELECT COUNT(*) FROM customer_emails WHERE customer_id = c.id AND is_active = 1) as email_count
        FROM customers c
        LEFT JOIN users u ON c.created_by = u.id
        ORDER BY c.customer_name
    ")->fetchAll();
} catch (Exception $e) {
    $customers = [];
    $message = 'Error loading customers: ' . $e->getMessage();
    $message_type = 'error';
}

// Get customer for editing if requested
$edit_customer = null;
if (isset($_GET['edit']) && $can_manage) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_customer = $stmt->fetch();
    } catch (Exception $e) {
        $message = 'Error loading customer for editing: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Customers';

// Include header component
include '../src/includes/header.php';
?>
            <h2><?php echo $page_title; ?> Management</h2>
            
            <div class="page-navigation">
                <a href="customers.php" class="btn btn-primary">Basic Customer Form</a>
                <a href="customers_enhanced.php" class="btn btn-secondary">Enhanced Contacts</a>
                <a href="customers_with_emails.php" class="btn btn-secondary">Email Management</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($can_manage): ?>
            <div class="form-section">
                <h3><?php echo $edit_customer ? 'Edit Customer' : 'Add New Customer'; ?></h3>
                <form method="POST" class="customer-form">
                    <?php if ($edit_customer): ?>
                        <input type="hidden" name="customer_id" value="<?php echo $edit_customer['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_name">Customer Name:</label>
                            <input type="text" id="customer_name" name="customer_name" required 
                                   value="<?php echo htmlspecialchars($edit_customer['customer_name'] ?? ''); ?>"
                                   placeholder="Company name">
                        </div>
                        <div class="form-group">
                            <label for="contact_person">Contact Person:</label>
                            <input type="text" id="contact_person" name="contact_person" 
                                   value="<?php echo htmlspecialchars($edit_customer['contact_person'] ?? ''); ?>"
                                   placeholder="Primary contact name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_title">Contact Title:</label>
                            <input type="text" id="contact_title" name="contact_title" 
                                   value="<?php echo htmlspecialchars($edit_customer['contact_title'] ?? ''); ?>"
                                   placeholder="e.g., Purchasing Manager, Buyer">
                        </div>
                        <div class="form-group">
                            <!-- Spacer for layout -->
                        </div>
                    </div>
                    
                    <h4>Contact Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Primary Email:</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($edit_customer['email'] ?? ''); ?>"
                                   placeholder="contact@customer.com">
                        </div>
                        <div class="form-group">
                            <label for="email_type">Email Type:</label>
                            <select id="email_type" name="email_type">
                                <option value="contact">Main Contact</option>
                                <option value="department">Department Group</option>
                                <option value="purchasing">Purchasing</option>
                                <option value="quality">Quality</option>
                                <option value="shipping">Shipping</option>
                                <option value="billing">Billing</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($edit_customer['phone'] ?? ''); ?>"
                                   placeholder="(555) 123-4567">
                        </div>
                        <div class="form-group">
                            <label for="additional_emails">Additional Emails (one per line):</label>
                            <textarea id="additional_emails" name="additional_emails" rows="3"
                                      placeholder="department@customer.com&#10;purchasing@customer.com&#10;quality@customer.com"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone_ext">Phone Extension:</label>
                            <input type="text" id="phone_ext" name="phone_ext" 
                                   value="<?php echo htmlspecialchars($edit_customer['phone_ext'] ?? ''); ?>"
                                   placeholder="ext. 1234">
                        </div>
                        <div class="form-group">
                            <!-- Spacer for layout -->
                        </div>
                    </div>
                    
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
                            <a href="customers.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
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
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Products</th>
                                <th>Emails</th>
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
                                    <td colspan="<?php echo $can_manage ? '9' : '8'; ?>" class="no-data">No customers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr class="<?php echo !$customer['is_active'] ? 'inactive' : ''; ?>">
                                        <td class="customer-code"><?php echo htmlspecialchars($customer['customer_code']); ?></td>
                                        <td class="customer-name">
                                            <strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong>
                                            <?php if ($customer['contact_person']): ?>
                                                <br><small>
                                                    <?php echo htmlspecialchars($customer['contact_person']); ?>
                                                    <?php if ($customer['contact_title']): ?>
                                                        <br><em><?php echo htmlspecialchars($customer['contact_title']); ?></em>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($customer['phone']): ?>
                                                <div>
                                                    <?php echo htmlspecialchars($customer['phone']); ?>
                                                    <?php if ($customer['phone_ext']): ?>
                                                        ext. <?php echo htmlspecialchars($customer['phone_ext']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($customer['email']): ?>
                                                <div><small><?php echo htmlspecialchars($customer['email']); ?></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $location = array_filter([$customer['city'], $customer['state']]);
                                            echo htmlspecialchars(implode(', ', $location) ?: 'N/A');
                                            ?>
                                        </td>
                                        <td class="product-count"><?php echo $customer['product_count']; ?></td>
                                        <td class="email-count">
                                            <span class="badge"><?php echo $customer['email_count']; ?></span>
                                        </td>
                                        <td>$<?php echo number_format($customer['credit_limit'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $customer['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <?php if ($can_manage): ?>
                                        <td class="actions">
                                            <a href="customers.php?edit=<?php echo $customer['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                            <a href="customers_enhanced.php?view_contacts=<?php echo $customer['id']; ?>" class="btn btn-small btn-info">Contacts</a>
                                            <a href="customers_with_emails.php?manage_emails=<?php echo $customer['id']; ?>" class="btn btn-small btn-success">Emails</a>
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

            <style>
            .page-navigation {
                margin: 20px 0;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 5px;
                text-align: center;
            }
            .page-navigation .btn {
                margin: 0 10px;
            }
            .email-count .badge {
                background-color: #28a745;
                color: white;
                padding: 2px 8px;
                border-radius: 50%;
                font-size: 0.8em;
                font-weight: bold;
            }
            .form-group textarea {
                resize: vertical;
            }
            .actions .btn {
                margin: 2px;
            }
            </style>

<?php
// Include footer component
include '../src/includes/footer.php';
?>