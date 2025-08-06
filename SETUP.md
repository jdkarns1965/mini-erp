# Manufacturing ERP Setup Guide

## Quick Start (Database Setup Required)

Your development environment is ready! Here's how to complete the setup:

### 1. Database Setup

The MySQL database needs to be initialized. You have two options:

#### Option A: Manual Setup (Recommended)
```bash
# Access MySQL (you'll need the root password)
mysql -u root -p

# Create database and user
CREATE DATABASE mini_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mini_erp_user'@'localhost' IDENTIFIED BY 'secure123!';
GRANT ALL PRIVILEGES ON mini_erp.* TO 'mini_erp_user'@'localhost';
FLUSH PRIVILEGES;
USE mini_erp;

# Import the schema
SOURCE /var/www/html/mini-erp/database/migrations/002_manufacturing_erp_schema.sql;
```

#### Option B: Update Environment File
Edit `/var/www/html/mini-erp/config/.env` with your MySQL root password:
```
DB_USER=root
DB_PASS=your_mysql_root_password
```

Then run: `cd /var/www/html/mini-erp/database && php setup_database.php`

### 2. Access the System

1. **Visit:** http://localhost/mini-erp/public/
2. **Login:** 
   - Username: `admin`
   - Password: `admin123`
3. **Important:** Change the default password immediately!

### 3. GitHub Repository Setup

Create your GitHub repository:
```bash
# Create repository on GitHub (private recommended)
# Then add remote and push:
git remote add origin https://github.com/yourusername/mini-erp.git
git branch -M main
git push -u origin main
```

## System Features

### ✅ Completed Features

1. **User Authentication System**
   - Role-based access control (5 user types)
   - Session management with timeout
   - Password hashing and security
   - Audit logging for ISO compliance

2. **Database Schema**
   - Complete manufacturing traceability schema
   - Users, Materials, Inventory, Recipes, Jobs
   - Material usage tracking and traceability
   - Quality events and audit logging

3. **User Roles**
   - **Admin:** Full system access, user management
   - **Supervisor:** Production oversight, approvals
   - **Material Handler:** Material receiving, inventory
   - **Quality Inspector:** Quality control, recipe approvals
   - **Viewer:** Read-only access for reports

4. **Professional UI**
   - Manufacturing-focused dashboard
   - Role-specific quick actions
   - Mobile-responsive design
   - Clean, professional appearance

### 🚧 Next Development Phase

Ready to build:
- Material receiving functionality
- FIFO inventory management
- Recipe management with dual approval
- Production job tracking
- Traceability reports

## Development Workflow

```bash
# Create feature branch
git checkout -b feature/material-receiving

# Develop and test
# ... make changes ...

# Commit and push
git add -A
git commit -m "Add material receiving functionality"
git push origin feature/material-receiving

# Create pull request on GitHub
# Merge after review
```

## File Structure

```
mini-erp/
├── config/
│   ├── .env (database credentials)
│   ├── config.php (application config)
│   └── database.php (database class)
├── public/ (web root)
│   ├── index.php (main dashboard)
│   ├── login.php (authentication)
│   └── css/style.css (styling)
├── src/
│   └── classes/
│       └── Auth.php (authentication system)
├── database/
│   ├── setup_database.php (installer)
│   └── migrations/ (schema files)
└── CLAUDE.md (detailed specifications)
```

## Security Notes

- Default admin password MUST be changed
- Database credentials in `.env` file (not in Git)
- All user input is sanitized
- SQL injection protection via PDO
- Session security and timeout handling

## Support

- Check CLAUDE.md for detailed specifications
- Database schema includes all traceability requirements
- Authentication system ready for ISO compliance
- Mobile-responsive for production floor use

Your manufacturing ERP foundation is complete and ready for feature development!