<?php
/**
 * Modern Contact Management System
 * Unified interface for customers, suppliers, and contacts
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

$page_title = 'Contact Management';

// Include header component
include '../src/includes/header.php';
?>

<div id="contact-management-app" class="contact-management-container">
    
    <!-- Header Section -->
    <div class="cm-header">
        <div class="cm-header-content">
            <h1><?php echo $page_title; ?></h1>
            <p class="cm-subtitle">Manage customers, suppliers, and contacts in one unified interface</p>
        </div>
        
        <div class="cm-header-actions">
            <?php if ($can_manage): ?>
            <button class="btn btn-primary" onclick="openNewBusinessModal()">
                <i class="icon-plus"></i> New Business
            </button>
            <button class="btn btn-secondary" onclick="openBulkImport()">
                <i class="icon-upload"></i> Import
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="cm-search-bar">
        <div class="search-container">
            <div class="search-input-group">
                <i class="icon-search"></i>
                <input 
                    type="text" 
                    id="businessSearch" 
                    class="search-input" 
                    placeholder="Search businesses, contacts, or start typing to create new..."
                    autocomplete="off"
                >
                <button class="search-clear" onclick="clearSearch()" style="display: none;">
                    <i class="icon-x"></i>
                </button>
            </div>
        </div>
        
        <div class="filter-container">
            <select id="businessTypeFilter" class="filter-select" onchange="filterBusinesses()">
                <option value="">All Business Types</option>
                <option value="customer">Customers Only</option>
                <option value="supplier">Suppliers Only</option>
                <option value="both">Customer & Supplier</option>
            </select>
            
            <select id="statusFilter" class="filter-select" onchange="filterBusinesses()">
                <option value="">All Statuses</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
            
            <button class="filter-reset" onclick="resetFilters()">
                <i class="icon-refresh"></i> Reset
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="cm-stats">
        <div class="stat-card clickable" data-filter="all" onclick="contactManager.filterByStats('all')" title="Click to show all businesses">
            <div class="stat-number" id="totalBusinesses">0</div>
            <div class="stat-label">Total Businesses</div>
        </div>
        <div class="stat-card clickable" data-filter="customer" onclick="contactManager.filterByStats('customer')" title="Click to show customers only">
            <div class="stat-number" id="totalCustomers">0</div>
            <div class="stat-label">Customers</div>
        </div>
        <div class="stat-card clickable" data-filter="supplier" onclick="contactManager.filterByStats('supplier')" title="Click to show suppliers only">
            <div class="stat-number" id="totalSuppliers">0</div>
            <div class="stat-label">Suppliers</div>
        </div>
        <div class="stat-card" title="Total contacts across all businesses">
            <div class="stat-number" id="totalContacts">0</div>
            <div class="stat-label">Contacts</div>
        </div>
    </div>

    <!-- Search Results / Business List -->
    <div class="cm-results" id="businessResults">
        <div class="results-header">
            <span class="results-count">Loading businesses...</span>
            <div class="view-controls">
                <button class="view-btn active" data-view="cards" onclick="switchView('cards')">
                    <i class="icon-grid"></i> Cards
                </button>
                <button class="view-btn" data-view="list" onclick="switchView('list')">
                    <i class="icon-list"></i> List
                </button>
                <button class="view-btn" data-view="table" onclick="switchView('table')">
                    <i class="icon-table"></i> Table
                </button>
            </div>
        </div>
        
        <!-- Card View (Default) -->
        <div id="cardView" class="business-cards-container">
            <!-- Dynamic content populated by JavaScript -->
        </div>
        
        <!-- List View -->
        <div id="listView" class="business-list-container" style="display: none;">
            <!-- Dynamic content populated by JavaScript -->
        </div>
        
        <!-- Table View -->
        <div id="tableView" class="business-table-container" style="display: none;">
            <!-- Dynamic content populated by JavaScript -->
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner" style="display: none;">
        <div class="spinner"></div>
        <p>Loading...</p>
    </div>

</div>

<!-- New Business Modal -->
<div id="newBusinessModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>Create New Business</h3>
            <button class="modal-close" onclick="closeNewBusinessModal()">
                <i class="icon-x"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="business-type-selection">
                <label class="business-type-label">Business Type:</label>
                <div class="business-type-options">
                    <label class="type-option">
                        <input type="radio" name="business_type" value="customer" checked>
                        <span class="type-card">
                            <i class="icon-user"></i>
                            <strong>Customer</strong>
                            <small>Buys products/services from us</small>
                        </span>
                    </label>
                    <label class="type-option">
                        <input type="radio" name="business_type" value="supplier">
                        <span class="type-card">
                            <i class="icon-truck"></i>
                            <strong>Supplier</strong>
                            <small>Provides materials/services to us</small>
                        </span>
                    </label>
                    <label class="type-option">
                        <input type="radio" name="business_type" value="both">
                        <span class="type-card">
                            <i class="icon-users"></i>
                            <strong>Both</strong>
                            <small>Customer and supplier</small>
                        </span>
                    </label>
                </div>
            </div>
            
            <form id="newBusinessForm" class="new-business-form">
                <!-- Form content will be loaded dynamically based on type selection -->
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeNewBusinessModal()">Cancel</button>
            <button type="submit" form="newBusinessForm" class="btn btn-primary">
                <i class="icon-save"></i> Create Business
            </button>
        </div>
    </div>
</div>

<!-- Contact Detail Modal -->
<div id="contactDetailModal" class="modal" style="display: none;">
    <div class="modal-content modal-xlarge">
        <!-- Content populated dynamically -->
    </div>
</div>

<!-- Modern CSS Styles -->
<link rel="stylesheet" href="assets/css/icons.css">
<link rel="stylesheet" href="assets/css/contact-management.css">

<!-- JavaScript for functionality -->
<script src="assets/js/contact-management.js"></script>

<?php
// Include footer component  
include '../src/includes/footer.php';
?>