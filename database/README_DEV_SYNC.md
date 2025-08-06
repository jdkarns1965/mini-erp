# Home Development Environment Database Sync

This guide explains how to keep your home development environment synchronized with your work database.

## Overview

The Mini ERP system includes several tools to help you sync your database between work and home:

- **Complete Database Export/Import** - Full schema and data sync
- **Migration Tracking** - Incremental schema changes
- **Environment-specific Configuration** - Safe development practices

## Initial Setup (First Time)

### 1. At Work - Export Your Database

```bash
# Navigate to database directory
cd /var/www/html/mini-erp/database

# Export complete database (schema + data)
php export_complete_schema.php
```

This creates `complete_database_export.sql` with:
- All table structures
- All data (users, materials, suppliers, customers, inventory, etc.)
- Proper foreign key handling

### 2. Transfer Export File to Home

Options for transferring the export:

**Option A: Git (Recommended for small databases)**
```bash
# Add export to git (if < 50MB)
git add database/complete_database_export.sql
git commit -m "Add database export for home sync"
git push
```

**Option B: Cloud Storage**
- Upload `complete_database_export.sql` to Google Drive, Dropbox, etc.
- Download at home

**Option C: USB Drive**
- Copy file to USB drive
- Transfer to home system

### 3. At Home - Import Database

```bash
# Pull latest code (if using Git)
git pull origin master

# Navigate to database directory
cd /var/www/html/mini-erp/database

# Import database (will prompt for confirmation)
php sync_to_home.php
```

## Regular Synchronization

### When Database Structure Changes

If schema changes are made (new tables, columns, etc.), use the migration system:

**At Work:**
```bash
# Check migration status
php migration_tracker.php status

# Run any new migrations
php migration_tracker.php migrate

# Export updated database
php export_complete_schema.php
```

**At Home:**
```bash
# Pull latest code and migrations
git pull origin master

# Import latest database export
php sync_to_home.php

# Or just run new migrations
php migration_tracker.php migrate
```

### For Data-Only Updates

If you just need updated data (new materials, customers, etc.) without schema changes:

**Option 1: Full Re-sync**
```bash
# At work: Export
php export_complete_schema.php

# At home: Import
php sync_to_home.php
```

**Option 2: Selective Data Export** (Advanced)
Create custom scripts to export/import specific tables only.

## File Structure

```
database/
├── complete_database_export.sql     # Full database dump
├── export_complete_schema.php       # Export script
├── sync_to_home.php                # Import script  
├── migration_tracker.php           # Migration management
├── add_container_type.php          # Individual migrations
├── add_phone_extension.php         # Individual migrations
├── add_contact_title.php           # Individual migrations
├── fix_lot_constraint.php          # Individual migrations
└── README_DEV_SYNC.md              # This file
```

## Migration System

The migration tracker ensures schema changes are applied consistently:

```bash
# Check what migrations are available vs executed
php migration_tracker.php status

# Run all pending migrations
php migration_tracker.php migrate
```

### Migration Files

Migration files follow naming conventions:
- `add_*.php` - Add new columns/tables
- `fix_*.php` - Fix constraints/indexes  
- `alter_*.php` - Modify existing structures
- `create_*.php` - Create new tables

Each migration:
- Is run only once (tracked in `migrations` table)
- Includes error handling
- Shows progress and results

## Environment Configuration

### Development vs Production

Make sure your home environment is configured properly:

**config/config.php:**
```php
// Home development settings
define('APP_ENV', 'development');
define('APP_DEBUG', true);
```

**config/database.php:**
```php
// Home database connection
$this->host = 'localhost';      
$this->dbname = 'mini_erp_dev';  // Different DB name
$this->username = 'dev_user';    // Dev user
$this->password = 'dev_pass';    // Dev password
```

### Safety Checks

The sync script includes safety checks:
- Only runs in development environment
- Prompts for confirmation before overwriting
- Shows file size and export date
- Verifies import success

## Troubleshooting

### Common Issues

**1. "Export file not found"**
```bash
# Make sure export was created at work
php export_complete_schema.php
```

**2. "Permission denied"**
```bash
# Check file permissions
chmod 644 complete_database_export.sql
```

**3. "Database connection failed"**
- Verify your home database credentials in `config/database.php`
- Make sure MySQL is running
- Check database exists

**4. "Migration failed"**
```bash
# Check migration status
php migration_tracker.php status

# Run migrations individually if needed
php add_container_type.php
```

### Large Database Files

If export file is too large for Git:

1. **Use Git LFS:**
```bash
git lfs track "*.sql"
git add .gitattributes database/complete_database_export.sql
```

2. **Compress export:**
```bash
gzip complete_database_export.sql
# Transfer .sql.gz file
gunzip complete_database_export.sql.gz
```

3. **Use cloud storage** for large files

## Best Practices

### Security
- Never commit production passwords to Git
- Use different database names for dev/prod
- Keep sensitive data out of development exports

### Workflow
1. **Daily:** Pull code changes from Git
2. **Weekly:** Sync database if working with data-heavy features  
3. **When needed:** Run migrations for schema changes
4. **Before major features:** Full database sync to ensure consistency

### Data Management
- Use sample/test data in development when possible
- Regularly clean up old development data
- Keep production data exports secure

## Advanced Usage

### Custom Export Scripts

Create specialized export scripts for specific needs:

```php
// export_materials_only.php
$tables = ['materials', 'suppliers'];
// Export only these tables...
```

### Automated Sync

Set up automated syncing with cron jobs:

```bash
# Daily database sync at 2 AM
0 2 * * * cd /var/www/html/mini-erp/database && php sync_to_home.php < echo "yes"
```

### Multi-Environment Support

Extend the system to support multiple environments:
- `dev` - Local development
- `staging` - Pre-production testing  
- `production` - Live system

This system ensures your home development environment stays synchronized with your work environment while maintaining proper separation and safety checks.