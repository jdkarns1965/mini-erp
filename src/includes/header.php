<?php
/**
 * Header Component
 * Complete header with branding, user info, and navigation
 * Requires: $current_user and $auth to be available
 * Optional: $page_title for page-specific title
 */

// Set default page title if not provided
if (!isset($page_title)) {
    $page_title = 'Mini ERP';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Mini ERP</title>
    <link href="css/style.css" rel="stylesheet">
    <script src="assets/js/navigation.js"></script>
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
            
            <?php include '../src/includes/navigation.php'; ?>
        </header>
        
        <main>