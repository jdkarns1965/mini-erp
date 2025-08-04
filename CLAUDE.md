Mini ERP Inventory Tracker
Project Overview
Manufacturing inventory tracking and traceability system for plastic injection molded automotive and consumer parts. Primary focus on eliminating paper-based tracking and ensuring ISO compliance with full lot traceability from raw materials through finished goods.

Company Context:

50 employees across two 8-hour shifts
Plastic injection molding for automotive and consumer products
Light assembly operations
ISO certification in progress (one finding to resolve)
Critical need for lot traceability to meet automotive customer requirements
Technology Stack
Hosting Environment:

GreenGeeks shared web hosting
LAMP stack (Linux, Apache, MySQL, PHP)
Standard cPanel hosting features
Web-based responsive interface
Development Environment:

WSL2 (Windows Subsystem for Linux) for local LAMP stack
VS Code with Claude Code CLI
GitHub for version control and collaboration
Git-based deployment to GreenGeeks (pull/clone from repository)
Frontend Technologies:

HTML5, CSS3, JavaScript
Bootstrap for responsive mobile-friendly design
AJAX for dynamic updates
Barcode scanning capability (web-based)
Backend Technologies:

PHP 7.4+ (check GreenGeeks supported versions)
MySQL 5.7+ database
PDO for database connections
Session-based authentication
Phase 1: Core Inventory & Traceability System
User Roles and Authentication
User Types:

Admin - Full system access, user management, configuration
Supervisor - Production oversight, quality approvals, reporting
Material Handler - Material receiving, inventory management, job material assignment
Quality Inspector - Quality control, recipe approvals, traceability reports
Viewer - Read-only access for reports and lookups
Authentication Requirements:

Simple username/password login
PHP session management
Role-based permissions
Password reset functionality
Activity logging for ISO compliance
Material Management
Material Types:

Base resins (various types: 90006, etc.)
Color concentrates (various colors and types)
Returned/rework materials
Material Formats:

Gaylord boxes: 1320-2200 lbs
Bag skids: 30-40 bags @ 25kg each
Partial containers from production
50 lb concentrate boxes
Receiving Process:

Material Handler enters new material receipt
Required fields: Material type, lot number, weight, supplier, date received
System assigns unique internal ID
FIFO queue automatically updated
Print receiving labels if needed
FIFO Inventory Management:

Automatic oldest-first material selection
Track partial container usage by weight
Handle returned material (maintains original lot number and FIFO position)
Visual inventory status dashboard
Production Recipe Management
Recipe Components:

Base material + concentrate percentage
Part-specific color recipes
Recipe versioning and approval tracking
Recipe Development Workflow:

New Part Protocol:
RFQ provides initial concentrate percentage
System flags as "First Production Run"
Prompt for sample batch size (supervisor/material handler decision)
Mix small test batch first
Quality Approval Process:
Quality Inspector tests sample parts
If rejected: adjust recipe, test again
If approved: recipe becomes standard for that part
Dual approval required (Supervisor + Quality Inspector)
Recipe Refinement:
Mid-production quality issues require production stop
Both Supervisor and Quality Inspector must approve changes
System records all recipe changes with reasons and approvers
Updated recipe becomes new standard
Recipe Storage:

Part number → Material type + concentrate percentage
Example: Part #12345 = Material 90006 + 3% Gray Concentrate ABC
Version history with dates and approval records
Production Tracking
Job-Based Material Assignment:

Production job created with part number and quantity
System automatically suggests materials per FIFO
Material Handler confirms material selection
System calculates exact material + concentrate requirements
Automatic lot number association with production job
Material Consumption Tracking:

Weigh containers before/after production
Track partial container remaining quantities
Handle multiple lot consumption within single job
Record returned/rework material with original lot numbers
Quality Control Integration:

Production stop capability for quality issues
Recipe adjustment workflow
Dual approval system (Supervisor + Quality Inspector)
Material salvage vs. scrap decisions
All decisions logged for ISO compliance
Traceability System
Forward Traceability:

From material lot → show all parts produced
Include both base material and concentrate lot numbers
Backward Traceability:

From finished parts → show all material lots used
Critical for customer quality issues and recalls
Reporting Requirements:

Customer traceability certificates
ISO audit reports
Material usage reports
Quality incident tracking
Production efficiency metrics
Database Schema Requirements
Core Tables:

users - Authentication and roles
materials - Material types and specifications
inventory - Current material inventory with lots
recipes - Part-specific material formulations
jobs - Production jobs and work orders
material_usage - Job material consumption tracking
traceability - Lot-to-part relationships
quality_events - Quality stops and approvals
audit_log - All system changes for ISO compliance
Key Relationships:

Jobs → Material Usage → Inventory (lot tracking)
Parts → Recipes → Materials (formulation management)
Quality Events → Jobs → Users (approval tracking)
User Interface Requirements
Mobile-Responsive Design:

Works on phones, tablets, desktops
Bootstrap-based responsive layout
Touch-friendly buttons and forms
Barcode scanning via phone camera
Key Screens:

Dashboard - Inventory status, active jobs, alerts
Material Receiving - Add new material with lot numbers
Job Management - Create jobs, assign materials
Quality Control - Recipe approvals, production stops
Traceability Lookup - Fast lot/part searches
Reports - Customer certificates, audit reports
Workflow Optimization:

Minimal clicks for common tasks
Auto-complete and dropdowns
Instant search and filtering
Clear error messages and validation
Development Priorities
Phase 1.1 - Foundation (Start Here):

User authentication system
Basic material receiving
Simple inventory display
Database schema setup
Phase 1.2 - Core Features:

FIFO material selection
Recipe management
Basic job tracking
Material consumption logging
Phase 1.3 - Quality & Traceability:

Quality control workflows
Dual approval system
Traceability reports
Customer certificates
Phase 1.4 - Polish:

Mobile optimization
Barcode scanning
Advanced reporting
Performance optimization
Future Modules (Post-Phase 1)
Module 2: Customer orders and scheduling
Module 3: Finished goods inventory and shipping
Module 4: Purchasing and vendor management
Module 5: Equipment and mold tracking
Development Workflow and Version Control
Git Repository Setup:

Initialize Git repository for project
Create GitHub repository (private recommended for business application)
Set up proper .gitignore for PHP projects (exclude config files with credentials)
Use branching strategy: main/production, develop, feature branches
WSL2 Development Environment:

Ubuntu/Debian on WSL2 with LAMP stack
Apache virtual host configuration for local development
MySQL service running in WSL2
PHP with necessary extensions (PDO, mysqli, etc.)
Development Process:

Create feature branches for new functionality
Develop and test locally in WSL2 environment
Commit changes with descriptive messages
Push to GitHub repository
Deploy to GreenGeeks staging area for testing
Merge to main branch after testing
Deploy to GreenGeeks production
Deployment Strategy:

GreenGeeks deployment via Git (SSH access or Git integration if available)
Alternative: Automated deployment using GitHub Actions (if GreenGeeks supports)
Separate configuration files for local, staging, and production environments
Database migration scripts for schema updates
File Structure:

mini-erp/
├── .git/
├── .gitignore
├── README.md
├── CLAUDE.md
├── config/
│   ├── database.php (local config)
│   └── database.production.php (production config - not in Git)
├── public/ (web root)
│   ├── index.php
│   ├── css/
│   ├── js/
│   └── assets/
├── src/
│   ├── classes/
│   ├── includes/
│   └── templates/
├── database/
│   ├── migrations/
│   └── seeds/
└── docs/
Check PHP version and available extensions
MySQL database limits and performance
File upload limits for any import features
SSL certificate for secure login
VS Code + Claude Code + Git Integration:

Maintain clean file structure with proper Git workflow
Use consistent coding standards and commit message format
Document all custom functions and API endpoints
Regular commits with descriptive messages
Feature branch development with proper merge requests
.gitignore configuration for PHP projects (exclude vendor/, config with credentials)
Security Requirements:

SQL injection prevention (PDO prepared statements)
XSS protection
CSRF tokens for forms
Secure password handling
Session security
Performance Considerations:

Efficient database queries
Minimal external dependencies
Optimized for shared hosting environment
Fast page load times for production floor use
Success Criteria
Primary Goals:

Eliminate paper-based tracking completely
Enable instant traceability lookups for customers
Ensure ISO compliance for ongoing audits
Reduce time spent on inventory management
Prevent quality escapes through better tracking
Measurable Outcomes:

Sub-5-second traceability report generation
Zero lost paper records
100% lot traceability for automotive parts
Reduced inventory discrepancies
Faster customer service response times
Getting Started Checklist
Set up development environment:
Configure WSL2 with Ubuntu and LAMP stack
Install Git and configure GitHub SSH keys
Set up VS Code with WSL2 integration
Install Claude Code CLI in WSL2 environment
Initialize project:
Create GitHub repository (private)
Clone repository to WSL2 environment
Set up proper .gitignore for PHP projects
Initialize basic project structure
Development workflow:
Create MySQL database and initial schema
Implement user authentication system
Build material receiving functionality
Test locally on WSL2 LAMP stack
Commit and push to GitHub regularly
Deployment preparation:
Configure GreenGeeks SSH access for Git deployment
Set up staging environment on GreenGeeks
Create production configuration files
Test deployment process
Go live:
Deploy to GreenGeeks production
Train material handlers and quality inspectors
Monitor and iterate based on user feedback
