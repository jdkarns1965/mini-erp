<?php
/**
 * Application Configuration
 */

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'mini_erp');
define('DB_USER', $_ENV['DB_USER'] ?? 'mini_erp_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Application Configuration
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? true);
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/mini-erp');

// Security Configuration
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 3600);
define('CSRF_TOKEN_LIFETIME', $_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600);

// Directory paths
define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}