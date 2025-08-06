# Mini-ERP Production Deployment Guide

## Prerequisites
- Web server with PHP 7.4+ support
- MySQL 5.7+ database
- SSL certificate recommended for production use

## Quick Deployment Steps

### 1. Database Setup
```sql
-- Create database and user
CREATE DATABASE mini_erp_prod;
CREATE USER 'mini_erp_user'@'%' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON mini_erp_prod.* TO 'mini_erp_user'@'%';
FLUSH PRIVILEGES;
```

### 2. Upload Files
Upload all files to your web server's public directory (e.g., public_html/mini-erp/)

### 3. Configuration
- Copy `config/.env.production` to `config/.env`
- Edit `config/.env` with your database credentials and domain
- Ensure proper file permissions (755 for directories, 644 for files)

### 4. Database Migration
```bash
php database/migrate.php
```

### 5. Create Initial Admin User
Access: `https://your-domain.com/mini-erp/database/create_admin_user.php`

## File Structure
```
mini-erp/
├── public/          # Web-accessible files
├── src/             # PHP classes and includes
├── config/          # Configuration files
├── database/        # Database scripts and migrations
└── docs/            # Documentation
```

## Security Notes
- Never commit `.env` files to version control
- Use strong passwords for database users
- Enable SSL/HTTPS for production use
- Set proper file permissions on server

## Default Login
- Username: admin
- Password: admin123
- **CHANGE IMMEDIATELY AFTER FIRST LOGIN**

## Features Available
- ✅ User Authentication (5 roles)
- ✅ Materials Master & FIFO Inventory
- ✅ Recipe Management with Approvals
- ✅ Products Master with Customer Integration
- ✅ Production Job Tracking
- ✅ Complete Lot Traceability
- ✅ Mobile-Responsive Interface

## Support
For issues or questions, check the troubleshooting section in database/TROUBLESHOOTING.md