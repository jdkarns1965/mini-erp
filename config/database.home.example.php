<?php
/**
 * Home Development Database Configuration Example
 * Copy this to database.php and customize for your home environment
 */

class Database {
    // Home development database settings
    private $host = 'localhost';
    private $dbname = 'mini_erp_dev';        // Different name from production
    private $username = 'your_dev_user';     // Your home MySQL user
    private $password = 'your_dev_password'; // Your home MySQL password
    private $charset = 'utf8mb4';
    
    private $pdo = null;
    private $error = '';
    
    public function connect() {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            try {
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                $this->error = $e->getMessage();
                throw new Exception("Database connection failed: " . $this->error);
            }
        }
        
        return $this->pdo;
    }
    
    public function getError() {
        return $this->error;
    }
}

/*
HOME SETUP INSTRUCTIONS:

1. Copy this file to database.php:
   cp database.home.example.php database.php

2. Create your development database:
   mysql -u root -p
   CREATE DATABASE mini_erp_dev;
   CREATE USER 'your_dev_user'@'localhost' IDENTIFIED BY 'your_dev_password';
   GRANT ALL PRIVILEGES ON mini_erp_dev.* TO 'your_dev_user'@'localhost';
   FLUSH PRIVILEGES;

3. Update the settings above with your actual:
   - Database name
   - Username  
   - Password
   
4. Test connection:
   cd ../database
   php setup_home_dev.php
   
5. Import database:
   php sync_to_home.php
*/
?>