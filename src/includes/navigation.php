<?php
/**
 * Navigation Component
 * Dynamic navigation with active page detection and role-based permissions
 */

// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Navigation structure
$nav_items = [
    'dashboard' => [
        'label' => 'Dashboard',
        'url' => 'index.php',
        'standalone' => true
    ],
    'materials_inventory' => [
        'label' => 'Materials & Inventory',
        'items' => [
            'materials' => ['label' => 'Materials Master', 'url' => 'materials.php'],
            'inventory' => ['label' => 'Inventory Management', 'url' => 'inventory.php'],
            'recipes' => ['label' => 'Recipes', 'url' => 'recipes.php']
        ]
    ],
    'production' => [
        'label' => 'Production',
        'items' => [
            'products' => ['label' => 'Products', 'url' => 'products.php'],
            'jobs' => ['label' => 'Production Jobs', 'url' => 'jobs.php'],
            'traceability' => ['label' => 'Traceability', 'url' => 'traceability.php']
        ]
    ],
    'business_partners' => [
        'label' => 'Business Partners',
        'items' => [
            'contact_management' => ['label' => 'Contact Management', 'url' => 'contact_management.php'],
            'customers' => ['label' => 'Customers (Legacy)', 'url' => 'customers.php'],
            'customers_enhanced' => ['label' => 'Enhanced Customers', 'url' => 'customers_enhanced.php'],
            'suppliers' => ['label' => 'Suppliers (Legacy)', 'url' => 'suppliers.php']
        ]
    ]
];

// Add System menu for authorized users
if (isset($auth) && $auth->hasRole(['admin', 'supervisor'])) {
    $system_items = [];
    
    if ($auth->hasRole(['admin', 'supervisor'])) {
        $system_items['reports'] = ['label' => 'Reports', 'url' => 'reports.php'];
    }
    
    if ($auth->hasRole(['admin'])) {
        $system_items['admin'] = ['label' => 'Administration', 'url' => 'admin.php'];
    }
    
    if (!empty($system_items)) {
        $nav_items['system'] = [
            'label' => 'System',
            'items' => $system_items
        ];
    }
}

/**
 * Check if a page is active in a dropdown menu
 */
function isActiveInDropdown($items, $current_page) {
    foreach ($items as $item) {
        if (basename($item['url']) === $current_page) {
            return true;
        }
    }
    return false;
}

/**
 * Get active class for navigation item
 */
function getActiveClass($url, $current_page) {
    return (basename($url) === $current_page) ? 'active' : '';
}
?>

<nav>
    <ul>
        <?php foreach ($nav_items as $key => $nav_item): ?>
            <?php if (isset($nav_item['standalone']) && $nav_item['standalone']): ?>
                <!-- Standalone navigation item -->
                <li>
                    <a href="<?php echo $nav_item['url']; ?>" class="<?php echo getActiveClass($nav_item['url'], $current_page); ?>">
                        <?php echo htmlspecialchars($nav_item['label']); ?>
                    </a>
                </li>
            <?php else: ?>
                <!-- Dropdown navigation item -->
                <li class="nav-dropdown">
                    <a href="#"><?php echo htmlspecialchars($nav_item['label']); ?></a>
                    <ul class="dropdown-menu">
                        <?php foreach ($nav_item['items'] as $item_key => $item): ?>
                            <li>
                                <a href="<?php echo $item['url']; ?>" class="<?php echo getActiveClass($item['url'], $current_page); ?>">
                                    <?php echo htmlspecialchars($item['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>