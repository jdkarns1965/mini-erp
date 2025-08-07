-- Mini ERP Database Complete Export
-- Generated on: 2025-08-07 14:12:11
-- Use this file to set up your home development environment

SET FOREIGN_KEY_CHECKS = 0;

-- Table structure for table `audit_log`
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_actions` (`user_id`,`timestamp`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_timestamp` (`timestamp`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `audit_log`
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `timestamp`) VALUES
('1', '1', 'LOGIN', 'users', '1', NULL, '{\"ip_address\": \"::1\", \"login_time\": \"2025-08-06 09:19:52\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-06 09:19:52'),
('2', '1', 'LOGIN', 'users', '1', NULL, '{\"ip_address\": \"::1\", \"login_time\": \"2025-08-06 12:09:05\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-06 12:09:05'),
('3', '1', 'LOGIN', 'users', '1', NULL, '{\"ip_address\": \"::1\", \"login_time\": \"2025-08-06 13:02:10\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-06 13:02:10'),
('4', '1', 'LOGIN', 'users', '1', NULL, '{\"ip_address\": \"::1\", \"login_time\": \"2025-08-07 09:56:27\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', '2025-08-07 09:56:27'),
('5', '1', 'LOGIN', 'users', '1', NULL, '{\"ip_address\": \"::1\", \"login_time\": \"2025-08-07 12:13:28\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-07 12:13:28'),
('6', '1', 'LOGIN', 'users', '1', NULL, '{\"ip_address\": \"::1\", \"login_time\": \"2025-08-07 13:46:47\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-07 13:46:47');

-- Table structure for table `contacts`
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`first_name`,_utf8mb4' ',`last_name`)) STORED,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_ext` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_full_name` (`full_name`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `contacts`
INSERT INTO `contacts` (`id`, `first_name`, `last_name`, `full_name`, `email`, `phone`, `phone_ext`, `mobile_phone`, `job_title`, `department`, `notes`, `is_active`, `created_at`, `created_by`) VALUES
('1', 'Avriel', 'Altschul', 'Avriel Altschul', 'aaltschul@derbyfab.com', '(937) 498-4054', NULL, NULL, 'Customer Service Representative', NULL, NULL, '1', '2025-08-06 11:23:48', '1');

-- Table structure for table `customer_contact_view`
DROP TABLE IF EXISTS `customer_contact_view`;
;

-- No data to dump for table `customer_contact_view`

-- Table structure for table `customer_contacts`
DROP TABLE IF EXISTS `customer_contacts`;
CREATE TABLE `customer_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `contact_id` int NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_contact` (`customer_id`,`contact_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_is_primary` (`is_primary`),
  CONSTRAINT `customer_contacts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_contacts_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `customer_contacts`
INSERT INTO `customer_contacts` (`id`, `customer_id`, `contact_id`, `role`, `is_primary`, `is_active`, `created_at`) VALUES
('1', '1', '1', 'Primary', '1', '1', '2025-08-06 11:23:48');

-- Table structure for table `customer_emails`
DROP TABLE IF EXISTS `customer_emails`;
CREATE TABLE `customer_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_type` enum('contact','department','returns','purchasing','engineering','quality','shipping','billing','general') COLLATE utf8mb4_unicode_ci DEFAULT 'contact',
  `description` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_email_type` (`email_type`),
  CONSTRAINT `customer_emails_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_emails_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `customer_emails`

-- Table structure for table `customers`
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_ext` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'USA',
  `is_active` tinyint(1) DEFAULT '1',
  `payment_terms` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `customers`
INSERT INTO `customers` (`id`, `customer_code`, `customer_name`, `contact_person`, `contact_title`, `email`, `phone`, `phone_ext`, `address_line1`, `address_line2`, `city`, `state`, `zip_code`, `country`, `is_active`, `payment_terms`, `credit_limit`, `notes`, `created_at`, `created_by`) VALUES
('1', 'CUST001', 'Derby Fabricating Solutions', 'Avriel Altschul', 'Customer Service Representative', 'aaltschul@derbyfab.com', '(937) 498-4054', '1104', '570 Lester Avenue', NULL, 'Sidney', 'OH', '45365', 'USA', '0', NULL, '0.00', NULL, '2025-08-06 11:23:48', '1');

-- Table structure for table `inventory`
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `material_id` int NOT NULL,
  `lot_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_lot_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity_received` decimal(10,3) NOT NULL,
  `quantity_available` decimal(10,3) NOT NULL,
  `container_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'gaylord',
  `unit_cost` decimal(10,4) DEFAULT '0.0000',
  `received_date` date NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `location` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','reserved','hold','consumed') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `received_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_material_fifo` (`material_id`,`received_date`),
  KEY `idx_lot_number` (`lot_number`),
  KEY `idx_status` (`status`),
  KEY `received_by` (`received_by`),
  KEY `idx_material_lot_lookup` (`material_id`,`lot_number`,`received_date`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `inventory`
INSERT INTO `inventory` (`id`, `material_id`, `lot_number`, `supplier_lot_number`, `quantity_received`, `quantity_available`, `container_type`, `unit_cost`, `received_date`, `expiration_date`, `location`, `status`, `received_by`, `created_at`, `updated_at`) VALUES
('1', '1', '123789Z452', '123789Z452', '2205.000', '2205.000', 'gaylord', '0.0000', '2025-08-06', NULL, 'RECEIVING', 'available', '1', '2025-08-06 09:37:46', '2025-08-06 09:37:46'),
('2', '1', '5679845', '5679845', '2205.000', '2205.000', 'gaylord', '0.0000', '2025-08-06', NULL, 'RECEIVING', 'available', '1', '2025-08-06 09:55:06', '2025-08-06 09:55:06'),
('3', '6', '753421', '753421', '2205.000', '2205.000', 'gaylord', '0.0000', '2025-08-06', NULL, 'RECEIVING', 'available', '1', '2025-08-06 10:35:24', '2025-08-06 10:35:24'),
('6', '6', '147258', '', '2204.900', '2204.900', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:54:45', '2025-08-06 10:54:45'),
('7', '6', '147258', '', '2204.900', '2204.900', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:54:45', '2025-08-06 10:54:45'),
('8', '6', '147258', '', '1500.000', '1500.000', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:54:45', '2025-08-06 10:54:45'),
('9', '6', '369258', '', '2204.900', '2204.900', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:59:55', '2025-08-06 10:59:55'),
('10', '6', '369258', '', '1499.800', '1499.800', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:59:55', '2025-08-06 10:59:55'),
('11', '6', '369258', '', '2204.800', '2204.800', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:59:55', '2025-08-06 10:59:55'),
('12', '6', '369258', '', '2204.900', '2204.900', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:59:55', '2025-08-06 10:59:55'),
('13', '6', '369258', '', '2205.000', '2205.000', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:59:55', '2025-08-06 10:59:55'),
('14', '6', '369258', '', '600.000', '600.000', 'gaylord', '0.0000', '2025-08-06', NULL, '', 'available', '1', '2025-08-06 10:59:55', '2025-08-06 10:59:55'),
('15', '6', '456456', '', '599.900', '599.900', 'gaylord', '0.0000', '2025-08-01', NULL, '', 'available', '1', '2025-08-06 11:02:33', '2025-08-06 11:02:33'),
('16', '6', '456456', '', '2205.000', '2205.000', 'gaylord', '0.0000', '2025-08-01', NULL, '', 'available', '1', '2025-08-06 11:02:33', '2025-08-06 11:02:33');

-- Table structure for table `jobs`
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int NOT NULL,
  `recipe_id` int NOT NULL,
  `target_quantity` int NOT NULL,
  `produced_quantity` int DEFAULT '0',
  `scrap_quantity` int DEFAULT '0',
  `status` enum('planned','in_progress','completed','on_hold','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'planned',
  `priority` enum('low','normal','high','rush') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `scheduled_date` date DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `operator_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_number` (`job_number`),
  KEY `idx_job_number` (`job_number`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled_date` (`scheduled_date`),
  KEY `product_id` (`product_id`),
  KEY `recipe_id` (`recipe_id`),
  KEY `operator_id` (`operator_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`),
  CONSTRAINT `jobs_ibfk_3` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `jobs_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `jobs`

-- Table structure for table `material_usage`
DROP TABLE IF EXISTS `material_usage`;
CREATE TABLE `material_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `inventory_id` int NOT NULL,
  `quantity_used` decimal(10,3) NOT NULL,
  `usage_timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `recorded_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_job_materials` (`job_id`),
  KEY `idx_inventory_usage` (`inventory_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `material_usage_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  CONSTRAINT `material_usage_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`),
  CONSTRAINT `material_usage_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `material_usage`

-- Table structure for table `materials`
DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `material_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_type` enum('base_resin','color_concentrate','rework','component') COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `unit_of_measure` enum('lbs','kg','oz','ea','ft','in') COLLATE utf8mb4_unicode_ci DEFAULT 'lbs',
  `unit_cost` decimal(10,4) DEFAULT '0.0000',
  `reorder_point` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_code` (`material_code`),
  KEY `idx_material_code` (`material_code`),
  KEY `idx_material_type` (`material_type`),
  KEY `created_by` (`created_by`),
  KEY `idx_supplier_id` (`supplier_id`),
  CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `materials`
INSERT INTO `materials` (`id`, `material_code`, `material_name`, `material_type`, `supplier_name`, `supplier_id`, `unit_of_measure`, `unit_cost`, `reorder_point`, `is_active`, `created_at`, `created_by`) VALUES
('1', '90006', 'MATERIAL.BASE.ACETAL.CELCON M90.n/a.NATURAL', 'base_resin', 'NIFCO iSupply', '1', 'lbs', '0.0000', '0', '1', '2025-08-06 09:17:51', '1'),
('2', 'GRAY-ABC', 'Gray Concentrate ABC', 'color_concentrate', 'Color Master LLC', '2', 'lbs', '0.0000', '0', '1', '2025-08-06 09:17:51', '1'),
('3', 'BLACK-XYZ', 'Black Concentrate XYZ', 'color_concentrate', 'Color Master LLC', '2', 'lbs', '0.0000', '0', '1', '2025-08-06 09:17:51', '1'),
('6', '90007', 'MATERIAL.BASE.ACETAL.CELCON M90.n/a.BLACK', 'base_resin', NULL, '1', 'lbs', '0.0000', '0', '1', '2025-08-06 10:33:35', '1');

-- Table structure for table `migrations`
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `migrations`
INSERT INTO `migrations` (`id`, `migration_name`, `applied_at`) VALUES
('1', '002_manufacturing_erp_schema', '2025-08-06 10:14:03'),
('2', '003_fix_column_names', '2025-08-06 10:14:03'),
('3', 'add_contact_title.php', '2025-08-06 13:05:50'),
('4', 'add_container_type.php', '2025-08-06 13:05:50'),
('5', 'add_phone_extension.php', '2025-08-06 13:05:50'),
('6', 'fix_lot_constraint.php', '2025-08-06 13:05:50'),
('7', 'add_foreign_keys.php', '2025-08-06 13:05:58');

-- Table structure for table `products`
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_part_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `product_category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_weight` decimal(8,3) DEFAULT NULL,
  `cycle_time` int DEFAULT NULL,
  `cavity_count` int DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  `engineering_drawing_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `idx_product_code` (`product_code`),
  KEY `idx_customer` (`customer_name`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `products`

-- Table structure for table `quality_events`
DROP TABLE IF EXISTS `quality_events`;
CREATE TABLE `quality_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `event_type` enum('recipe_approval','production_stop','quality_hold','scrap_decision','rework_approval') COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `decision` enum('approved','rejected','rework','scrap','hold') COLLATE utf8mb4_unicode_ci NOT NULL,
  `decided_by` int NOT NULL,
  `supervisor_approval` int DEFAULT NULL,
  `quality_approval` int DEFAULT NULL,
  `event_timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_job_events` (`job_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_event_timestamp` (`event_timestamp`),
  KEY `decided_by` (`decided_by`),
  KEY `supervisor_approval` (`supervisor_approval`),
  KEY `quality_approval` (`quality_approval`),
  CONSTRAINT `quality_events_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  CONSTRAINT `quality_events_ibfk_2` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`),
  CONSTRAINT `quality_events_ibfk_3` FOREIGN KEY (`supervisor_approval`) REFERENCES `users` (`id`),
  CONSTRAINT `quality_events_ibfk_4` FOREIGN KEY (`quality_approval`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `quality_events`

-- Table structure for table `recipes`
DROP TABLE IF EXISTS `recipes`;
CREATE TABLE `recipes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `version` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0',
  `base_material_id` int NOT NULL,
  `concentrate_material_id` int DEFAULT NULL,
  `concentrate_percentage` decimal(5,2) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT '0',
  `approved_by_supervisor` int DEFAULT NULL,
  `approved_by_quality` int DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_version` (`product_id`,`version`),
  KEY `idx_product_recipe` (`product_id`,`is_active`),
  KEY `idx_approval_status` (`is_approved`),
  KEY `base_material_id` (`base_material_id`),
  KEY `concentrate_material_id` (`concentrate_material_id`),
  KEY `approved_by_supervisor` (`approved_by_supervisor`),
  KEY `approved_by_quality` (`approved_by_quality`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `recipes_ibfk_2` FOREIGN KEY (`base_material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `recipes_ibfk_3` FOREIGN KEY (`concentrate_material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `recipes_ibfk_4` FOREIGN KEY (`approved_by_supervisor`) REFERENCES `users` (`id`),
  CONSTRAINT `recipes_ibfk_5` FOREIGN KEY (`approved_by_quality`) REFERENCES `users` (`id`),
  CONSTRAINT `recipes_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `recipes`

-- Table structure for table `supplier_contact_view`
DROP TABLE IF EXISTS `supplier_contact_view`;
;

-- Dumping data for table `supplier_contact_view`
INSERT INTO `supplier_contact_view` (`supplier_id`, `supplier_code`, `supplier_name`, `contact_id`, `contact_name`, `first_name`, `last_name`, `email`, `phone`, `phone_ext`, `mobile_phone`, `job_title`, `department`, `contact_role`, `is_primary`, `contact_active`) VALUES
('3', 'SUPP003', 'Derby Fabricating Solutions', '1', 'Avriel Altschul', 'Avriel', 'Altschul', 'aaltschul@derbyfab.com', '(937) 498-4054', NULL, NULL, 'Customer Service Representative', NULL, 'Primary', '1', '1');

-- Table structure for table `supplier_contacts`
DROP TABLE IF EXISTS `supplier_contacts`;
CREATE TABLE `supplier_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `contact_id` int NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_supplier_contact` (`supplier_id`,`contact_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_contact_id` (`contact_id`),
  KEY `idx_is_primary` (`is_primary`),
  CONSTRAINT `supplier_contacts_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_contacts_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `supplier_contacts`
INSERT INTO `supplier_contacts` (`id`, `supplier_id`, `contact_id`, `role`, `is_primary`, `is_active`, `created_at`) VALUES
('1', '3', '1', 'Primary', '1', '1', '2025-08-06 11:24:42');

-- Table structure for table `supplier_emails`
DROP TABLE IF EXISTS `supplier_emails`;
CREATE TABLE `supplier_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_type` enum('contact','department','sales','support','billing','quality','shipping','returns','general') COLLATE utf8mb4_unicode_ci DEFAULT 'contact',
  `description` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_email_type` (`email_type`),
  CONSTRAINT `supplier_emails_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_emails_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `supplier_emails`

-- Table structure for table `suppliers`
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_ext` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'USA',
  `is_active` tinyint(1) DEFAULT '1',
  `payment_terms` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_time_days` int DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `suppliers`
INSERT INTO `suppliers` (`id`, `supplier_code`, `supplier_name`, `contact_person`, `contact_title`, `email`, `phone`, `phone_ext`, `address_line1`, `address_line2`, `city`, `state`, `zip_code`, `country`, `is_active`, `payment_terms`, `lead_time_days`, `notes`, `created_at`, `created_by`) VALUES
('1', 'SUPP001', 'NIFCO iStore', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USA', '1', NULL, '0', NULL, '2025-08-06 10:26:10', '1'),
('2', 'SUPP002', 'Color Master LLC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USA', '1', NULL, '0', NULL, '2025-08-06 10:26:10', '1'),
('3', 'SUPP003', 'Derby Fabricating Solutions', 'Avriel Altschul', NULL, 'aaltschul@derbyfab.com', '(937) 498-4054', '1104', '570 Lester Avenue', NULL, 'Sidney', 'OH', '45365', 'USA', '1', NULL, '0', NULL, '2025-08-06 11:24:42', '1');

-- Table structure for table `user_sessions`
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_sessions` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No data to dump for table `user_sessions`

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','supervisor','material_handler','quality_inspector','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
('1', 'admin', 'admin@company.com', '$2y$10$Ltt7ObgKdr6ykUaO4eM8jeSjprsoPrl3YjlSDkvNd6w4.eAzatkvm', 'System Administrator', 'admin', '1', '2025-08-07 13:46:47', '2025-08-06 09:17:50', '2025-08-07 13:46:47');

SET FOREIGN_KEY_CHECKS = 1;

-- Export completed successfully!
