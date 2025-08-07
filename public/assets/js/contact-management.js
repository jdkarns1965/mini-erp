/**
 * Modern Contact Management JavaScript
 * Handles all interactive functionality for the unified contact interface
 */

class ContactManagement {
    constructor() {
        this.businesses = [];
        this.filteredBusinesses = [];
        this.currentView = 'cards';
        this.searchTimeout = null;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadBusinesses();
        this.loadStats();
    }
    
    setupEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('businessSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
            searchInput.addEventListener('keydown', (e) => this.handleSearchKeydown(e));
        }
        
        // Filter functionality
        document.getElementById('businessTypeFilter')?.addEventListener('change', () => this.filterBusinesses());
        document.getElementById('statusFilter')?.addEventListener('change', () => this.filterBusinesses());
        
        // Business type selection in modal
        document.querySelectorAll('input[name="business_type"]').forEach(radio => {
            radio.addEventListener('change', () => this.loadBusinessForm(radio.value));
        });
        
        // Form submission
        document.getElementById('newBusinessForm')?.addEventListener('submit', (e) => this.handleNewBusinessSubmit(e));
        
        // Modal close on background click
        document.getElementById('newBusinessModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'newBusinessModal') {
                this.closeNewBusinessModal();
            }
        });
    }
    
    handleSearch(query) {
        clearTimeout(this.searchTimeout);
        
        const clearBtn = document.querySelector('.search-clear');
        if (query.length > 0) {
            clearBtn.style.display = 'block';
        } else {
            clearBtn.style.display = 'none';
        }
        
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, 300);
    }
    
    handleSearchKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = e.target.value.trim();
            
            if (query && this.filteredBusinesses.length === 0) {
                // No results found, suggest creating new business
                this.showCreateNewSuggestion(query);
            } else if (this.filteredBusinesses.length === 1) {
                // Only one result, open it
                this.openBusinessDetail(this.filteredBusinesses[0]);
            }
        }
    }
    
    performSearch(query) {
        if (!query.trim()) {
            this.filteredBusinesses = [...this.businesses];
        } else {
            const searchTerm = query.toLowerCase();
            this.filteredBusinesses = this.businesses.filter(business => {
                return business.name.toLowerCase().includes(searchTerm) ||
                       business.code.toLowerCase().includes(searchTerm) ||
                       (business.primary_contact && business.primary_contact.toLowerCase().includes(searchTerm)) ||
                       (business.email && business.email.toLowerCase().includes(searchTerm)) ||
                       (business.phone && business.phone.includes(searchTerm));
            });
        }
        
        this.updateResultsDisplay();
    }
    
    filterBusinesses() {
        const typeFilter = document.getElementById('businessTypeFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        
        let filtered = [...this.businesses];
        
        if (typeFilter) {
            filtered = filtered.filter(business => {
                if (typeFilter === 'both') {
                    return business.is_customer && business.is_supplier;
                } else {
                    return business[`is_${typeFilter}`];
                }
            });
        }
        
        if (statusFilter) {
            const isActive = statusFilter === 'active';
            filtered = filtered.filter(business => business.is_active === isActive);
        }
        
        this.filteredBusinesses = filtered;
        this.updateResultsDisplay();
    }
    
    switchView(view) {
        this.currentView = view;
        
        // Update button states
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`).classList.add('active');
        
        // Show/hide view containers
        document.getElementById('cardView').style.display = view === 'cards' ? 'grid' : 'none';
        document.getElementById('listView').style.display = view === 'list' ? 'block' : 'none';
        document.getElementById('tableView').style.display = view === 'table' ? 'block' : 'none';
        
        this.updateResultsDisplay();
    }
    
    updateResultsDisplay() {
        const count = this.filteredBusinesses.length;
        const resultText = count === 1 ? '1 business' : `${count} businesses`;
        document.querySelector('.results-count').textContent = resultText;
        
        if (this.currentView === 'cards') {
            this.renderCardView();
        } else if (this.currentView === 'list') {
            this.renderListView();
        } else if (this.currentView === 'table') {
            this.renderTableView();
        }
    }
    
    renderCardView() {
        const container = document.getElementById('cardView');
        
        if (this.filteredBusinesses.length === 0) {
            container.innerHTML = this.getEmptyState();
            return;
        }
        
        container.innerHTML = this.filteredBusinesses.map(business => `
            <div class="business-card" onclick="contactManager.openBusinessDetail('${business.id}')">
                <div class="business-card-header">
                    <div class="business-info">
                        <h3>${this.escapeHtml(business.name)}</h3>
                        <div class="business-code">${business.code}</div>
                    </div>
                    <div class="business-type">
                        ${business.is_customer ? '<span class="type-badge customer">Customer</span>' : ''}
                        ${business.is_supplier ? '<span class="type-badge supplier">Supplier</span>' : ''}
                    </div>
                </div>
                
                <div class="business-details">
                    ${business.location ? `
                        <div class="detail-row">
                            <i class="icon-map-pin"></i>
                            <span>${this.escapeHtml(business.location)}</span>
                        </div>
                    ` : ''}
                    
                    ${business.phone ? `
                        <div class="detail-row">
                            <i class="icon-phone"></i>
                            <span>${this.escapeHtml(business.phone)}</span>
                        </div>
                    ` : ''}
                    
                    ${business.email ? `
                        <div class="detail-row">
                            <i class="icon-mail"></i>
                            <span>${this.escapeHtml(business.email)}</span>
                        </div>
                    ` : ''}
                </div>
                
                <div class="business-contacts">
                    <div class="contact-count">${business.contact_count} contacts • ${business.email_count} emails</div>
                    ${business.primary_contact ? `
                        <div class="primary-contact">
                            <div class="contact-name">${this.escapeHtml(business.primary_contact)}</div>
                            ${business.primary_contact_title ? `<div class="contact-title">${this.escapeHtml(business.primary_contact_title)}</div>` : ''}
                        </div>
                    ` : '<div class="primary-contact">No primary contact</div>'}
                </div>
            </div>
        `).join('');
    }
    
    renderListView() {
        const container = document.getElementById('listView');
        
        if (this.filteredBusinesses.length === 0) {
            container.innerHTML = this.getEmptyState();
            return;
        }
        
        container.innerHTML = this.filteredBusinesses.map(business => `
            <div class="business-list-item" onclick="contactManager.openBusinessDetail('${business.id}')">
                <div class="business-avatar">
                    ${business.name.charAt(0).toUpperCase()}
                </div>
                <div class="business-list-info">
                    <div class="business-list-name">${this.escapeHtml(business.name)}</div>
                    <div class="business-list-meta">
                        <span>${business.code}</span>
                        ${business.location ? `<span>${this.escapeHtml(business.location)}</span>` : ''}
                        <span>${business.contact_count} contacts</span>
                    </div>
                </div>
                <div class="business-list-actions">
                    ${business.is_customer ? '<span class="type-badge customer">Customer</span>' : ''}
                    ${business.is_supplier ? '<span class="type-badge supplier">Supplier</span>' : ''}
                </div>
            </div>
        `).join('');
    }
    
    renderTableView() {
        const container = document.getElementById('tableView');
        
        if (this.filteredBusinesses.length === 0) {
            container.innerHTML = this.getEmptyState();
            return;
        }
        
        container.innerHTML = `
            <table class="business-table">
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Primary Contact</th>
                        <th>Contacts</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${this.filteredBusinesses.map(business => `
                        <tr onclick="contactManager.openBusinessDetail('${business.id}')">
                            <td>
                                <div class="business-table-name">
                                    <strong>${this.escapeHtml(business.name)}</strong>
                                    <div class="business-table-code">${business.code}</div>
                                </div>
                            </td>
                            <td>
                                ${business.is_customer ? '<span class="type-badge customer">Customer</span>' : ''}
                                ${business.is_supplier ? '<span class="type-badge supplier">Supplier</span>' : ''}
                            </td>
                            <td>${business.location || 'N/A'}</td>
                            <td>${business.primary_contact || 'None'}</td>
                            <td>${business.contact_count}</td>
                            <td>
                                <span class="status-badge status-${business.is_active ? 'active' : 'inactive'}">
                                    ${business.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-small btn-secondary" onclick="event.stopPropagation(); contactManager.editBusiness('${business.id}')">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }
    
    getEmptyState() {
        const searchQuery = document.getElementById('businessSearch').value;
        
        if (searchQuery.trim()) {
            return `
                <div class="empty-state">
                    <div class="empty-state-icon">🔍</div>
                    <h3>No businesses found for "${this.escapeHtml(searchQuery)}"</h3>
                    <p>Would you like to create a new business with this name?</p>
                    <button class="btn btn-primary" onclick="contactManager.createNewWithName('${this.escapeHtml(searchQuery)}')">
                        <i class="icon-plus"></i> Create "${this.escapeHtml(searchQuery)}"
                    </button>
                </div>
            `;
        } else {
            return `
                <div class="empty-state">
                    <div class="empty-state-icon">🏢</div>
                    <h3>No businesses found</h3>
                    <p>Start by creating your first business or contact.</p>
                    <button class="btn btn-primary" onclick="contactManager.openNewBusinessModal()">
                        <i class="icon-plus"></i> Create New Business
                    </button>
                </div>
            `;
        }
    }
    
    async loadBusinesses() {
        try {
            this.showLoading(true);
            console.log('Loading businesses from API...');
            
            const response = await fetch('api/businesses-simple.php');
            console.log('API Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('API Response data:', data);
            
            if (data.success) {
                this.businesses = data.businesses;
                this.filteredBusinesses = [...this.businesses];
                console.log('Loaded businesses:', this.businesses.length);
                this.updateResultsDisplay();
            } else {
                throw new Error(data.message || 'Failed to load businesses');
            }
        } catch (error) {
            console.error('Error loading businesses:', error);
            this.showError('Failed to load businesses: ' + error.message);
            
            // Show empty state with error
            document.getElementById('cardView').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Failed to Load Businesses</h3>
                    <p>Error: ${error.message}</p>
                    <button class="btn btn-primary" onclick="contactManager.loadBusinesses()">
                        <i class="icon-refresh"></i> Retry
                    </button>
                </div>
            `;
        } finally {
            this.showLoading(false);
        }
    }
    
    async loadStats() {
        try {
            console.log('Loading stats from API...');
            const response = await fetch('api/business-stats.php');
            console.log('Stats API Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Stats API Response data:', data);
            
            if (data.success) {
                document.getElementById('totalBusinesses').textContent = data.stats.total_businesses || '0';
                document.getElementById('totalCustomers').textContent = data.stats.total_customers || '0';
                document.getElementById('totalSuppliers').textContent = data.stats.total_suppliers || '0';
                document.getElementById('totalContacts').textContent = data.stats.total_contacts || '0';
                
                console.log('Stats updated successfully');
            } else {
                throw new Error(data.message || 'Failed to load stats');
            }
        } catch (error) {
            console.error('Error loading stats:', error);
            // Show error in stats
            document.getElementById('totalBusinesses').textContent = '?';
            document.getElementById('totalCustomers').textContent = '?';
            document.getElementById('totalSuppliers').textContent = '?';
            document.getElementById('totalContacts').textContent = '?';
        }
    }
    
    openNewBusinessModal() {
        document.getElementById('newBusinessModal').style.display = 'flex';
        this.loadBusinessForm('customer'); // Default to customer
    }
    
    closeNewBusinessModal() {
        document.getElementById('newBusinessModal').style.display = 'none';
        document.getElementById('newBusinessForm').reset();
    }
    
    createNewWithName(name) {
        this.openNewBusinessModal();
        // Pre-fill the business name after the form loads
        setTimeout(() => {
            const nameInput = document.querySelector('#newBusinessForm input[name="business_name"]');
            if (nameInput) {
                nameInput.value = name;
                nameInput.focus();
            }
        }, 100);
    }
    
    loadBusinessForm(type) {
        const form = document.getElementById('newBusinessForm');
        
        const formHTML = `
            <div class="form-section">
                <h4>Business Information</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="business_name">Business Name *</label>
                        <input type="text" id="business_name" name="business_name" required 
                               placeholder="Enter business name">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4>Primary Contact</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="contact_first_name">First Name</label>
                        <input type="text" id="contact_first_name" name="contact_first_name" 
                               placeholder="Contact's first name">
                    </div>
                    <div class="form-group">
                        <label for="contact_last_name">Last Name</label>
                        <input type="text" id="contact_last_name" name="contact_last_name" 
                               placeholder="Contact's last name">
                    </div>
                    <div class="form-group">
                        <label for="contact_title">Job Title</label>
                        <input type="text" id="contact_title" name="contact_title" 
                               placeholder="e.g., Manager, Director">
                    </div>
                    <div class="form-group">
                        <label for="contact_department">Department</label>
                        <input type="text" id="contact_department" name="contact_department" 
                               placeholder="e.g., Purchasing, Sales">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4>Contact Details</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="primary_email">Primary Email</label>
                        <input type="email" id="primary_email" name="primary_email" 
                               placeholder="contact@business.com">
                    </div>
                    <div class="form-group">
                        <label for="email_type">Email Type</label>
                        <select id="email_type" name="email_type">
                            <option value="contact">Main Contact</option>
                            <option value="department">Department</option>
                            <option value="purchasing">Purchasing</option>
                            <option value="sales">Sales</option>
                            <option value="support">Support</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="primary_phone">Primary Phone</label>
                        <input type="tel" id="primary_phone" name="primary_phone" 
                               placeholder="(555) 123-4567">
                    </div>
                    <div class="form-group">
                        <label for="phone_ext">Extension</label>
                        <input type="text" id="phone_ext" name="phone_ext" 
                               placeholder="ext. 1234">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label for="additional_emails">Additional Emails (one per line)</label>
                    <textarea id="additional_emails" name="additional_emails" rows="3"
                              placeholder="department@business.com&#10;support@business.com&#10;sales@business.com"></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h4>Business Address</h4>
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="address_line1">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" 
                               placeholder="Street address">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="address_line2">Address Line 2</label>
                        <input type="text" id="address_line2" name="address_line2" 
                               placeholder="Suite, unit, etc. (optional)">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" placeholder="City">
                    </div>
                    <div class="form-group">
                        <label for="state">State/Province</label>
                        <input type="text" id="state" name="state" placeholder="State">
                    </div>
                    <div class="form-group">
                        <label for="zip_code">ZIP/Postal Code</label>
                        <input type="text" id="zip_code" name="zip_code" placeholder="12345">
                    </div>
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" value="USA" placeholder="Country">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4>Business Terms</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="payment_terms">Payment Terms</label>
                        <select id="payment_terms" name="payment_terms">
                            <option value="">Select payment terms</option>
                            <option value="Net 30">Net 30</option>
                            <option value="Net 60">Net 60</option>
                            <option value="COD">Cash on Delivery</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Wire Transfer">Wire Transfer</option>
                        </select>
                    </div>
                    ${type === 'customer' ? `
                        <div class="form-group">
                            <label for="credit_limit">Credit Limit</label>
                            <input type="number" id="credit_limit" name="credit_limit" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                    ` : ''}
                    ${type === 'supplier' ? `
                        <div class="form-group">
                            <label for="lead_time_days">Lead Time (Days)</label>
                            <input type="number" id="lead_time_days" name="lead_time_days" 
                                   min="0" placeholder="0">
                        </div>
                    ` : ''}
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="Additional notes about this business..."></textarea>
                </div>
            </div>
            
            <input type="hidden" name="business_type" value="${type}">
        `;
        
        form.innerHTML = formHTML;
    }
    
    async handleNewBusinessSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        // Validate required fields
        if (!formData.get('business_name')?.trim()) {
            this.showNotification('Business name is required', 'error');
            return;
        }
        
        try {
            this.showLoading(true);
            console.log('Creating business...');
            
            const response = await fetch('api/create-business.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('Create business response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Create business response data:', data);
            
            if (data.success) {
                this.closeNewBusinessModal();
                this.showNotification('Business created successfully!', 'success');
                
                // Refresh data
                await this.loadBusinesses();
                await this.loadStats();
                
            } else {
                throw new Error(data.message || 'Failed to create business');
            }
        } catch (error) {
            console.error('Error creating business:', error);
            this.showNotification('Failed to create business: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async openBusinessDetail(businessId) {
        try {
            this.showLoading(true);
            
            const response = await fetch(`api/business-detail.php?id=${businessId}`);
            const data = await response.json();
            
            if (data.success) {
                this.showBusinessDetailModal(data.business);
            } else {
                throw new Error(data.message || 'Failed to load business details');
            }
        } catch (error) {
            console.error('Error loading business details:', error);
            this.showError('Failed to load business details.');
        } finally {
            this.showLoading(false);
        }
    }
    
    showBusinessDetailModal(business) {
        const modal = document.getElementById('contactDetailModal');
        
        const modalHTML = this.generateBusinessDetailHTML(business);
        modal.querySelector('.modal-content').innerHTML = modalHTML;
        modal.style.display = 'flex';
    }
    
    generateBusinessDetailHTML(business) {
        return `
            <div class="modal-header business-detail-header">
                <div class="business-header-info">
                    <div class="business-avatar-large">
                        ${business.name.charAt(0).toUpperCase()}
                    </div>
                    <div class="business-header-text">
                        <h2>${this.escapeHtml(business.name)}</h2>
                        <div class="business-meta">
                            <span class="business-code">${business.code}</span>
                            ${business.is_customer ? '<span class="type-badge customer">Customer</span>' : ''}
                            ${business.is_supplier ? '<span class="type-badge supplier">Supplier</span>' : ''}
                            <span class="status-badge status-${business.is_active ? 'active' : 'inactive'}">
                                ${business.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                </div>
                <button class="modal-close" onclick="document.getElementById('contactDetailModal').style.display = 'none'">
                    <i class="icon-x"></i>
                </button>
            </div>
            
            <div class="modal-body business-detail-body">
                <div class="business-detail-content">
                    
                    <!-- Primary Contact Section -->
                    <div class="detail-section">
                        <h3><i class="icon-user"></i> Primary Contact</h3>
                        <div class="contact-card primary-contact-card">
                            ${business.contact_person ? `
                                <div class="contact-info">
                                    <div class="contact-name">${this.escapeHtml(business.contact_person)}</div>
                                    ${business.contact_title ? `<div class="contact-title">${this.escapeHtml(business.contact_title)}</div>` : ''}
                                </div>
                                <div class="contact-methods">
                                    ${business.email ? `
                                        <div class="contact-method">
                                            <i class="icon-mail"></i>
                                            <a href="mailto:${business.email}">${this.escapeHtml(business.email)}</a>
                                        </div>
                                    ` : ''}
                                    ${business.phone ? `
                                        <div class="contact-method">
                                            <i class="icon-phone"></i>
                                            <a href="tel:${business.phone}">${this.escapeHtml(business.phone)}</a>
                                            ${business.phone_ext ? ` ext. ${this.escapeHtml(business.phone_ext)}` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            ` : '<div class="no-data">No primary contact information</div>'}
                            <div class="contact-actions">
                                <button class="btn btn-small btn-primary" onclick="contactManager.editPrimaryContact('${business.id}')">
                                    <i class="icon-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="detail-section">
                        <h3><i class="icon-map-pin"></i> Address</h3>
                        <div class="address-card">
                            ${business.full_address ? `
                                <div class="address-text">
                                    ${business.address_line1 ? `<div>${this.escapeHtml(business.address_line1)}</div>` : ''}
                                    ${business.address_line2 ? `<div>${this.escapeHtml(business.address_line2)}</div>` : ''}
                                    <div>
                                        ${[business.city, business.state, business.zip_code].filter(Boolean).map(item => this.escapeHtml(item)).join(', ')}
                                    </div>
                                    ${business.country && business.country !== 'USA' ? `<div>${this.escapeHtml(business.country)}</div>` : ''}
                                </div>
                                <div class="address-actions">
                                    <button class="btn btn-small btn-secondary" onclick="contactManager.openMapLocation('${business.id}')">
                                        <i class="icon-map-pin"></i> Map
                                    </button>
                                </div>
                            ` : '<div class="no-data">No address information</div>'}
                        </div>
                    </div>

                    <!-- Additional Contacts Section -->
                    <div class="detail-section">
                        <h3>
                            <i class="icon-users"></i> 
                            Additional Contacts 
                            <span class="count-badge">${business.contact_count}</span>
                        </h3>
                        <div class="contacts-list">
                            ${business.contacts && business.contacts.length > 0 ? 
                                business.contacts.map(contact => `
                                    <div class="contact-card">
                                        <div class="contact-info">
                                            <div class="contact-name">${this.escapeHtml(contact.full_name || contact.first_name + ' ' + contact.last_name)}</div>
                                            <div class="contact-role">${this.escapeHtml(contact.role)}</div>
                                            ${contact.job_title ? `<div class="contact-title">${this.escapeHtml(contact.job_title)}</div>` : ''}
                                        </div>
                                        <div class="contact-methods">
                                            ${contact.email ? `
                                                <div class="contact-method">
                                                    <i class="icon-mail"></i>
                                                    <a href="mailto:${contact.email}">${this.escapeHtml(contact.email)}</a>
                                                </div>
                                            ` : ''}
                                            ${contact.phone ? `
                                                <div class="contact-method">
                                                    <i class="icon-phone"></i>
                                                    <a href="tel:${contact.phone}">${this.escapeHtml(contact.phone)}</a>
                                                    ${contact.phone_ext ? ` ext. ${this.escapeHtml(contact.phone_ext)}` : ''}
                                                </div>
                                            ` : ''}
                                        </div>
                                        ${contact.is_primary ? '<div class="primary-indicator">Primary</div>' : ''}
                                    </div>
                                `).join('') : 
                                '<div class="no-data">No additional contacts</div>'
                            }
                            <button class="btn btn-secondary add-contact-btn" onclick="contactManager.addNewContact('${business.id}')">
                                <i class="icon-plus"></i> Add Contact
                            </button>
                        </div>
                    </div>

                    <!-- Emails Section -->
                    <div class="detail-section">
                        <h3>
                            <i class="icon-mail"></i> 
                            Email Addresses 
                            <span class="count-badge">${business.email_count}</span>
                        </h3>
                        <div class="emails-list">
                            ${business.emails && business.emails.length > 0 ? 
                                business.emails.map(email => `
                                    <div class="email-card">
                                        <div class="email-info">
                                            <div class="email-address">
                                                <a href="mailto:${email.email}">${this.escapeHtml(email.email)}</a>
                                            </div>
                                            <div class="email-meta">
                                                <span class="email-type-badge type-${email.email_type}">
                                                    ${Email.getCustomerEmailTypes()[email.email_type] || email.email_type}
                                                </span>
                                                ${email.description ? `<span class="email-description">${this.escapeHtml(email.description)}</span>` : ''}
                                            </div>
                                        </div>
                                        <div class="email-actions">
                                            <button class="btn btn-small btn-warning" onclick="contactManager.removeEmail('${email.id}')">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                `).join('') : 
                                '<div class="no-data">No additional email addresses</div>'
                            }
                            <button class="btn btn-secondary add-email-btn" onclick="contactManager.addNewEmail('${business.id}')">
                                <i class="icon-plus"></i> Add Email
                            </button>
                        </div>
                    </div>

                    <!-- Business Information Section -->
                    <div class="detail-section">
                        <h3><i class="icon-info"></i> Business Information</h3>
                        <div class="business-info-grid">
                            <div class="info-item">
                                <label>Payment Terms:</label>
                                <span>${business.payment_terms || 'Not specified'}</span>
                            </div>
                            ${business.is_customer ? `
                                <div class="info-item">
                                    <label>Credit Limit:</label>
                                    <span>$${parseFloat(business.credit_limit || 0).toLocaleString()}</span>
                                </div>
                                <div class="info-item">
                                    <label>Products:</label>
                                    <span>${business.product_count || 0}</span>
                                </div>
                            ` : ''}
                            ${business.is_supplier ? `
                                <div class="info-item">
                                    <label>Lead Time:</label>
                                    <span>${business.lead_time_days || 0} days</span>
                                </div>
                                <div class="info-item">
                                    <label>Materials:</label>
                                    <span>${business.material_count || 0}</span>
                                </div>
                            ` : ''}
                            <div class="info-item full-width">
                                <label>Notes:</label>
                                <div class="notes-text">${business.notes ? this.escapeHtml(business.notes) : 'No notes'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata Section -->
                    <div class="detail-section metadata-section">
                        <h3><i class="icon-info"></i> Record Information</h3>
                        <div class="metadata-info">
                            <div class="metadata-item">
                                <label>Created:</label>
                                <span>${new Date(business.created_at).toLocaleDateString()} by ${business.created_by_name || 'Unknown'}</span>
                            </div>
                            <div class="metadata-item">
                                <label>Last Updated:</label>
                                <span>${business.updated_at ? new Date(business.updated_at).toLocaleDateString() : 'Never'}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="modal-footer business-detail-footer">
                <div class="footer-actions-left">
                    <button class="btn btn-secondary" onclick="contactManager.printBusinessCard('${business.id}')">
                        <i class="icon-print"></i> Print
                    </button>
                    <button class="btn btn-secondary" onclick="contactManager.exportBusinessCard('${business.id}')">
                        <i class="icon-download"></i> Export
                    </button>
                </div>
                <div class="footer-actions-right">
                    <button class="btn btn-secondary" onclick="document.getElementById('contactDetailModal').style.display = 'none'">
                        Close
                    </button>
                    <button class="btn btn-primary" onclick="contactManager.editBusiness('${business.id}')">
                        <i class="icon-edit"></i> Edit Business
                    </button>
                </div>
            </div>
        `;
    }
    
    clearSearch() {
        document.getElementById('businessSearch').value = '';
        document.querySelector('.search-clear').style.display = 'none';
        this.filteredBusinesses = [...this.businesses];
        this.updateResultsDisplay();
    }
    
    resetFilters() {
        document.getElementById('businessTypeFilter').value = '';
        document.getElementById('statusFilter').value = '';
        this.filterBusinesses();
    }
    
    filterByStats(type) {
        // Clear search input
        document.getElementById('businessSearch').value = '';
        document.querySelector('.search-clear').style.display = 'none';
        
        // Set appropriate filters
        const typeFilter = document.getElementById('businessTypeFilter');
        const statusFilter = document.getElementById('statusFilter');
        
        if (type === 'all') {
            typeFilter.value = '';
            statusFilter.value = 'active'; // Show active businesses
        } else if (type === 'customer') {
            typeFilter.value = 'customer';
            statusFilter.value = 'active';
        } else if (type === 'supplier') {
            typeFilter.value = 'supplier';
            statusFilter.value = 'active';
        }
        
        // Apply filters
        this.filterBusinesses();
        
        // Visual feedback
        this.highlightStatCard(type);
        
        // Scroll to results
        document.getElementById('businessResults').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
    
    highlightStatCard(type) {
        // Remove previous highlights
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.remove('active-filter');
        });
        
        // Add highlight to clicked card
        const targetCard = document.querySelector(`[data-filter="${type}"]`);
        if (targetCard) {
            targetCard.classList.add('active-filter');
            
            // Remove highlight after animation
            setTimeout(() => {
                targetCard.classList.remove('active-filter');
            }, 2000);
        }
    }
    
    showLoading(show) {
        document.getElementById('loadingSpinner').style.display = show ? 'flex' : 'none';
    }
    
    showSuccess(message) {
        // Implement toast notification
        console.log('Success:', message);
    }
    
    showError(message) {
        // Implement toast notification
        console.error('Error:', message);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    // Business detail modal action handlers
    editBusiness(businessId) {
        console.log('Edit business:', businessId);
        // Will implement in next step
        this.showNotification('Edit business functionality coming soon', 'info');
    }
    
    editPrimaryContact(businessId) {
        console.log('Edit primary contact:', businessId);
        this.showNotification('Edit primary contact functionality coming soon', 'info');
    }
    
    addNewContact(businessId) {
        console.log('Add new contact:', businessId);
        this.showNotification('Add new contact functionality coming soon', 'info');
    }
    
    addNewEmail(businessId) {
        console.log('Add new email:', businessId);
        this.showNotification('Add new email functionality coming soon', 'info');
    }
    
    removeEmail(emailId) {
        console.log('Remove email:', emailId);
        this.showNotification('Remove email functionality coming soon', 'info');
    }
    
    openMapLocation(businessId) {
        console.log('Open map location:', businessId);
        this.showNotification('Map integration coming soon', 'info');
    }
    
    printBusinessCard(businessId) {
        console.log('Print business card:', businessId);
        window.print();
    }
    
    exportBusinessCard(businessId) {
        console.log('Export business card:', businessId);
        this.showNotification('Export functionality coming soon', 'info');
    }
    
    showNotification(message, type = 'info') {
        // Simple notification system
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
}

// Initialize the contact management system
let contactManager;
document.addEventListener('DOMContentLoaded', () => {
    contactManager = new ContactManagement();
});

// Global functions for HTML onclick handlers
function openNewBusinessModal() {
    contactManager.openNewBusinessModal();
}

function closeNewBusinessModal() {
    contactManager.closeNewBusinessModal();
}

function openBulkImport() {
    // Implement bulk import functionality
    console.log('Bulk import not implemented yet');
}

function clearSearch() {
    contactManager.clearSearch();
}

function filterBusinesses() {
    contactManager.filterBusinesses();
}

function resetFilters() {
    contactManager.resetFilters();
}

function switchView(view) {
    contactManager.switchView(view);
}