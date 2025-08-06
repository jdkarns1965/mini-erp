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

# Phase 2: Manufacturing Operations
*Extension to existing Phase 1 Material Tracking System*

## Overview
Phase 2 extends the material tracking foundation to handle complete manufacturing operations: molding with inserts, sequential assembly operations, poka-yoke quality stations, and finished goods management. Integrates seamlessly with existing FIFO material management and traceability systems.

## Manufacturing Context
- **Plastic injection molding** with insert molding capabilities
- **Sequential assembly operations** (primer → foam → clips → adhesive → rubber components)
- **Secondary operations** (drilling, trimming, machining)
- **Automated poka-yoke stations** (quality control + final assembly + labeling)
- **Flexible routing** for new contracts and product variations

## Core Manufacturing Modules

### Products Master
**Purpose:** Define what products are manufactured

**Data Structure:**
```
Product Code: AUTO-BRACKET-001
Product Description: Automotive Dashboard Bracket - Black
Customer: Ford Motor Company
Customer Part Number: F-DBB-12345
Product Category: Automotive Interior
Status: ACTIVE | DEVELOPMENT | OBSOLETE
Engineering Drawings: Link to documentation
Specifications: Material requirements, tolerances
Created Date: 2025-01-15
Created By: ENG-001 (John Smith)
```

**Integration with Phase 1:**
- Links to existing recipe management
- References material types from Phase 1 material master
- Supports traceability requirements

### Components Master
**Purpose:** Track assembly components and inserts (separate from molding materials)

**Component Types:**
- **Insert Components:** Threaded inserts, metal reinforcements, electronic parts
- **Assembly Components:** Clips, felts, foams, adhesive tapes, rubber seals
- **Secondary Components:** Hardware, fasteners, labels

**Data Structure:**
```
Component Code: INSERT-M6-THREAD-001
Description: M6 Threaded Insert - Brass
Category: INSERT | ASSEMBLY | SECONDARY
Unit of Measure: EA (each)
Unit Cost: $0.15
Supplier: FASTENER-CORP
Supplier Part Number: FC-M6-BR-001
Lead Time: 14 days
Reorder Point: 500 EA
Economic Order Quantity: 2000 EA
Quality Specs: Torque spec, material grade
```

**Inventory Management:**
- Separate inventory tracking from molding materials
- FIFO management for components with expiration dates
- Lot control for critical components (threaded inserts, etc.)
- Integration with existing receiving process

### Routing/Operations Master
**Purpose:** Define HOW products are manufactured (operation sequences)

**Operation Types:**
- **Molding Operations:** Include insert placement, molding parameters
- **Assembly Operations:** Sequential component application with timing
- **Secondary Operations:** Machining, drilling, trimming
- **Quality Operations:** Poka-yoke stations, inspections
- **Packaging Operations:** Final packaging, labeling

**Flexible Operation Numbering:**
```
Operation 10: Injection Molding with Insert Placement
Operation 15: Part Removal and Visual Inspection
Operation 20: Apply Primer to Surface A
Operation 22: Cure Wait (15 seconds)
Operation 25: Apply Foam Gasket
Operation 30: Install Metal Clips
Operation 35: Apply Adhesive Tape
Operation 40: Install Rubber Seal
Operation 45: Poka-Yoke Quality Check + Final Assembly
Operation 50: Packaging
Operation 60: Move to Finished Goods
```

**Operation Details:**
```
Operation ID: OP-APPLY-PRIMER-001
Operation Number: 20 (user-controlled sequencing)
Operation Name: Apply Primer to Surface A
Category: ASSEMBLY
Work Center: Assembly Station #2
Setup Time: 5 minutes
Run Time: 30 seconds per piece
Required Skills: Primer Application Certified
Dependencies: Must complete Operation 15 first
Components Consumed: PRIMER-SURFACE-001 (0.02 oz per piece)
Quality Checkpoints: Visual inspection of primer coverage
Special Instructions: Allow 15 second cure before next operation
```

**Routing Templates:**
- Standard operation templates for common processes
- Easy drag-and-drop operation insertion
- Clone and modify existing routings for similar products

### Bill of Materials (BOM)
**Purpose:** Define WHAT materials and components go into each product

**Multi-Level BOM Structure:**
```
Product: AUTO-BRACKET-001 (Dashboard Bracket - Black)
BOM Version: 1.2
Effective Date: 2025-02-01
Status: ACTIVE

MOLDING LEVEL:
├── Line 10: RESIN-ABS-BLACK-001 (2.20 lbs) - Operation 10
├── Line 15: INSERT-M6-THREAD-001 (2 ea) - Operation 10  
└── Line 20: RELEASE-AGENT-001 (0.02 lbs) - Operation 10

ASSEMBLY LEVEL:
├── Line 30: PRIMER-SURFACE-001 (0.02 oz) - Operation 20
├── Line 40: FOAM-GASKET-001 (1 ea) - Operation 25
├── Line 50: CLIP-METAL-001 (2 ea) - Operation 30
├── Line 60: ADHESIVE-TAPE-001 (6 inches) - Operation 35
└── Line 70: RUBBER-SEAL-001 (1 ea) - Operation 40
```

**BOM Line Details:**
```
Line Number: 30
Component: PRIMER-SURFACE-001
Description: Surface Primer for Foam Adhesion
Quantity Required: 0.02 oz per unit
Unit of Measure: OZ
Scrap Factor: 10% (1.10 multiplier)
Net Quantity: 0.022 oz per unit
Operation: 20 (Apply Primer)
Required: YES (cannot skip)
Alternative Components: PRIMER-SURFACE-002 (backup supplier)
```

**Integration with Phase 1:**
- **Material consumption** links to existing FIFO material inventory
- **Recipe integration** connects to existing recipe management
- **Lot traceability** extends existing traceability system

### Work Orders
**Purpose:** Plan and execute production runs

**Work Order Types:**
- **Production Orders:** Standard customer orders
- **Sample Orders:** Prototype and customer samples
- **Rework Orders:** Fixing defective parts
- **Maintenance Orders:** Equipment/mold maintenance

**Work Order Structure:**
```
Work Order: WO-2025-001234
Product: AUTO-BRACKET-001
Customer: Ford Motor Company
Order Quantity: 500 pieces
Due Date: 2025-02-15
Priority: NORMAL | RUSH | SAMPLE
Status: PLANNED | RELEASED | IN_PROGRESS | COMPLETED

MATERIAL ALLOCATION (from existing Phase 1 system):
├── RESIN-ABS-BLACK-001: 1100 lbs (from Lot LOT-2025-0156)
├── INSERT-M6-THREAD-001: 1000 ea (from Lot INS-2025-0089)
└── PRIMER-SURFACE-001: 11 oz (from Lot PRM-2025-0023)

OPERATION STATUS:
├── Operation 10 (Molding): COMPLETED - 500 pieces produced
├── Operation 20 (Primer): IN_PROGRESS - 300 pieces completed
├── Operation 25 (Foam): WAITING - 0 pieces completed
└── Operation 30 (Clips): WAITING - 0 pieces completed
```

**Material Allocation Process:**
1. Work order created with product and quantity
2. System calculates material requirements from BOM
3. **Integration with Phase 1:** FIFO system suggests oldest materials first
4. Material handler confirms material selection
5. Materials allocated and reserved for this work order
6. **Existing lot traceability** automatically maintained

### Work-in-Process (WIP) Tracking
**Purpose:** Track parts location and status between operations

**WIP Queue Management:**
```
Work Order: WO-2025-001234
Current Status by Operation:

Operation 10 (Molding): COMPLETED
├── Pieces Completed: 500
├── Pieces Good: 495
├── Pieces Scrapped: 5 (mold flash defects)
└── Ready for Next Operation: 495

Operation 20 (Primer Application): IN_PROGRESS  
├── Pieces Received: 495
├── Pieces Completed: 300
├── Pieces in Queue: 195
├── Pieces in Process: 0
└── Operation Efficiency: 95% (300/315 expected)

Operation 25 (Foam Application): WAITING
├── Pieces in Queue: 0
├── Waiting for: Operation 20 completion
└── Estimated Start: 2025-02-10 14:30
```

**Bottleneck Identification:**
- Real-time view of where work is backing up
- Operation efficiency tracking
- Estimated completion times
- Resource allocation optimization

### Quality Control & Poka-Yoke Integration
**Purpose:** Manage automated quality stations and decision making

**Poka-Yoke Station Configuration:**
```
Station ID: POKE-YOKE-003
Station Name: Final Assembly & Quality Check
Products Handled: AUTO-BRACKET-001, CONSOLE-BRACKET-002
Capabilities: 
├── Final assembly operations
├── Component presence detection  
├── Witness mark application
├── Dimensional verification
├── Customer label generation (Ford API integration)
└── Internal barcode generation
```

**Quality Decision Flow:**
```
Part enters Poka-Yoke Station:
├── Station performs final assembly (if required)
├── Station applies witness mark (if required)
├── Station runs quality checks
├── PASS → Generate labels → Move to Finished Goods
└── FAIL → Route to rework queue or scrap

ERP Integration:
├── Receive work order info from ERP
├── Send completion data back to ERP
├── Update WIP status automatically
└── Update finished goods inventory
```

**Integration with Phase 1 Quality System:**
- Extends existing quality approval workflows
- Maintains dual approval requirements (Supervisor + Quality Inspector)
- Integrates with existing audit logging for ISO compliance

### Finished Goods Inventory
**Purpose:** Manage completed products ready for shipment

**Finished Goods Structure:**
```
Finished Good ID: FG-2025-001234-001
Work Order: WO-2025-001234
Product: AUTO-BRACKET-001
Lot Number: FG-LOT-2025-0234
Quantity Available: 495 pieces
Location: FG-A-15-B
Status: AVAILABLE | HOLD | SHIPPED
Quality Status: PASSED (Poka-Yoke Station #3)
Labels Applied: Customer labels (Ford system)
Packaging: Customer-specified boxes
Date Completed: 2025-02-10
Expiration Date: N/A (non-perishable)
```

**Traceability Integration:**
- **Forward traceability:** From material lots → finished parts
- **Backward traceability:** From finished parts → all material/component lots used
- **Complete audit trail** linking to Phase 1 material tracking
- **Customer traceability certificates** generated automatically

## Database Schema Extensions

### New Tables for Phase 2:
```sql
products - Product master data
components - Assembly components and inserts (separate from materials)
routing_templates - Standard operation templates
product_routing - Product-specific operation sequences  
bom_header - BOM version control and approval
bom_lines - Detailed material/component requirements
work_orders - Production planning and execution
wip_tracking - Work-in-process status by operation
pokayoke_stations - Quality station configuration
pokayoke_results - Quality test results and decisions
finished_goods - Completed product inventory
component_inventory - Assembly component tracking (extends Phase 1)
```

### Enhanced Phase 1 Tables:
```sql
materials - Add component flag (is_component: true/false)
inventory - Extend to handle both materials and components
jobs - Enhance to link to work_orders
quality_events - Extend to include poka-yoke decisions
traceability - Extend to include component lot tracking
```

## User Interface Extensions

### New Screens for Phase 2:

**Production Dashboard:**
- Active work orders by priority
- WIP status across all operations
- Bottleneck identification
- Material shortage alerts
- Quality holds and issues

**Work Order Management:**
- Create new production orders
- Material allocation from Phase 1 FIFO system
- Operation status tracking
- Quality checkpoint approvals

**BOM Management:**
- Product BOM creation and editing
- Material/component selection from masters
- Operation assignment
- Version control and approvals

**Components Inventory:**
- Separate component receiving (extends Phase 1 receiving)
- Component FIFO management
- Component shortage alerts
- Integration with existing inventory dashboard

**WIP Tracking:**
- Real-time operation status
- Queue management
- Efficiency reporting
- Resource allocation

**Quality Control:**
- Poka-yoke station monitoring
- Quality hold management
- Rework order creation
- Quality reporting (extends Phase 1)

## Integration Points with Phase 1

### Material Consumption:
- Work orders consume materials via existing FIFO system
- Recipe management extends to BOM component ratios
- Existing lot traceability maintained through production

### Quality Integration:
- Existing quality approval workflows extended to production
- Dual approval system (Supervisor + Quality Inspector) maintained
- ISO compliance logging enhanced for manufacturing operations

### User Role Extensions:
- **Material Handler:** Gains work order material allocation responsibilities
- **Quality Inspector:** Gains production quality checkpoint responsibilities  
- **Supervisor:** Gains production oversight and approval responsibilities
- **New Role - Production Operator:** Operation execution and WIP updates

### Reporting Integration:
- Existing traceability reports extended to finished goods
- Customer certificates include complete material + component lots
- ISO audit reports include manufacturing quality events
- Efficiency reporting adds operation and WIP metrics

## Development Priorities for Phase 2

### Phase 2.1 - Foundation:
- Products master and BOM creation
- Components master and inventory
- Basic routing/operations setup
- Work order creation

### Phase 2.2 - Production Flow:
- WIP tracking and operation status
- Material/component allocation from Phase 1
- Basic finished goods management

### Phase 2.3 - Quality Integration:
- Poka-yoke station integration
- Quality decision workflows
- Rework and scrap handling

### Phase 2.4 - Optimization:
- Production scheduling
- Efficiency reporting
- Advanced traceability
- Customer integration APIs

## Success Criteria for Phase 2

### Operational Goals:
- Eliminate paper-based production tracking
- Real-time visibility into production status
- Automated material consumption from FIFO system
- Complete lot traceability from materials through finished goods
- Integrated quality control with ISO compliance

### Measurable Outcomes:
- Sub-10-second work order material allocation
- Real-time WIP status updates
- 100% lot traceability for all finished products
- Automated quality station integration
- Complete elimination of production paperwork

## Future Integration Opportunities

### Phase 3 Considerations:
- Customer order integration and scheduling
- Advanced production planning and optimization
- Predictive maintenance integration
- Supply chain optimization
- Advanced analytics and reporting

This Phase 2 extension seamlessly builds upon the solid material tracking foundation from Phase 1, creating a complete manufacturing operations system while maintaining all existing ISO compliance and traceability capabilities.
