# Database Migration Guide

## For Development Teams

When database schema changes are made, follow these steps to keep all development machines synchronized.

## Method 1: Using Migration Scripts (Recommended)

### Creating a Migration
1. Create a new SQL file in `database/migrations/` with format: `XXX_description.sql`
   - Example: `004_add_user_preferences.sql`
2. Include both UP and rollback instructions in comments
3. Commit the migration file to Git

### Applying Migrations
Other developers run:
```bash
cd /var/www/html/mini-erp/database
php migrate.php
```

This will:
- Track which migrations have been applied
- Apply only new migrations
- Show success/failure status

### Example Migration File
```sql
-- Migration 004: Add user preferences table
-- UP: Creates the table
-- DOWN: DROP TABLE user_preferences;

CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Method 2: Schema Export/Import

### Export Current Schema
```bash
cd /var/www/html/mini-erp/database
chmod +x export_schema.sh
./export_schema.sh
```

### Import on Other Machines
```bash
mysql -u mini_erp_user -p mini_erp < current_schema.sql
```

## Method 3: Git Hooks (Advanced)

Create `.git/hooks/post-merge` to auto-run migrations after git pull:
```bash
#!/bin/bash
cd database && php migrate.php
```

## Best Practices

1. **Always backup before migrations**:
   ```bash
   mysqldump -u mini_erp_user -p mini_erp > backup_$(date +%Y%m%d).sql
   ```

2. **Test migrations on development data first**

3. **Write reversible migrations when possible**

4. **Document breaking changes in migration files**

5. **Use descriptive migration names**

## Current Migration Status

Run this to see applied migrations:
```bash
mysql -u mini_erp_user -p mini_erp -e "SELECT * FROM migrations ORDER BY applied_at;"
```