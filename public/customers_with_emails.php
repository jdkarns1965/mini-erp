<?php
/**
 * Customer Management with Email Support
 * Handles customers, contacts, and department/group emails
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

// Check permissions
$can_manage = $auth->hasRole(['admin', 'supervisor', 'material_handler']);

// Get database connection
$pdo = $db->connect();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    if (isset($_POST['add_emails'])) {
        try {
            $added_count = 0;
            $errors = [];
            
            if (isset($_POST['emails']) && is_array($_POST['emails'])) {
                foreach ($_POST['emails'] as $index => $email_data) {
                    if (empty($email_data['email'])) {
                        continue; // Skip empty email fields
                    }
                    
                    if (!Email::validateEmail($email_data['email'])) {
                        $errors[] = "Invalid email format: {$email_data['email']}";
                        continue;
                    }
                    
                    if ($email->customerEmailExists($_POST['customer_id'], $email_data['email'])) {
                        $errors[] = "Email already exists: {$email_data['email']}";
                        continue;
                    }
                    
                    $email->addCustomerEmail(
                        $_POST['customer_id'],
                        $email_data['email'],
                        $email_data['type'] ?: 'contact',
                        $email_data['description'] ?: null,
                        $current_user['id']
                    );
                    $added_count++;
                }
            }
            
            if ($added_count > 0) {
                $message = "Successfully added {$added_count} email(s)!";
                if (!empty($errors)) {
                    $message .= " Some emails had errors: " . implode(', ', $errors);
                }
                $message_type = 'success';
            } else {
                $message = empty($errors) ? 'No emails to add' : 'Errors: ' . implode(', ', $errors);
                $message_type = 'error';
            }
            
        } catch (Exception $e) {
            $message = 'Error adding emails: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['remove_email'])) {
        try {
            $email->removeCustomerEmail($_POST['email_id']);
            $message = 'Email removed successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error removing email: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
    
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
            
            // Create customer
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
            
            $pdo->commit();
            $message = "Customer added successfully! Code: $customer_code";
            $message_type = 'success';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error adding customer: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get customers with email counts
try {
    $customers = $pdo->query("
        SELECT c.*, u.full_name as created_by_name,
               (SELECT COUNT(*) FROM products WHERE customer_id = c.id AND is_active = 1) as product_count,
               (SELECT COUNT(*) FROM customer_emails WHERE customer_id = c.id AND is_active = 1) as email_count,
               pc.full_name as primary_contact_name,
               pc.email as primary_contact_email,
               pc.phone as primary_contact_phone
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

// Handle email management view
$manage_emails_for = null;
$customer_emails = [];
if (isset($_GET['manage_emails'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$_GET['manage_emails']]);
        $manage_emails_for = $stmt->fetch();
        
        if ($manage_emails_for) {
            $customer_emails = $email->getCustomerEmails($manage_emails_for['id']);
        }
    } catch (Exception $e) {
        $message = 'Error loading customer emails: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$page_title = 'Customer Management with Emails';

// Include header component
include '../src/includes/header.php';
?>
            <h2><?php echo $page_title; ?></h2>
            <p><small>Full customer management including contacts and department emails</small></p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($manage_emails_for): ?>
            <div class="email-management-section">
                <h3>Email Management for <?php echo htmlspecialchars($manage_emails_for['customer_name']); ?></h3>
                
                <?php if ($can_manage): ?>
                <div class="form-section">
                    <h4>Add New Email</h4>
                    <form method="POST" class="email-form">
                        <input type="hidden" name="customer_id" value="<?php echo $manage_emails_for['id']; ?>">
                        
                        <div id="email-fields-container">
                            <div class="email-field-group" data-index="0">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email_0">Email Address:</label>
                                        <input type="email" id="email_0" name="emails[0][email]" 
                                               placeholder="contact@company.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="email_type_0">Email Type:</label>
                                        <select id="email_type_0" name="emails[0][type]">
                                            <option value="contact">Main Contact</option>
                                            <option value="department">Department Group</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="description_0">Description:</label>
                                        <input type="text" id="description_0" name="emails[0][description]" 
                                               placeholder="e.g., Returns team">
                                    </div>
                                    <div class="form-group email-actions">
                                        <button type="button" class="btn btn-small btn-danger remove-email" style="display: none;">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="add-email-field" class="btn btn-secondary">+ Add Email</button>
                            <button type="submit" name="add_emails" class="btn btn-primary">Save Emails</button>
                            <a href="customers_with_emails.php" class="btn btn-secondary">Back to Customers</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="emails-list">
                    <h4>Current Emails</h4>
                    <?php if (empty($customer_emails)): ?>
                        <p class="no-data">No emails found for this customer.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Added</th>
                                    <?php if ($can_manage): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer_emails as $cust_email): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($cust_email['email']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="email-type-badge type-<?php echo $cust_email['email_type']; ?>">
                                                <?php echo Email::getCustomerEmailTypes()[$cust_email['email_type']]; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($cust_email['description'] ?: 'N/A'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($cust_email['created_at'])); ?></td>
                                        <?php if ($can_manage): ?>
                                        <td class="actions">
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Remove this email?')">
                                                <input type="hidden" name="email_id" value="<?php echo $cust_email['id']; ?>">
                                                <button type="submit" name="remove_email" class="btn btn-small btn-warning">
                                                    Remove
                                                </button>
                                            </form>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="customers_with_emails.php" class="btn btn-secondary">Back to Customers</a>
                </div>
            </div>
            
            <?php elseif ($can_manage && !isset($_GET['manage_emails'])): ?>
            <div class="form-section">
                <h3>Add New Customer</h3>
                <form method="POST" class="customer-form">
                    <h4>Company Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_name">Customer Name:</label>
                            <input type="text" id="customer_name" name="customer_name" required 
                                   placeholder="Company name">
                        </div>
                    </div>
                    
                    <h4>Address Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="address_line1">Address Line 1:</label>
                            <input type="text" id="address_line1" name="address_line1" 
                                   placeholder="Street address">
                        </div>
                        <div class="form-group">
                            <label for="address_line2">Address Line 2:</label>
                            <input type="text" id="address_line2" name="address_line2" 
                                   placeholder="Suite, unit, etc. (optional)">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City:</label>
                            <input type="text" id="city" name="city" placeholder="City">
                        </div>
                        <div class="form-group">
                            <label for="state">State:</label>
                            <input type="text" id="state" name="state" placeholder="State/Province">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code">ZIP Code:</label>
                            <input type="text" id="zip_code" name="zip_code" 
                                   placeholder="ZIP/Postal code">
                        </div>
                        <div class="form-group">
                            <label for="country">Country:</label>
                            <input type="text" id="country" name="country" value="USA"
                                   placeholder="Country">
                        </div>
                    </div>
                    
                    <h4>Business Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_terms">Payment Terms:</label>
                            <input type="text" id="payment_terms" name="payment_terms" 
                                   placeholder="e.g., Net 30, COD">
                        </div>
                        <div class="form-group">
                            <label for="credit_limit">Credit Limit ($):</label>
                            <input type="number" id="credit_limit" name="credit_limit" step="0.01" min="0" 
                                   value="0.00" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="Additional notes about this customer"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_customer" class="btn btn-primary">Add Customer</button>
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
                                <th>Primary Contact</th>
                                <th>Location</th>
                                <th>Products</th>
                                <th>Emails</th>
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
                                        <td class="email-count">
                                            <span class="badge"><?php echo $customer['email_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $customer['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <?php if ($can_manage): ?>
                                        <td class="actions">
                                            <a href="customers_enhanced.php?edit=<?php echo $customer['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                            <a href="customers_enhanced.php?view_contacts=<?php echo $customer['id']; ?>" class="btn btn-small btn-info">Contacts</a>
                                            <a href="customers_with_emails.php?manage_emails=<?php echo $customer['id']; ?>" class="btn btn-small btn-success">Emails</a>
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
            .email-type-badge {
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 0.8em;
                font-weight: bold;
                text-transform: uppercase;
            }
            .type-department { background-color: #e3f2fd; color: #1565c0; }
            .type-contact { background-color: #f5f5f5; color: #424242; }
            
            .email-count .badge {
                background-color: #4caf50;
                color: white;
                padding: 2px 6px;
                border-radius: 50%;
                font-size: 0.8em;
                font-weight: bold;
            }
            
            .email-field-group {
                margin-bottom: 15px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background-color: #f9f9f9;
            }
            
            .email-field-group .form-row {
                display: flex;
                gap: 15px;
                align-items: end;
            }
            
            .email-field-group .form-group {
                flex: 1;
            }
            
            .email-actions {
                flex: 0 0 auto;
            }
            
            #add-email-field {
                margin-bottom: 10px;
            }
            </style>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                let emailIndex = 1;
                const container = document.getElementById('email-fields-container');
                const addButton = document.getElementById('add-email-field');
                
                if (addButton) {
                    addButton.addEventListener('click', function() {
                        const newEmailField = createEmailField(emailIndex);
                        container.appendChild(newEmailField);
                        updateRemoveButtons();
                        emailIndex++;
                    });
                }
                
                function createEmailField(index) {
                    const div = document.createElement('div');
                    div.className = 'email-field-group';
                    div.setAttribute('data-index', index);
                    
                    div.innerHTML = `
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email_${index}">Email Address:</label>
                                <input type="email" id="email_${index}" name="emails[${index}][email]" 
                                       placeholder="contact@company.com">
                            </div>
                            <div class="form-group">
                                <label for="email_type_${index}">Email Type:</label>
                                <select id="email_type_${index}" name="emails[${index}][type]">
                                    <option value="contact">Main Contact</option>
                                    <option value="department">Department Group</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="description_${index}">Description:</label>
                                <input type="text" id="description_${index}" name="emails[${index}][description]" 
                                       placeholder="e.g., Returns team">
                            </div>
                            <div class="form-group email-actions">
                                <button type="button" class="btn btn-small btn-danger remove-email">Remove</button>
                            </div>
                        </div>
                    `;
                    
                    // Add remove functionality
                    const removeButton = div.querySelector('.remove-email');
                    removeButton.addEventListener('click', function() {
                        div.remove();
                        updateRemoveButtons();
                    });
                    
                    return div;
                }
                
                function updateRemoveButtons() {
                    const emailFields = container.querySelectorAll('.email-field-group');
                    emailFields.forEach((field, index) => {
                        const removeButton = field.querySelector('.remove-email');
                        if (emailFields.length > 1) {
                            removeButton.style.display = 'inline-block';
                        } else {
                            removeButton.style.display = 'none';
                        }
                    });
                }
                
                // Initialize remove button visibility
                updateRemoveButtons();
                
                // Add remove functionality to existing remove buttons
                container.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-email')) {
                        e.target.closest('.email-field-group').remove();
                        updateRemoveButtons();
                    }
                });
            });
            </script>

<?php
// Include footer component
include '../src/includes/footer.php';
?>