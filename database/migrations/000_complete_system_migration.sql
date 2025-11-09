-- ============================================================================
-- COMPLETE SYSTEM MIGRATION (ALL-IN-ONE FOR NEW INSTALLATIONS)
-- ============================================================================
-- This is the COMPLETE migration for the entire ERP system
-- Creates all permission system tables, business module tables, and seeds data
-- Includes: Permission system, Manager permissions, Staff permissions, Business tables
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================
-- Usage: mysql -u username -p database_name < database/migrations/000_complete_system_migration.sql
-- ============================================================================
-- IMPORTANT: Run this AFTER the initial installer creates core tables (users, companies, etc.)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- PART 0: MODULE LABELS SYSTEM (For Customization)
-- ============================================================================

-- Create erp_module_labels table for custom module naming
CREATE TABLE IF NOT EXISTS `erp_module_labels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `module_code` VARCHAR(50) NOT NULL COMMENT 'Internal code name (accounting, bookings, etc)',
  `default_label` VARCHAR(100) NOT NULL COMMENT 'Default display name',
  `custom_label` VARCHAR(100) DEFAULT NULL COMMENT 'Custom label set by super admin',
  `icon_class` VARCHAR(50) DEFAULT NULL COMMENT 'Icon class for the module',
  `display_order` INT(11) DEFAULT 0 COMMENT 'Order in navigation',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether module is visible',
  `updated_by` INT(11) DEFAULT NULL COMMENT 'User who last updated',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_module_code` (`module_code`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default module labels (using Bootstrap Icons format)
INSERT INTO erp_module_labels (module_code, default_label, icon_class, display_order, is_active) VALUES
('dashboard', 'Dashboard', 'bi-speedometer2', 1, 1),
('accounting', 'Accounting', 'bi-calculator', 2, 1),
('bookings', 'Bookings', 'bi-calendar', 3, 1),
('properties', 'Properties', 'bi-building', 4, 1),
('inventory', 'Inventory', 'bi-box-seam', 5, 1),
('utilities', 'Utilities', 'bi-lightning', 6, 1),
('reports', 'Reports', 'bi-bar-chart', 7, 1),
('settings', 'Settings', 'bi-gear', 8, 1),
('users', 'User Management', 'bi-people', 9, 1),
('notifications', 'Notifications', 'bi-bell', 10, 1),
('pos', 'Point of Sale', 'bi-cart', 11, 1),
('tax', 'Tax Management', 'bi-file-text', 12, 1)
ON DUPLICATE KEY UPDATE default_label = VALUES(default_label);

-- ============================================================================
-- PART 1: PERMISSION SYSTEM
-- ============================================================================

-- STEP 1: Create erp_permissions table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `module` VARCHAR(100) NOT NULL,
    `permission` VARCHAR(50) NOT NULL COMMENT 'read, write, delete, create, update',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_module_permission` (`module`, `permission`),
    KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STEP 2: Create erp_roles table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `role_name` VARCHAR(100) NOT NULL,
    `role_code` VARCHAR(50) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_system` TINYINT(1) DEFAULT 0 COMMENT 'System roles cannot be deleted',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_role_code` (`role_code`),
    KEY `idx_role_name` (`role_name`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STEP 3: Create erp_role_permissions table (CRITICAL JUNCTION TABLE)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_role_permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `role_id` INT(11) NOT NULL,
    `permission_id` INT(11) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
    KEY `idx_role_id` (`role_id`),
    KEY `idx_permission_id` (`permission_id`),
    CONSTRAINT `fk_role_permissions_role` 
        FOREIGN KEY (`role_id`) REFERENCES `erp_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission` 
        FOREIGN KEY (`permission_id`) REFERENCES `erp_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STEP 4: Insert all system roles
-- ============================================================================
INSERT IGNORE INTO `erp_roles` (`role_name`, `role_code`, `description`, `is_system`, `is_active`, `created_at`) VALUES
('Super Admin', 'super_admin', 'Full system access with all permissions', 1, 1, NOW()),
('Admin', 'admin', 'Administrator with system access', 1, 1, NOW()),
('Manager', 'manager', 'Management role with full business module access', 1, 1, NOW()),
('Staff', 'staff', 'Staff level access', 1, 1, NOW()),
('User', 'user', 'Basic user role', 1, 1, NOW()),
('Accountant', 'accountant', 'Accounting focused role', 0, 1, NOW());

-- STEP 5: Insert all required permissions for all modules
-- ============================================================================
INSERT IGNORE INTO `erp_permissions` (`module`, `permission`, `description`, `created_at`) VALUES
-- Accounting module
('accounting', 'read', 'View accounting data', NOW()),
('accounting', 'write', 'Create/edit accounting entries', NOW()),
('accounting', 'delete', 'Delete accounting entries', NOW()),
('accounting', 'create', 'Create accounting entries', NOW()),
('accounting', 'update', 'Update accounting entries', NOW()),

-- Bookings module
('bookings', 'read', 'View bookings', NOW()),
('bookings', 'write', 'Create/edit bookings', NOW()),
('bookings', 'delete', 'Delete bookings', NOW()),
('bookings', 'create', 'Create bookings', NOW()),
('bookings', 'update', 'Update bookings', NOW()),

-- Properties module
('properties', 'read', 'View properties', NOW()),
('properties', 'write', 'Create/edit properties', NOW()),
('properties', 'delete', 'Delete properties', NOW()),
('properties', 'create', 'Create properties', NOW()),
('properties', 'update', 'Update properties', NOW()),

-- Inventory module
('inventory', 'read', 'View inventory', NOW()),
('inventory', 'write', 'Create/edit inventory', NOW()),
('inventory', 'delete', 'Delete inventory', NOW()),
('inventory', 'create', 'Create inventory', NOW()),
('inventory', 'update', 'Update inventory', NOW()),

-- Utilities module
('utilities', 'read', 'View utilities', NOW()),
('utilities', 'write', 'Create/edit utilities', NOW()),
('utilities', 'delete', 'Delete utilities', NOW()),
('utilities', 'create', 'Create utilities', NOW()),
('utilities', 'update', 'Update utilities', NOW()),

-- Settings module
('settings', 'read', 'View settings', NOW()),
('settings', 'write', 'Create/edit settings', NOW()),
('settings', 'delete', 'Delete settings', NOW()),
('settings', 'create', 'Create settings', NOW()),
('settings', 'update', 'Update settings', NOW()),

-- Dashboard module
('dashboard', 'read', 'View dashboard', NOW()),

-- Notifications module
('notifications', 'read', 'View notifications', NOW()),
('notifications', 'write', 'Create/edit notifications', NOW()),
('notifications', 'delete', 'Delete notifications', NOW()),

-- Users module
('users', 'read', 'View users', NOW()),
('users', 'write', 'Create/edit users', NOW()),
('users', 'delete', 'Delete users', NOW()),
('users', 'create', 'Create users', NOW()),
('users', 'update', 'Update users', NOW()),

-- Companies module
('companies', 'read', 'View companies', NOW()),
('companies', 'write', 'Create/edit companies', NOW()),
('companies', 'delete', 'Delete companies', NOW()),

-- Reports module
('reports', 'read', 'View reports', NOW()),
('reports', 'write', 'Create/edit reports', NOW()),

-- Modules module
('modules', 'read', 'View modules', NOW()),
('modules', 'write', 'Create/edit modules', NOW()),

-- Accounting Sub-modules
('accounts', 'read', 'View chart of accounts', NOW()),
('accounts', 'write', 'Create/edit accounts', NOW()),
('accounts', 'delete', 'Delete accounts', NOW()),
('accounts', 'create', 'Create accounts', NOW()),
('accounts', 'update', 'Update accounts', NOW()),

('cash', 'read', 'View cash management', NOW()),
('cash', 'write', 'Create/edit cash transactions', NOW()),
('cash', 'delete', 'Delete cash transactions', NOW()),
('cash', 'create', 'Create cash transactions', NOW()),
('cash', 'update', 'Update cash transactions', NOW()),

('receivables', 'read', 'View receivables', NOW()),
('receivables', 'write', 'Create/edit receivables', NOW()),
('receivables', 'delete', 'Delete receivables', NOW()),
('receivables', 'create', 'Create receivables', NOW()),
('receivables', 'update', 'Update receivables', NOW()),

('payables', 'read', 'View payables', NOW()),
('payables', 'write', 'Create/edit payables', NOW()),
('payables', 'delete', 'Delete payables', NOW()),
('payables', 'create', 'Create payables', NOW()),
('payables', 'update', 'Update payables', NOW()),

('ledger', 'read', 'View general ledger', NOW()),
('ledger', 'write', 'Create/edit ledger entries', NOW()),
('ledger', 'delete', 'Delete ledger entries', NOW()),
('ledger', 'create', 'Create ledger entries', NOW()),
('ledger', 'update', 'Update ledger entries', NOW()),

('estimates', 'read', 'View estimates', NOW()),
('estimates', 'write', 'Create/edit estimates', NOW()),
('estimates', 'delete', 'Delete estimates', NOW()),
('estimates', 'create', 'Create estimates', NOW()),
('estimates', 'update', 'Update estimates', NOW()),

-- POS Module
('pos', 'read', 'View POS', NOW()),
('pos', 'write', 'Create/edit POS transactions', NOW()),
('pos', 'delete', 'Delete POS transactions', NOW()),
('pos', 'create', 'Create POS transactions', NOW()),
('pos', 'update', 'Update POS transactions', NOW());

-- STEP 6: Assign ALL permissions to super_admin role
-- ============================================================================
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'super_admin'
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- STEP 7: Assign ALL permissions to admin role
-- ============================================================================
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'admin'
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- STEP 8: Assign ALL business module permissions to manager role
-- Includes: Accounting, Accounting sub-modules, POS, Bookings, Properties, Inventory, Utilities
-- Excludes: Tax module
-- ============================================================================
-- First, remove tax permissions from manager (if any exist)
DELETE rp FROM `erp_role_permissions` rp
JOIN `erp_roles` r ON rp.role_id = r.id
JOIN `erp_permissions` p ON rp.permission_id = p.id
WHERE r.role_code = 'manager'
AND p.module = 'tax';

-- Assign all business module permissions (excluding tax)
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'manager'
AND p.module IN ('accounting', 'accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates', 'pos', 'bookings', 'properties', 'inventory', 'utilities', 'settings', 'dashboard', 'notifications')
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- STEP 9: Assign permissions to staff role
-- Staff has read, update, and create permissions for: POS, Bookings, Inventory, Utilities
-- Staff has read permission for: Dashboard, Notifications
-- ============================================================================
-- Remove any existing staff permissions first (clean slate)
DELETE rp FROM `erp_role_permissions` rp
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'staff';

-- Grant read and update permissions for POS, Bookings, Inventory, Utilities, Dashboard, Notifications
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'staff'
AND p.module IN ('pos', 'bookings', 'inventory', 'utilities', 'dashboard', 'notifications')
AND p.permission IN ('read', 'update')
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- Grant create permissions for POS, Bookings, Inventory, Utilities
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'staff'
AND p.module IN ('pos', 'bookings', 'inventory', 'utilities')
AND p.permission = 'create'
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- STEP 10: Assign accounting permissions to accountant role
-- ============================================================================
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'accountant'
AND p.module = 'accounting'
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- ============================================================================
-- PART 2: BUSINESS MODULE TABLES
-- ============================================================================

-- SPACES TABLE (for property management and occupancy tracking)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_spaces` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `property_id` INT(11) NOT NULL,
    `space_number` VARCHAR(50) DEFAULT NULL,
    `space_name` VARCHAR(255) NOT NULL,
    `parent_space_id` INT(11) DEFAULT NULL COMMENT 'For sub-spaces',
    `category` ENUM('event_space','commercial','hospitality','storage','parking','residential','other') NOT NULL,
    `space_type` VARCHAR(100) DEFAULT NULL COMMENT 'hall,meeting_room,store,office,restaurant,etc.',
    `floor` VARCHAR(50) DEFAULT NULL,
    `area` DECIMAL(10,2) DEFAULT NULL COMMENT 'square meters',
    `capacity` INT(11) DEFAULT NULL COMMENT 'persons or vehicles',
    `configuration` VARCHAR(255) DEFAULT NULL COMMENT 'theater,classroom,banquet,etc.',
    `amenities` TEXT DEFAULT NULL COMMENT 'JSON array',
    `accessibility_features` TEXT DEFAULT NULL COMMENT 'JSON array',
    `operational_status` ENUM('active','under_maintenance','under_renovation','temporarily_closed','decommissioned') DEFAULT 'active',
    `operational_mode` ENUM('available_for_booking','leased','owner_operated','reserved','vacant') DEFAULT 'vacant',
    `status` ENUM('active','inactive') DEFAULT 'active',
    `is_bookable` TINYINT(1) DEFAULT 0,
    `facility_id` INT(11) DEFAULT NULL COMMENT 'Link to facilities table when bookable',
    `description` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_property_id` (`property_id`),
    KEY `idx_parent_space_id` (`parent_space_id`),
    KEY `idx_category` (`category`),
    KEY `idx_operational_status` (`operational_status`),
    KEY `idx_operational_mode` (`operational_mode`),
    KEY `idx_status` (`status`),
    KEY `idx_is_bookable` (`is_bookable`),
    KEY `idx_facility_id` (`facility_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STOCK LEVELS TABLE (for inventory management)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_stock_levels` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `item_id` INT(11) NOT NULL,
    `location_id` INT(11) DEFAULT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `reserved_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `available_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `unit_cost` DECIMAL(15,2) DEFAULT 0 COMMENT 'Average unit cost for inventory value calculation',
    `reorder_level` DECIMAL(10,2) DEFAULT 0,
    `reorder_point` DECIMAL(10,2) DEFAULT 0,
    `last_movement_date` DATETIME DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_item_location` (`item_id`, `location_id`),
    KEY `idx_item_id` (`item_id`),
    KEY `idx_location_id` (`location_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ITEMS TABLE (for inventory items master data)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `sku` VARCHAR(100) NOT NULL UNIQUE,
    `item_name` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) DEFAULT NULL COMMENT 'Alias for item_name',
    `description` TEXT DEFAULT NULL,
    `item_type` ENUM('inventory', 'non_inventory', 'service', 'fixed_asset') NOT NULL DEFAULT 'inventory',
    `category` VARCHAR(100) DEFAULT NULL,
    `subcategory` VARCHAR(100) DEFAULT NULL,
    `brand` VARCHAR(100) DEFAULT NULL,
    `manufacturer` VARCHAR(100) DEFAULT NULL,
    `model_number` VARCHAR(100) DEFAULT NULL,
    `barcode` VARCHAR(100) DEFAULT NULL UNIQUE,
    `qr_code` VARCHAR(100) DEFAULT NULL,
    `unit_of_measure` VARCHAR(20) DEFAULT 'each',
    `specifications` JSON DEFAULT NULL,
    `reorder_point` DECIMAL(10,2) DEFAULT 0,
    `reorder_level` DECIMAL(10,2) DEFAULT 0,
    `reorder_quantity` DECIMAL(10,2) DEFAULT 0,
    `safety_stock` DECIMAL(10,2) DEFAULT 0,
    `max_stock` DECIMAL(10,2) DEFAULT NULL,
    `lead_time_days` INT(11) DEFAULT 0,
    `item_status` ENUM('active', 'discontinued', 'out_of_stock') DEFAULT 'active',
    `status` ENUM('active','inactive') DEFAULT 'active',
    `cost_price` DECIMAL(15,2) DEFAULT 0,
    `average_cost` DECIMAL(15,2) DEFAULT 0,
    `selling_price` DECIMAL(15,2) DEFAULT 0,
    `retail_price` DECIMAL(15,2) DEFAULT 0,
    `wholesale_price` DECIMAL(15,2) DEFAULT 0,
    `costing_method` ENUM('fifo', 'lifo', 'weighted_average', 'standard', 'actual') DEFAULT 'weighted_average',
    `track_serial` TINYINT(1) DEFAULT 0,
    `track_batch` TINYINT(1) DEFAULT 0,
    `expiry_tracking` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_sku` (`sku`),
    KEY `idx_item_name` (`item_name`),
    KEY `idx_item_type` (`item_type`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_item_status` (`item_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LEASES TABLE (for property lease management)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_leases` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `lease_number` VARCHAR(50) NOT NULL UNIQUE,
    `space_id` INT(11) NOT NULL,
    `tenant_id` INT(11) NOT NULL,
    `lease_type` ENUM('commercial','residential','mixed') NOT NULL,
    `lease_term` ENUM('fixed_term','periodic','month_to_month') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL COMMENT 'NULL for periodic/month-to-month',
    `rent_amount` DECIMAL(15,2) NOT NULL,
    `payment_frequency` ENUM('monthly','weekly','quarterly','annually','daily') DEFAULT 'monthly',
    `rent_due_date` INT(2) DEFAULT 5 COMMENT 'day of month (1-31)',
    `security_deposit` DECIMAL(15,2) DEFAULT 0.00,
    `service_charge` DECIMAL(15,2) DEFAULT 0.00,
    `utility_responsibility` ENUM('tenant','landlord','shared') DEFAULT 'tenant',
    `maintenance_responsibility` ENUM('tenant','landlord','shared') DEFAULT 'landlord',
    `permitted_use` TEXT DEFAULT NULL,
    `subletting_allowed` TINYINT(1) DEFAULT 0,
    `rent_escalation_rate` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'percentage annual increase',
    `terms` TEXT DEFAULT NULL COMMENT 'JSON: additional terms',
    `status` ENUM('active','expired','terminated','pending_renewal') DEFAULT 'active',
    `renewal_notice_sent` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_lease_number` (`lease_number`),
    KEY `idx_space_id` (`space_id`),
    KEY `idx_tenant_id` (`tenant_id`),
    KEY `idx_status` (`status`),
    KEY `idx_end_date` (`end_date`),
    KEY `idx_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WORK ORDERS TABLE (for maintenance management)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_work_orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `work_order_number` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `property_id` INT(11) DEFAULT NULL,
    `space_id` INT(11) DEFAULT NULL,
    `work_type` ENUM('maintenance','repair','inspection','cleaning','upgrade','other') DEFAULT 'maintenance',
    `priority` ENUM('low','medium','high','urgent') DEFAULT 'medium',
    `status` ENUM('pending','assigned','in_progress','completed','cancelled','on_hold') DEFAULT 'pending',
    `assigned_to` INT(11) DEFAULT NULL COMMENT 'User ID',
    `requested_by` INT(11) DEFAULT NULL COMMENT 'User ID',
    `due_date` DATE DEFAULT NULL,
    `completed_date` DATE DEFAULT NULL,
    `estimated_cost` DECIMAL(15,2) DEFAULT 0,
    `actual_cost` DECIMAL(15,2) DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_work_order_number` (`work_order_number`),
    KEY `idx_property_id` (`property_id`),
    KEY `idx_space_id` (`space_id`),
    KEY `idx_status` (`status`),
    KEY `idx_work_type` (`work_type`),
    KEY `idx_priority` (`priority`),
    KEY `idx_due_date` (`due_date`),
    KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TAX DEADLINES TABLE (for tax compliance tracking)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_tax_deadlines` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tax_type` VARCHAR(50) NOT NULL,
    `deadline_date` DATE NOT NULL,
    `deadline_type` ENUM('filing', 'payment') NOT NULL,
    `period_covered` VARCHAR(20) DEFAULT NULL,
    `reminder_sent` TINYINT(1) DEFAULT 0,
    `reminder_date` DATE DEFAULT NULL,
    `status` ENUM('upcoming', 'due_today', 'overdue', 'completed') DEFAULT 'upcoming',
    `completed` TINYINT(1) DEFAULT 0,
    `completed_date` DATE DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_deadline_date` (`deadline_date`),
    KEY `idx_status` (`status`),
    KEY `idx_tax_type` (`tax_type`),
    KEY `idx_deadline_type` (`deadline_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- UTILITY BILLS TABLE (for utilities management)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_utility_bills` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `bill_number` VARCHAR(50) NOT NULL UNIQUE,
    `utility_type` ENUM('electricity','water','gas','internet','phone','sewage','trash','other') NOT NULL,
    `property_id` INT(11) DEFAULT NULL,
    `space_id` INT(11) DEFAULT NULL,
    `account_number` VARCHAR(100) DEFAULT NULL,
    `bill_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `paid_amount` DECIMAL(15,2) DEFAULT 0,
    `balance_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
    `payment_date` DATE DEFAULT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_bill_number` (`bill_number`),
    KEY `idx_utility_type` (`utility_type`),
    KEY `idx_property_id` (`property_id`),
    KEY `idx_space_id` (`space_id`),
    KEY `idx_status` (`status`),
    KEY `idx_bill_date` (`bill_date`),
    KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- VAT TRANSACTIONS TABLE (for VAT return calculations)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_vat_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(50) DEFAULT NULL,
    `date` DATE NOT NULL,
    `transaction_type` ENUM('sale', 'purchase', 'adjustment') NOT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'invoice, bill, payment, etc.',
    `reference_id` INT(11) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `vat_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `vat_rate` DECIMAL(5,2) DEFAULT 0,
    `net_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status` ENUM('draft', 'posted', 'cancelled') DEFAULT 'posted',
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_date` (`date`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_reference_type` (`reference_type`),
    KEY `idx_reference_id` (`reference_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check all permission tables exist
SELECT 'Permission Tables' as check_type, 
       COUNT(*) as tables_found
FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_permissions', 'erp_roles', 'erp_role_permissions');

-- Check all business module tables exist
SELECT 'Business Module Tables' as check_type, 
       COUNT(*) as tables_found
FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_spaces', 'erp_stock_levels', 'erp_items', 'erp_leases', 
                   'erp_work_orders', 'erp_tax_deadlines', 'erp_utility_bills');

-- Check role permissions count
SELECT r.role_code, r.role_name, COUNT(rp.id) as permission_count
FROM `erp_roles` r
LEFT JOIN `erp_role_permissions` rp ON r.id = rp.role_id
GROUP BY r.id, r.role_code, r.role_name
ORDER BY r.role_code;

-- List all manager permissions
SELECT p.module, p.permission, r.role_code
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
ORDER BY p.module, p.permission;

-- List all staff permissions
SELECT p.module, p.permission
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'staff'
ORDER BY p.module, p.permission;

-- ============================================================================
-- END OF COMPLETE SYSTEM MIGRATION
-- ============================================================================
-- This migration includes:
-- ✅ All permission system tables (erp_permissions, erp_roles, erp_role_permissions)
-- ✅ All roles (super_admin, admin, manager, staff, user, accountant)
-- ✅ All permissions for all modules including Accounting sub-modules and POS
-- ✅ Manager: All business modules + Accounting sub-modules + POS (Tax excluded)
-- ✅ Staff: POS, Bookings, Inventory, Utilities (read, update, create)
-- ✅ Accountant: All accounting permissions
-- ✅ All business module tables (spaces, stock_levels, items, leases, work_orders, tax_deadlines, utility_bills)
-- ============================================================================

