# Mini ERP Home Development Troubleshooting Guide

This guide helps you diagnose and fix common issues when setting up or using the Mini ERP system at home.

## 🚨 Emergency Tools

### Quick Diagnostic
```bash
cd /var/www/html/mini-erp/database
php doctor.php
```

### Auto-Fix Common Issues
```bash
php doctor.php --fix
```

### Nuclear Option (Last Resort)
```bash
php emergency_reset.php
```

## 🔍 Common Problems and Solutions

### 1. "Can't Connect to Database"

**Symptoms:**
- Login page shows "Database connection failed"
- Scripts fail with PDO connection errors
- `php doctor.php` shows database connection failed

**Diagnosis:**
```bash
php doctor.php  # Check database connection status
```

**Solutions:**

#### A. Wrong Database Configuration
```bash
# Check your database settings
cat config/database.php

# Common issues:
# - Wrong host (usually 'localhost' for home)
# - Wrong database name
# - Wrong username/password
# - Database doesn't exist
```

**Fix:**
1. Create database if missing:
```sql
mysql -u root -p
CREATE DATABASE mini_erp_dev;
CREATE USER 'dev_user'@'localhost' IDENTIFIED BY 'dev_password';
GRANT ALL PRIVILEGES ON mini_erp_dev.* TO 'dev_user'@'localhost';
FLUSH PRIVILEGES;
```

2. Update `config/database.php` with correct settings

#### B. MySQL Service Not Running
```bash
# Check if MySQL is running
sudo systemctl status mysql
# or
sudo service mysql status

# Start MySQL if stopped
sudo systemctl start mysql
```

### 2. "Database is Empty" or "Tables Missing"

**Symptoms:**
- Can connect to database but login fails
- "Table doesn't exist" errors
- Empty dashboard

**Diagnosis:**
```bash
php doctor.php  # Will show missing tables
```

**Solutions:**

#### A. Import Database Export
```bash
# Make sure you have the export file from work
ls -la complete_database_export.sql

# Import database
php sync_to_home.php
```

#### B. Missing Export File
1. **At work computer:**
```bash
php export_complete_schema.php
```

2. **Transfer file to home** (Git, USB, cloud storage)

3. **At home:**
```bash
php sync_to_home.php
```

### 3. "Migration Errors"

**Symptoms:**
- Migration tracker shows errors
- "Column already exists" or "Column not found"
- Database schema inconsistencies

**Diagnosis:**
```bash
php migration_tracker.php status
```

**Solutions:**

#### A. Reset Migration Status
```bash
# Clear migration history and restart
mysql -u your_user -p your_database
DELETE FROM migrations;

# Re-run migrations
php migration_tracker.php migrate
```

#### B. Fix Individual Migrations
```bash
# Run specific migration manually
php add_container_type.php
php add_phone_extension.php
```

### 4. "Permission Denied" Errors

**Symptoms:**
- Can't write to files
- "Permission denied" when running scripts
- File upload failures

**Solutions:**

#### A. Fix File Permissions
```bash
# Make sure web server can read/write
sudo chown -R www-data:www-data /var/www/html/mini-erp
chmod -R 755 /var/www/html/mini-erp

# Or if using your user account:
sudo chown -R $USER:$USER /var/www/html/mini-erp
```

#### B. Fix Database Directory
```bash
chmod 755 /var/www/html/mini-erp/database
chmod +x /var/www/html/mini-erp/database/*.php
```

### 5. "White Screen of Death" / Blank Pages

**Symptoms:**
- Pages load but show nothing
- No errors visible
- Browser shows blank white page

**Solutions:**

#### A. Enable Error Reporting
Edit `config/config.php`:
```php
define('APP_DEBUG', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### B. Check Apache/PHP Logs
```bash
# Check Apache error log
sudo tail -f /var/log/apache2/error.log

# Check PHP error log
sudo tail -f /var/log/php/error.log
```

#### C. Check File Includes
```bash
# Make sure include files exist
ls -la src/includes/
# Should show: header.php, navigation.php, footer.php
```

### 6. "Headers Already Sent" Errors

**Symptoms:**
- Error messages about headers already sent
- Session warnings
- Redirect failures

**Solutions:**

#### A. Check for Output Before Headers
- Remove any `echo`, `print`, or HTML before `<?php`
- Check for spaces/newlines before opening PHP tags
- Ensure no output in included files before session_start()

#### B. Fix Include Files
```bash
# Check first few characters of files
head -c 20 src/includes/header.php
# Should start with <?php, no spaces or BOM
```

### 7. "Class Not Found" or "Include Path" Errors

**Symptoms:**
- Fatal errors about missing classes
- "require_once" failures
- Authentication class not found

**Solutions:**

#### A. Check File Structure
```bash
# Verify directory structure
tree -L 3 /var/www/html/mini-erp
```

Expected structure:
```
mini-erp/
├── config/
│   ├── config.php
│   └── database.php
├── src/
│   ├── classes/
│   └── includes/
├── public/
└── database/
```

#### B. Fix Include Paths
Most common issue is wrong relative paths. Update includes:
```php
// In public/*.php files, use:
require_once '../config/config.php';
require_once '../src/classes/Auth.php';
```

### 8. "Login Always Fails" 

**Symptoms:**
- Correct username/password rejected
- "Invalid credentials" message
- Can't access any pages

**Solutions:**

#### A. Check User Table
```bash
# Connect to database and check users
mysql -u your_user -p your_database
SELECT username, email, is_active FROM users;
```

#### B. Create New Admin User
```bash
# Use doctor to create emergency admin
php doctor.php --fix

# Or create manually:
php -r "
require_once '../config/database.php';
require_once '../src/classes/Auth.php';
\$auth = new Auth(new Database());
\$result = \$auth->createUser('admin', 'admin@test.com', 'admin123', 'Administrator', 'admin');
var_dump(\$result);
"
```

## 🛡️ Prevention Strategies

### 1. Regular Health Checks
```bash
# Run weekly diagnostic
php doctor.php

# Keep database exports current
php export_complete_schema.php  # At work
```

### 2. Backup Before Changes
```bash
# Create backup before major changes
php backup_database.php
```

### 3. Version Control Hygiene
```bash
# Always pull latest before working
git pull origin master

# Commit frequently
git add . && git commit -m "Work in progress"
```

### 4. Environment Consistency
```bash
# Use same PHP version as work
php -v

# Check MySQL version
mysql --version
```

## 📞 When All Else Fails

### 1. Nuclear Reset
```bash
# DANGER: Destroys everything and starts fresh
php emergency_reset.php
```

### 2. Clean Git Reset
```bash
# Reset to last known good commit
git reset --hard HEAD~1

# Or reset to specific commit
git reset --hard <commit-hash>
```

### 3. Fresh Clone
```bash
# Start completely over
cd ..
rm -rf mini-erp
git clone <repository-url> mini-erp
cd mini-erp/database
php setup_home_dev.php
```

## 🔧 Advanced Debugging

### Enable Debug Mode
Edit `config/config.php`:
```php
define('APP_DEBUG', true);
define('DEBUG_SQL', true);  // Log all SQL queries
```

### Database Query Logging
Add to database connection:
```php
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### Check PHP Configuration
```bash
php -m  # List loaded modules
php --ini  # Show configuration files
```

## 📚 Getting Help

1. **Run diagnostics first:** `php doctor.php`
2. **Check logs:** Apache/PHP error logs
3. **Verify environment:** PHP version, MySQL version
4. **Compare with work:** Configuration differences
5. **Fresh export:** Get latest database from work

## 🆘 Emergency Contacts

- **Database Issues:** Use `emergency_reset.php`
- **Code Issues:** `git reset --hard` to last working commit  
- **Complete Failure:** Fresh clone from repository

Remember: Your home development environment is meant to be disposable. Don't be afraid to reset completely if needed!