<?php
/**
 * Logout Page for Manufacturing ERP
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/classes/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Perform logout
$result = $auth->logout();

// Redirect to login page with success message
header('Location: login.php?message=logged_out');
exit;