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
('staff_management', 'Staff Management', 'bi-people-fill', 3, 1),
('bookings', 'Bookings', 'bi-calendar', 4, 1),
('locations', 'Locations', 'bi-building', 5, 1), -- Locations (formerly Properties)
('properties', 'Properties', 'bi-building', 5, 1), -- Legacy compatibility
('inventory', 'Inventory', 'bi-box-seam', 6, 1),
('utilities', 'Utilities', 'bi-lightning', 7, 1),
('reports', 'Reports', 'bi-bar-chart', 8, 1),
('settings', 'Settings', 'bi-gear', 9, 1),
('users', 'User Management', 'bi-people', 10, 1),
('notifications', 'Notifications', 'bi-bell', 11, 1),
('pos', 'Point of Sale', 'bi-cart', 12, 1),
('tax', 'Tax Management', 'bi-file-text', 13, 1),
('entities', 'Entities', 'bi-diagram-3', 14, 1) -- Entities (formerly Companies)
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

-- Locations module (formerly Properties)
('locations', 'read', 'View locations', NOW()),
('locations', 'write', 'Create/edit locations', NOW()),
('locations', 'delete', 'Delete locations', NOW()),
('locations', 'create', 'Create locations', NOW()),
('locations', 'update', 'Update locations', NOW()),

-- Properties module (legacy compatibility)
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

-- Entities module (formerly Companies)
('entities', 'read', 'View entities', NOW()),
('entities', 'write', 'Create/edit entities', NOW()),
('entities', 'delete', 'Delete entities', NOW()),
('entities', 'create', 'Create entities', NOW()),
('entities', 'update', 'Update entities', NOW()),

-- Companies module (legacy compatibility)
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
AND p.module IN ('accounting', 'accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates', 'pos', 'bookings', 'locations', 'properties', 'inventory', 'utilities', 'settings', 'dashboard', 'notifications')
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

-- PROPERTIES TABLE (for location/property management)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_properties` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `property_code` VARCHAR(50) NOT NULL UNIQUE,
    `property_name` VARCHAR(255) NOT NULL,
    `property_type` ENUM('multi_purpose','standalone_building','land','other') DEFAULT 'multi_purpose',
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `gps_latitude` DECIMAL(10,8) DEFAULT NULL,
    `gps_longitude` DECIMAL(11,8) DEFAULT NULL,
    `land_area` DECIMAL(10,2) DEFAULT NULL COMMENT 'in square meters',
    `built_area` DECIMAL(10,2) DEFAULT NULL COMMENT 'in square meters',
    `year_built` INT(4) DEFAULT NULL,
    `year_acquired` INT(4) DEFAULT NULL,
    `property_value` DECIMAL(15,2) DEFAULT NULL,
    `manager_id` INT(11) DEFAULT NULL COMMENT 'user_id',
    `status` ENUM('operational','under_construction','under_renovation','closed') DEFAULT 'operational',
    `ownership_status` ENUM('owned','leased','joint_venture') DEFAULT 'owned',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_property_code` (`property_code`),
    KEY `idx_manager_id` (`manager_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- TAX TYPES TABLE (for tax configuration)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `erp_tax_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tax code (VAT, WHT, CIT, PAYE)',
    `name` VARCHAR(255) NOT NULL COMMENT 'Tax name',
    `rate` DECIMAL(10,2) DEFAULT 0 COMMENT 'Tax rate percentage',
    `calculation_method` ENUM('percentage', 'fixed', 'progressive') DEFAULT 'percentage',
    `authority` VARCHAR(100) DEFAULT 'FIRS' COMMENT 'Tax authority',
    `filing_frequency` ENUM('monthly', 'quarterly', 'annually') DEFAULT 'monthly',
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `tax_inclusive` TINYINT(1) DEFAULT 0 COMMENT 'Whether tax is included in price',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_code` (`code`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_authority` (`authority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default tax types
INSERT INTO `erp_tax_types` (`code`, `name`, `rate`, `calculation_method`, `authority`, `filing_frequency`, `description`, `is_active`, `tax_inclusive`, `created_at`) VALUES
('VAT', 'Value Added Tax', 7.5, 'percentage', 'FIRS', 'monthly', 'Value Added Tax (VAT) on goods and services', 1, 0, NOW()),
('WHT', 'Withholding Tax', 10.0, 'percentage', 'FIRS', 'monthly', 'Withholding Tax on payments', 1, 0, NOW()),
('CIT', 'Company Income Tax', 30.0, 'percentage', 'FIRS', 'annually', 'Company Income Tax on corporate profits', 1, 0, NOW()),
('PAYE', 'Pay As You Earn', 0, 'progressive', 'FIRS', 'monthly', 'Pay As You Earn - Progressive tax on employee income', 1, 0, NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

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
AND table_name IN ('erp_properties', 'erp_spaces', 'erp_stock_levels', 'erp_items', 'erp_leases', 
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
-- Ã¢Å“â€¦ All permission system tables (erp_permissions, erp_roles, erp_role_permissions)
-- Ã¢Å“â€¦ All roles (super_admin, admin, manager, staff, user, accountant)
-- Ã¢Å“â€¦ All permissions for all modules including Accounting sub-modules and POS
-- Ã¢Å“â€¦ Manager: All business modules + Accounting sub-modules + POS (Tax excluded)
-- Ã¢Å“â€¦ Staff: POS, Bookings, Inventory, Utilities (read, update, create)
-- Ã¢Å“â€¦ Accountant: All accounting permissions
-- Ã¢Å“â€¦ All business module tables (properties, spaces, stock_levels, items, leases, work_orders, tax_deadlines, utility_bills)
-- ============================================================================

- -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   C O M P L E T E   S Y S T E M   T A B L E S   F I X   -   R U N   D I R E C T L Y   I N   P H P M Y A D M I N 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   T h i s   m i g r a t i o n   c r e a t e s   A L L   m i s s i n g   t a b l e s   a n d   v i e w s   f o r   t h e   E R P   s y s t e m . 
 
 - -   R U N   T H I S   I N   P H P M Y A D M I N :   S e l e c t   y o u r   ' e r p s '   d a t a b a s e ,   g o   t o   S Q L   t a b ,   p a s t e ,   E x e c u t e 
 
 - -   I D E M P O T E N T   -   S a f e   t o   r u n   m u l t i p l e   t i m e s 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 S E T   F O R E I G N _ K E Y _ C H E C K S   =   0 ; 
 
 S E T   S Q L _ M O D E   =   ' N O _ A U T O _ V A L U E _ O N _ Z E R O ' ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   I N V E N T O R Y   T A B L E S 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   S t o c k   T r a n s a c t i o n s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ s t o c k _ t r a n s a c t i o n s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` t r a n s a c t i o n _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` t r a n s a c t i o n _ t y p e `   E N U M ( ' r e c e i v e ' , ' i s s u e ' , ' t r a n s f e r ' , ' a d j u s t ' , ' r e t u r n ' , ' s a l e ' )   N O T   N U L L , 
 
         ` i t e m _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` l o c a t i o n _ f r o m _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` l o c a t i o n _ t o _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` q u a n t i t y `   D E C I M A L ( 1 5 , 4 )   N O T   N U L L , 
 
         ` u n i t _ c o s t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` u n i t _ p r i c e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` r e f e r e n c e _ t y p e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` r e f e r e n c e _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` t r a n s a c t i o n _ d a t e `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         ` n o t e s `   T E X T   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ b y `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ t r a n s a c t i o n _ n u m b e r `   ( ` t r a n s a c t i o n _ n u m b e r ` ) , 
 
         K E Y   ` i d x _ i t e m _ i d `   ( ` i t e m _ i d ` ) , 
 
         K E Y   ` i d x _ t r a n s a c t i o n _ d a t e `   ( ` t r a n s a c t i o n _ d a t e ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   S t o c k   A d j u s t m e n t s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ s t o c k _ a d j u s t m e n t s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` a d j u s t m e n t _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` i t e m _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` l o c a t i o n _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` a d j u s t m e n t _ t y p e `   E N U M ( ' a d d i t i o n ' ,   ' s u b t r a c t i o n ' ,   ' r e s e t ' )   N O T   N U L L , 
 
         ` q u a n t i t y `   D E C I M A L ( 1 5 , 4 )   N O T   N U L L , 
 
         ` r e a s o n `   T E X T   D E F A U L T   N U L L , 
 
         ` a d j u s t e d _ b y `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` a d j u s t m e n t _ d a t e `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         ` s t a t u s `   E N U M ( ' d r a f t ' ,   ' p o s t e d ' ,   ' c a n c e l l e d ' )   D E F A U L T   ' d r a f t ' , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ a d j u s t m e n t _ n u m b e r `   ( ` a d j u s t m e n t _ n u m b e r ` ) , 
 
         K E Y   ` i d x _ i t e m _ i d `   ( ` i t e m _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   S t o c k   T a k e s   ( P h y s i c a l   I n v e n t o r y ) 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ s t o c k _ t a k e s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` s t o c k _ t a k e _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` l o c a t i o n _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` s t a r t _ d a t e `   D A T E T I M E   N O T   N U L L , 
 
         ` e n d _ d a t e `   D A T E T I M E   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' i n _ p r o g r e s s ' ,   ' c o m p l e t e d ' ,   ' c a n c e l l e d ' )   D E F A U L T   ' i n _ p r o g r e s s ' , 
 
         ` c o n d u c t e d _ b y `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` n o t e s `   T E X T   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ s t o c k _ t a k e _ n u m b e r `   ( ` s t o c k _ t a k e _ n u m b e r ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   S t o c k   T a k e   I t e m s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ s t o c k _ t a k e _ i t e m s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` s t o c k _ t a k e _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` i t e m _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` s y s t e m _ q u a n t i t y `   D E C I M A L ( 1 5 , 4 )   D E F A U L T   0 , 
 
         ` c o u n t e d _ q u a n t i t y `   D E C I M A L ( 1 5 , 4 )   D E F A U L T   N U L L , 
 
         ` v a r i a n c e `   D E C I M A L ( 1 5 , 4 )   D E F A U L T   0 , 
 
         ` n o t e s `   T E X T   D E F A U L T   N U L L , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         K E Y   ` i d x _ s t o c k _ t a k e _ i d `   ( ` s t o c k _ t a k e _ i d ` ) , 
 
         K E Y   ` i d x _ i t e m _ i d `   ( ` i t e m _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   S U P P L I E R / P U R C H A S I N G   T A B L E S 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   S u p p l i e r s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ s u p p l i e r s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` s u p p l i e r _ c o d e `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` s u p p l i e r _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L , 
 
         ` c o n t a c t _ p e r s o n `   V A R C H A R ( 2 5 5 )   D E F A U L T   N U L L , 
 
         ` e m a i l `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` p h o n e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` a d d r e s s `   T E X T   D E F A U L T   N U L L , 
 
         ` c i t y `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` s t a t e `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` c o u n t r y `   V A R C H A R ( 1 0 0 )   D E F A U L T   ' N i g e r i a ' , 
 
         ` t a x _ n u m b e r `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` p a y m e n t _ t e r m s `   I N T ( 1 1 )   D E F A U L T   0 , 
 
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' i n a c t i v e ' )   D E F A U L T   ' a c t i v e ' , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ s u p p l i e r _ c o d e `   ( ` s u p p l i e r _ c o d e ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   P u r c h a s e   O r d e r s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ p u r c h a s e _ o r d e r s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` p o _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` s u p p l i e r _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` o r d e r _ d a t e `   D A T E   N O T   N U L L , 
 
         ` e x p e c t e d _ d a t e `   D A T E   D E F A U L T   N U L L , 
 
         ` s u b t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t a x _ t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t o t a l _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` s t a t u s `   E N U M ( ' d r a f t ' , ' s e n t ' , ' p a r t i a l l y _ r e c e i v e d ' , ' r e c e i v e d ' , ' c a n c e l l e d ' , ' c l o s e d ' )   D E F A U L T   ' d r a f t ' , 
 
         ` n o t e s `   T E X T   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ b y `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ p o _ n u m b e r `   ( ` p o _ n u m b e r ` ) , 
 
         K E Y   ` i d x _ s u p p l i e r _ i d `   ( ` s u p p l i e r _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   P u r c h a s e   O r d e r   I t e m s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ p u r c h a s e _ o r d e r _ i t e m s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` p o _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` i t e m _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` d e s c r i p t i o n `   T E X T   D E F A U L T   N U L L , 
 
         ` q u a n t i t y `   D E C I M A L ( 1 5 , 4 )   N O T   N U L L , 
 
         ` r e c e i v e d _ q u a n t i t y `   D E C I M A L ( 1 5 , 4 )   D E F A U L T   0 , 
 
         ` u n i t _ p r i c e `   D E C I M A L ( 1 5 , 2 )   N O T   N U L L , 
 
         ` t a x _ r a t e `   D E C I M A L ( 5 , 2 )   D E F A U L T   0 , 
 
         ` t a x _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` l i n e _ t o t a l `   D E C I M A L ( 1 5 , 2 )   N O T   N U L L , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         K E Y   ` i d x _ p o _ i d `   ( ` p o _ i d ` ) , 
 
         K E Y   ` i d x _ i t e m _ i d `   ( ` i t e m _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   G o o d s   R e c e i p t s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ g o o d s _ r e c e i p t s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` g r n _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` p o _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` s u p p l i e r _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` r e c e i v e d _ d a t e `   D A T E   N O T   N U L L , 
 
         ` l o c a t i o n _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' d r a f t ' , ' p o s t e d ' , ' c a n c e l l e d ' )   D E F A U L T   ' d r a f t ' , 
 
         ` n o t e s `   T E X T   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ b y `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ g r n _ n u m b e r `   ( ` g r n _ n u m b e r ` ) , 
 
         K E Y   ` i d x _ s u p p l i e r _ i d `   ( ` s u p p l i e r _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   G o o d s   R e c e i p t   I t e m s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ g o o d s _ r e c e i p t _ i t e m s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` g r n _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` i t e m _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` d e s c r i p t i o n `   T E X T   D E F A U L T   N U L L , 
 
         ` r e c e i v e d _ q u a n t i t y `   D E C I M A L ( 1 5 , 4 )   N O T   N U L L , 
 
         ` u n i t _ c o s t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` l i n e _ t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         K E Y   ` i d x _ g r n _ i d `   ( ` g r n _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   B O O K I N G   T A B L E S 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   B o o k i n g   A d d - o n s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ b o o k i n g _ a d d o n s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` b o o k i n g _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` a d d o n _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` a d d o n _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L , 
 
         ` q u a n t i t y `   D E C I M A L ( 1 0 , 2 )   D E F A U L T   1 , 
 
         ` u n i t _ p r i c e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t o t a l _ p r i c e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         K E Y   ` i d x _ b o o k i n g _ i d `   ( ` b o o k i n g _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   P R O P E R T Y / L O C A T I O N   T A B L E S   &   V I E W S 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   L o c a t i o n s   V i e w   ( p o i n t s   t o   p r o p e r t i e s   f o r   c o m p a t i b i l i t y ) 
 
 D R O P   V I E W   I F   E X I S T S   ` e r p _ l o c a t i o n s ` ; 
 
 C R E A T E   V I E W   ` e r p _ l o c a t i o n s `   A S   
 
 S E L E C T   * ,   i d   A S   l o c a t i o n _ i d ,   p r o p e r t y _ n a m e   A S   l o c a t i o n _ n a m e   F R O M   ` e r p _ p r o p e r t i e s ` ; 
 
 
 
 - -   T e n a n t s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ t e n a n t s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` t e n a n t _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L , 
 
         ` t e n a n t _ t y p e `   E N U M ( ' i n d i v i d u a l ' ,   ' c o r p o r a t e ' )   D E F A U L T   ' i n d i v i d u a l ' , 
 
         ` e m a i l `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` p h o n e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` i d e n t i f i c a t i o n _ t y p e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` i d e n t i f i c a t i o n _ n u m b e r `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` a d d r e s s `   T E X T   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' a c t i v e ' ,   ' i n a c t i v e ' )   D E F A U L T   ' a c t i v e ' , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   R e n t   I n v o i c e s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ r e n t _ i n v o i c e s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` i n v o i c e _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` l e a s e _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` t e n a n t _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` p r o p e r t y _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` s p a c e _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` p e r i o d _ s t a r t `   D A T E   N O T   N U L L , 
 
         ` p e r i o d _ e n d `   D A T E   N O T   N U L L , 
 
         ` d u e _ d a t e `   D A T E   N O T   N U L L , 
 
         ` s u b t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t a x _ t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t o t a l _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` b a l a n c e _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` s t a t u s `   E N U M ( ' d r a f t ' ,   ' s e n t ' ,   ' p a r t i a l ' ,   ' p a i d ' ,   ' c a n c e l l e d ' ,   ' o v e r d u e ' )   D E F A U L T   ' d r a f t ' , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ i n v o i c e _ n u m b e r `   ( ` i n v o i c e _ n u m b e r ` ) , 
 
         K E Y   ` i d x _ l e a s e _ i d `   ( ` l e a s e _ i d ` ) , 
 
         K E Y   ` i d x _ t e n a n t _ i d `   ( ` t e n a n t _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   U T I L I T I E S   T A B L E S 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   U t i l i t y   M e t e r s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ m e t e r s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` m e t e r _ n u m b e r `   V A R C H A R ( 1 0 0 )   N O T   N U L L , 
 
         ` u t i l i t y _ t y p e _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` p r o p e r t y _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` s p a c e _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` t e n a n t _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` m e t e r _ l o c a t i o n `   V A R C H A R ( 2 5 5 )   D E F A U L T   N U L L , 
 
         ` i n i t i a l _ r e a d i n g `   D E C I M A L ( 1 5 , 4 )   D E F A U L T   0 , 
 
         ` c u r r e n t _ r e a d i n g `   D E C I M A L ( 1 5 , 4 )   D E F A U L T   0 , 
 
         ` l a s t _ r e a d i n g _ d a t e `   D A T E T I M E   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' i n a c t i v e ' , ' m a i n t e n a n c e ' )   D E F A U L T   ' a c t i v e ' , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ m e t e r _ n u m b e r `   ( ` m e t e r _ n u m b e r ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   M e t e r   R e a d i n g s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ m e t e r _ r e a d i n g s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` m e t e r _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` r e a d i n g _ d a t e `   D A T E T I M E   N O T   N U L L , 
 
         ` r e a d i n g _ v a l u e `   D E C I M A L ( 1 5 , 4 )   N O T   N U L L , 
 
         ` c o n s u m p t i o n `   D E C I M A L ( 1 5 , 4 )   D E F A U L T   0 , 
 
         ` r e a d i n g _ t y p e `   E N U M ( ' a c t u a l ' , ' e s t i m a t e d ' , ' i n i t i a l ' )   D E F A U L T   ' a c t u a l ' , 
 
         ` r e c o r d e d _ b y `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` i m a g e _ p a t h `   V A R C H A R ( 2 5 5 )   D E F A U L T   N U L L , 
 
         ` n o t e s `   T E X T   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         K E Y   ` i d x _ m e t e r _ i d `   ( ` m e t e r _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   U t i l i t y   P r o v i d e r s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ u t i l i t y _ p r o v i d e r s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` p r o v i d e r _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L , 
 
         ` u t i l i t y _ t y p e _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` c o n t a c t _ p e r s o n `   V A R C H A R ( 2 5 5 )   D E F A U L T   N U L L , 
 
         ` e m a i l `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` p h o n e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` a d d r e s s `   T E X T   D E F A U L T   N U L L , 
 
         ` a c c o u n t _ n u m b e r `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' i n a c t i v e ' )   D E F A U L T   ' a c t i v e ' , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   U t i l i t y   T a r i f f s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ t a r i f f s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` u t i l i t y _ t y p e _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` t a r i f f _ n a m e `   V A R C H A R ( 1 0 0 )   N O T   N U L L , 
 
         ` r a t e _ p e r _ u n i t `   D E C I M A L ( 1 5 , 4 )   N O T   N U L L , 
 
         ` f i x e d _ c h a r g e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` m i n _ c h a r g e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` e f f e c t i v e _ f r o m `   D A T E   D E F A U L T   N U L L , 
 
         ` e f f e c t i v e _ t o `   D A T E   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' e x p i r e d ' , ' p e n d i n g ' )   D E F A U L T   ' a c t i v e ' , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   U t i l i t y   P a y m e n t s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ u t i l i t y _ p a y m e n t s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` b i l l _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` p a y m e n t _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` p a y m e n t _ d a t e `   D A T E   N O T   N U L L , 
 
         ` a m o u n t `   D E C I M A L ( 1 5 , 2 )   N O T   N U L L , 
 
         ` p a y m e n t _ m e t h o d `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` r e f e r e n c e _ n u m b e r `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` n o t e s `   T E X T   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ p a y m e n t _ n u m b e r `   ( ` p a y m e n t _ n u m b e r ` ) , 
 
         K E Y   ` i d x _ b i l l _ i d `   ( ` b i l l _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4 ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   P A Y R O L L   T A B L E S 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   P a y r o l l   R u n s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ p a y r o l l _ r u n s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` p e r i o d _ n a m e `   V A R C H A R ( 1 0 0 )   N O T   N U L L , 
 
         ` p e r i o d _ s t a r t `   D A T E   N O T   N U L L , 
 
         ` p e r i o d _ e n d `   D A T E   N O T   N U L L , 
 
         ` r u n _ d a t e `   D A T E   N O T   N U L L , 
 
         ` t o t a l _ g r o s s `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t o t a l _ d e d u c t i o n s `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t o t a l _ n e t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` s t a t u s `   E N U M ( ' d r a f t ' ,   ' p r o c e s s e d ' ,   ' a p p r o v e d ' ,   ' p a i d ' ,   ' c a n c e l l e d ' )   D E F A U L T   ' d r a f t ' , 
 
         ` c r e a t e d _ b y `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4 ; 
 
 
 
 - -   P a y s l i p s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ p a y s l i p s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` p a y r o l l _ r u n _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` e m p l o y e e _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` b a s i c _ s a l a r y `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` a l l o w a n c e s `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` b o n u s `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` g r o s s _ p a y `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t a x _ d e d u c t i o n `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` p e n s i o n _ d e d u c t i o n `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` o t h e r _ d e d u c t i o n s `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` t o t a l _ d e d u c t i o n s `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` n e t _ p a y `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` p a y m e n t _ s t a t u s `   E N U M ( ' u n p a i d ' ,   ' p a i d ' )   D E F A U L T   ' u n p a i d ' , 
 
         ` p a y m e n t _ d a t e `   D A T E   D E F A U L T   N U L L , 
 
         ` p a y m e n t _ m e t h o d `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         K E Y   ` i d x _ p a y r o l l _ r u n _ i d `   ( ` p a y r o l l _ r u n _ i d ` ) , 
 
         K E Y   ` i d x _ e m p l o y e e _ i d `   ( ` e m p l o y e e _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4 ; 
 
 
 
 - -   P A Y E   D e d u c t i o n s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ p a y e _ d e d u c t i o n s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` p a y s l i p _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` e m p l o y e e _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` p e r i o d `   V A R C H A R ( 7 )   N O T   N U L L , 
 
         ` t a x a b l e _ i n c o m e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` p a y e _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` p o s t e d _ t o _ t a x `   T I N Y I N T ( 1 )   D E F A U L T   0 , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         K E Y   ` i d x _ p a y s l i p _ i d `   ( ` p a y s l i p _ i d ` ) , 
 
         K E Y   ` i d x _ e m p l o y e e _ i d `   ( ` e m p l o y e e _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4 ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   T A X   T A B L E S 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   T a x   P a y m e n t s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ t a x _ p a y m e n t s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` t a x _ t y p e _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` p a y m e n t _ d a t e `   D A T E   N O T   N U L L , 
 
         ` p e r i o d _ s t a r t `   D A T E   D E F A U L T   N U L L , 
 
         ` p e r i o d _ e n d `   D A T E   D E F A U L T   N U L L , 
 
         ` a m o u n t `   D E C I M A L ( 1 5 , 2 )   N O T   N U L L , 
 
         ` r e f e r e n c e _ n u m b e r `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` p a y m e n t _ m e t h o d `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' d r a f t ' , ' p o s t e d ' , ' c a n c e l l e d ' )   D E F A U L T   ' p o s t e d ' , 
 
         ` n o t e s `   T E X T   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         K E Y   ` i d x _ t a x _ t y p e _ i d `   ( ` t a x _ t y p e _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4 ; 
 
 
 
 - -   W H T   C e r t i f i c a t e s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ w h t _ c e r t i f i c a t e s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` c e r t i f i c a t e _ n u m b e r `   V A R C H A R ( 1 0 0 )   N O T   N U L L , 
 
         ` v e n d o r _ i d `   I N T ( 1 1 )   N O T   N U L L , 
 
         ` b i l l _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` i s s u e _ d a t e `   D A T E   N O T   N U L L , 
 
         ` a m o u n t _ w i t h h e l d `   D E C I M A L ( 1 5 , 2 )   N O T   N U L L , 
 
         ` f i l e _ p a t h `   V A R C H A R ( 2 5 5 )   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' p e n d i n g ' , ' r e c e i v e d ' , ' u s e d ' )   D E F A U L T   ' p e n d i n g ' , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ c e r t _ n u m b e r `   ( ` c e r t i f i c a t e _ n u m b e r ` ) , 
 
         K E Y   ` i d x _ v e n d o r _ i d `   ( ` v e n d o r _ i d ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4 ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   F I X E D   A S S E T S   T A B L E S 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   F i x e d   A s s e t s 
 
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ f i x e d _ a s s e t s `   ( 
 
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T , 
 
         ` a s s e t _ c o d e `   V A R C H A R ( 5 0 )   N O T   N U L L , 
 
         ` a s s e t _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L , 
 
         ` c a t e g o r y _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` p r o p e r t y _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` s p a c e _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L , 
 
         ` p u r c h a s e _ d a t e `   D A T E   D E F A U L T   N U L L , 
 
         ` p u r c h a s e _ v a l u e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` c u r r e n t _ v a l u e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` d e p r e c i a t i o n _ m e t h o d `   E N U M ( ' s t r a i g h t _ l i n e ' , ' d e c l i n i n g _ b a l a n c e ' , ' n o n e ' )   D E F A U L T   ' s t r a i g h t _ l i n e ' , 
 
         ` u s e f u l _ l i f e _ y e a r s `   I N T ( 1 1 )   D E F A U L T   5 , 
 
         ` r e s i d u a l _ v a l u e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 , 
 
         ` s e r i a l _ n u m b e r `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` m a n u f a c t u r e r `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L , 
 
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' d i s p o s e d ' , ' u n d e r _ r e p a i r ' , ' l o s t ' )   D E F A U L T   ' a c t i v e ' , 
 
         ` d i s p o s a l _ d a t e `   D A T E   D E F A U L T   N U L L , 
 
         ` d i s p o s a l _ v a l u e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   N U L L , 
 
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P , 
 
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P , 
 
         P R I M A R Y   K E Y   ( ` i d ` ) , 
 
         U N I Q U E   K E Y   ` u n i q u e _ a s s e t _ c o d e `   ( ` a s s e t _ c o d e ` ) 
 
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   R E C O R D   M I G R A T I O N   E X E C U T I O N 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
 
 - -   M a r k   m i g r a t i o n s   a s   e x e c u t e d   ( i f   m i g r a t i o n s   t a b l e   e x i s t s ) 
 
 I N S E R T   I G N O R E   I N T O   ` e r p _ m i g r a t i o n s `   ( ` m i g r a t i o n ` ,   ` b a t c h ` ,   ` e x e c u t e d _ a t ` )   V A L U E S 
 
 ( ' 0 1 8 _ c o m p r e h e n s i v e _ b u s i n e s s _ f i x . s q l ' ,   9 9 ,   N O W ( ) ) , 
 
 ( ' 0 1 9 _ m a s s i v e _ s y s t e m _ f i x . s q l ' ,   9 9 ,   N O W ( ) ) , 
 
 ( ' 0 2 0 _ c o m p l e t e _ s y s t e m _ t a b l e s . s q l ' ,   9 9 ,   N O W ( ) ) ; 
 
 
 
 S E T   F O R E I G N _ K E Y _ C H E C K S   =   1 ; 
 
 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 - -   D O N E !   A l l   t a b l e s   c r e a t e d   s u c c e s s f u l l y . 
 
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = 
 
 
-- ============================================================================
-- COMPLETE SYSTEM TABLES FIX - RUN DIRECTLY IN PHPMYADMIN
-- ============================================================================
-- This migration creates ALL missing tables and views for the ERP system.
-- RUN THIS IN PHPMYADMIN: Select your 'erps' database, go to SQL tab, paste, Execute
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================================================
-- INVENTORY TABLES
-- ============================================================================

-- Stock Transactions
CREATE TABLE IF NOT EXISTS `erp_stock_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(50) NOT NULL,
    `transaction_type` ENUM('receive','issue','transfer','adjust','return','sale') NOT NULL,
    `item_id` INT(11) NOT NULL,
    `location_from_id` INT(11) DEFAULT NULL,
    `location_to_id` INT(11) DEFAULT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,2) DEFAULT 0,
    `unit_price` DECIMAL(15,2) DEFAULT 0,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT(11) DEFAULT NULL,
    `transaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_transaction_number` (`transaction_number`),
    KEY `idx_item_id` (`item_id`),
    KEY `idx_transaction_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Adjustments
CREATE TABLE IF NOT EXISTS `erp_stock_adjustments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `adjustment_number` VARCHAR(50) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `location_id` INT(11) DEFAULT NULL,
    `adjustment_type` ENUM('addition', 'subtraction', 'reset') NOT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `adjusted_by` INT(11) DEFAULT NULL,
    `adjustment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('draft', 'posted', 'cancelled') DEFAULT 'draft',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_adjustment_number` (`adjustment_number`),
    KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Takes (Physical Inventory)
CREATE TABLE IF NOT EXISTS `erp_stock_takes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `stock_take_number` VARCHAR(50) NOT NULL,
    `location_id` INT(11) DEFAULT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `status` ENUM('in_progress', 'completed', 'cancelled') DEFAULT 'in_progress',
    `conducted_by` INT(11) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_stock_take_number` (`stock_take_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Take Items
CREATE TABLE IF NOT EXISTS `erp_stock_take_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `stock_take_id` INT(11) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `system_quantity` DECIMAL(15,4) DEFAULT 0,
    `counted_quantity` DECIMAL(15,4) DEFAULT NULL,
    `variance` DECIMAL(15,4) DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_stock_take_id` (`stock_take_id`),
    KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUPPLIER/PURCHASING TABLES
-- ============================================================================

-- Suppliers
CREATE TABLE IF NOT EXISTS `erp_suppliers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `supplier_code` VARCHAR(50) NOT NULL,
    `supplier_name` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'Nigeria',
    `tax_number` VARCHAR(50) DEFAULT NULL,
    `payment_terms` INT(11) DEFAULT 0,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_supplier_code` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Orders
CREATE TABLE IF NOT EXISTS `erp_purchase_orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `po_number` VARCHAR(50) NOT NULL,
    `supplier_id` INT(11) NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_date` DATE DEFAULT NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `tax_total` DECIMAL(15,2) DEFAULT 0,
    `total_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft','sent','partially_received','received','cancelled','closed') DEFAULT 'draft',
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_po_number` (`po_number`),
    KEY `idx_supplier_id` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Order Items
CREATE TABLE IF NOT EXISTS `erp_purchase_order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `po_id` INT(11) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `received_quantity` DECIMAL(15,4) DEFAULT 0,
    `unit_price` DECIMAL(15,2) NOT NULL,
    `tax_rate` DECIMAL(5,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `line_total` DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_po_id` (`po_id`),
    KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goods Receipts
CREATE TABLE IF NOT EXISTS `erp_goods_receipts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `grn_number` VARCHAR(50) NOT NULL,
    `po_id` INT(11) DEFAULT NULL,
    `supplier_id` INT(11) NOT NULL,
    `received_date` DATE NOT NULL,
    `location_id` INT(11) DEFAULT NULL,
    `status` ENUM('draft','posted','cancelled') DEFAULT 'draft',
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_grn_number` (`grn_number`),
    KEY `idx_supplier_id` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goods Receipt Items
CREATE TABLE IF NOT EXISTS `erp_goods_receipt_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `grn_id` INT(11) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `received_quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,2) DEFAULT 0,
    `line_total` DECIMAL(15,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_grn_id` (`grn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- BOOKING TABLES
-- ============================================================================

-- Booking Add-ons
CREATE TABLE IF NOT EXISTS `erp_booking_addons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `addon_id` INT(11) NOT NULL,
    `addon_name` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(10,2) DEFAULT 1,
    `unit_price` DECIMAL(15,2) DEFAULT 0,
    `total_price` DECIMAL(15,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_booking_id` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PROPERTY/LOCATION TABLES & VIEWS
-- ============================================================================

-- Locations View (points to properties for compatibility)
DROP VIEW IF EXISTS `erp_locations`;
CREATE VIEW `erp_locations` AS 
SELECT *, id AS location_id, property_name AS location_name FROM `erp_properties`;

-- Tenants
CREATE TABLE IF NOT EXISTS `erp_tenants` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tenant_name` VARCHAR(255) NOT NULL,
    `tenant_type` ENUM('individual', 'corporate') DEFAULT 'individual',
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `identification_type` VARCHAR(50) DEFAULT NULL,
    `identification_number` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rent Invoices
CREATE TABLE IF NOT EXISTS `erp_rent_invoices` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL,
    `lease_id` INT(11) NOT NULL,
    `tenant_id` INT(11) NOT NULL,
    `property_id` INT(11) NOT NULL,
    `space_id` INT(11) DEFAULT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `tax_total` DECIMAL(15,2) DEFAULT 0,
    `total_amount` DECIMAL(15,2) DEFAULT 0,
    `balance_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'sent', 'partial', 'paid', 'cancelled', 'overdue') DEFAULT 'draft',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_invoice_number` (`invoice_number`),
    KEY `idx_lease_id` (`lease_id`),
    KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- UTILITIES TABLES
-- ============================================================================

-- Utility Meters
CREATE TABLE IF NOT EXISTS `erp_meters` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `meter_number` VARCHAR(100) NOT NULL,
    `utility_type_id` INT(11) NOT NULL,
    `property_id` INT(11) DEFAULT NULL,
    `space_id` INT(11) DEFAULT NULL,
    `tenant_id` INT(11) DEFAULT NULL,
    `meter_location` VARCHAR(255) DEFAULT NULL,
    `initial_reading` DECIMAL(15,4) DEFAULT 0,
    `current_reading` DECIMAL(15,4) DEFAULT 0,
    `last_reading_date` DATETIME DEFAULT NULL,
    `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_meter_number` (`meter_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meter Readings
CREATE TABLE IF NOT EXISTS `erp_meter_readings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `meter_id` INT(11) NOT NULL,
    `reading_date` DATETIME NOT NULL,
    `reading_value` DECIMAL(15,4) NOT NULL,
    `consumption` DECIMAL(15,4) DEFAULT 0,
    `reading_type` ENUM('actual','estimated','initial') DEFAULT 'actual',
    `recorded_by` INT(11) DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_meter_id` (`meter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utility Providers
CREATE TABLE IF NOT EXISTS `erp_utility_providers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `provider_name` VARCHAR(255) NOT NULL,
    `utility_type_id` INT(11) NOT NULL,
    `contact_person` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `account_number` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utility Tariffs
CREATE TABLE IF NOT EXISTS `erp_tariffs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `utility_type_id` INT(11) NOT NULL,
    `tariff_name` VARCHAR(100) NOT NULL,
    `rate_per_unit` DECIMAL(15,4) NOT NULL,
    `fixed_charge` DECIMAL(15,2) DEFAULT 0,
    `min_charge` DECIMAL(15,2) DEFAULT 0,
    `effective_from` DATE DEFAULT NULL,
    `effective_to` DATE DEFAULT NULL,
    `status` ENUM('active','expired','pending') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utility Payments
CREATE TABLE IF NOT EXISTS `erp_utility_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `bill_id` INT(11) NOT NULL,
    `payment_number` VARCHAR(50) NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_payment_number` (`payment_number`),
    KEY `idx_bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PAYROLL TABLES
-- ============================================================================

-- Payroll Runs
CREATE TABLE IF NOT EXISTS `erp_payroll_runs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `period_name` VARCHAR(100) NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `run_date` DATE NOT NULL,
    `total_gross` DECIMAL(15,2) DEFAULT 0,
    `total_deductions` DECIMAL(15,2) DEFAULT 0,
    `total_net` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'processed', 'approved', 'paid', 'cancelled') DEFAULT 'draft',
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payslips
CREATE TABLE IF NOT EXISTS `erp_payslips` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `payroll_run_id` INT(11) NOT NULL,
    `employee_id` INT(11) NOT NULL,
    `basic_salary` DECIMAL(15,2) DEFAULT 0,
    `allowances` DECIMAL(15,2) DEFAULT 0,
    `bonus` DECIMAL(15,2) DEFAULT 0,
    `gross_pay` DECIMAL(15,2) DEFAULT 0,
    `tax_deduction` DECIMAL(15,2) DEFAULT 0,
    `pension_deduction` DECIMAL(15,2) DEFAULT 0,
    `other_deductions` DECIMAL(15,2) DEFAULT 0,
    `total_deductions` DECIMAL(15,2) DEFAULT 0,
    `net_pay` DECIMAL(15,2) DEFAULT 0,
    `payment_status` ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    `payment_date` DATE DEFAULT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payroll_run_id` (`payroll_run_id`),
    KEY `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAYE Deductions
CREATE TABLE IF NOT EXISTS `erp_paye_deductions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `payslip_id` INT(11) NOT NULL,
    `employee_id` INT(11) NOT NULL,
    `period` VARCHAR(7) NOT NULL,
    `taxable_income` DECIMAL(15,2) DEFAULT 0,
    `paye_amount` DECIMAL(15,2) DEFAULT 0,
    `posted_to_tax` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payslip_id` (`payslip_id`),
    KEY `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TAX TABLES
-- ============================================================================

-- Tax Payments
CREATE TABLE IF NOT EXISTS `erp_tax_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tax_type_id` INT(11) NOT NULL,
    `payment_date` DATE NOT NULL,
    `period_start` DATE DEFAULT NULL,
    `period_end` DATE DEFAULT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('draft','posted','cancelled') DEFAULT 'posted',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tax_type_id` (`tax_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WHT Certificates
CREATE TABLE IF NOT EXISTS `erp_wht_certificates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `certificate_number` VARCHAR(100) NOT NULL,
    `vendor_id` INT(11) NOT NULL,
    `bill_id` INT(11) DEFAULT NULL,
    `issue_date` DATE NOT NULL,
    `amount_withheld` DECIMAL(15,2) NOT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','received','used') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_cert_number` (`certificate_number`),
    KEY `idx_vendor_id` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- FIXED ASSETS TABLES
-- ============================================================================

-- Fixed Assets
CREATE TABLE IF NOT EXISTS `erp_fixed_assets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asset_code` VARCHAR(50) NOT NULL,
    `asset_name` VARCHAR(255) NOT NULL,
    `category_id` INT(11) DEFAULT NULL,
    `property_id` INT(11) DEFAULT NULL,
    `space_id` INT(11) DEFAULT NULL,
    `purchase_date` DATE DEFAULT NULL,
    `purchase_value` DECIMAL(15,2) DEFAULT 0,
    `current_value` DECIMAL(15,2) DEFAULT 0,
    `depreciation_method` ENUM('straight_line','declining_balance','none') DEFAULT 'straight_line',
    `useful_life_years` INT(11) DEFAULT 5,
    `residual_value` DECIMAL(15,2) DEFAULT 0,
    `serial_number` VARCHAR(100) DEFAULT NULL,
    `manufacturer` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active','disposed','under_repair','lost') DEFAULT 'active',
    `disposal_date` DATE DEFAULT NULL,
    `disposal_value` DECIMAL(15,2) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_asset_code` (`asset_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- RECORD MIGRATION EXECUTION
-- ============================================================================

-- Mark migrations as executed (if migrations table exists)
INSERT IGNORE INTO `erp_migrations` (`migration`, `batch`, `executed_at`) VALUES
('018_comprehensive_business_fix.sql', 99, NOW()),
('019_massive_system_fix.sql', 99, NOW()),
('020_complete_system_tables.sql', 99, NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- DONE! All tables created successfully.
-- ============================================================================
- -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =  
 - -   F I X   M I S S I N G   P R I M A R Y   T A B L E S  
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =  
 - -   C r e a t e s   t a b l e s   t h a t   w e r e   m i s s i n g   f r o m   p r e v i o u s   m i g r a t i o n s  
 - -   U p d a t e d   t o   i n c l u d e   e r p _ c o m p a n i e s   a n d   c o r r e c t   c o l u m n   n a m e s  
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =  
  
 S E T   F O R E I G N _ K E Y _ C H E C K S   =   0 ;  
  
 - -   1 .   A C C O U N T S   T A B L E   ( C h a r t   o f   A c c o u n t s )  
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ a c c o u n t s `   (  
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
         ` a c c o u n t _ c o d e `   V A R C H A R ( 5 0 )   N O T   N U L L ,  
         ` a c c o u n t _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L ,  
         ` a c c o u n t _ t y p e `   E N U M ( ' a s s e t ' , ' l i a b i l i t y ' , ' e q u i t y ' , ' i n c o m e ' , ' e x p e n s e ' )   N O T   N U L L ,  
         ` a c c o u n t _ c a t e g o r y `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L ,  
         ` p a r e n t _ a c c o u n t _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L ,  
         ` d e s c r i p t i o n `   T E X T   D E F A U L T   N U L L ,  
         ` i s _ s y s t e m _ a c c o u n t `   T I N Y I N T ( 1 )   D E F A U L T   0 ,  
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P ,  
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P ,  
         P R I M A R Y   K E Y   ( ` i d ` ) ,  
         U N I Q U E   K E Y   ` u n i q u e _ a c c o u n t _ c o d e `   ( ` a c c o u n t _ c o d e ` ) ,  
         K E Y   ` i d x _ a c c o u n t _ t y p e `   ( ` a c c o u n t _ t y p e ` ) ,  
         K E Y   ` i d x _ p a r e n t `   ( ` p a r e n t _ a c c o u n t _ i d ` )  
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ;  
  
 - -   2 .   F A C I L I T I E S   T A B L E   ( R e s o u r c e s )  
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ f a c i l i t i e s `   (  
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
         ` f a c i l i t y _ c o d e `   V A R C H A R ( 5 0 )   N O T   N U L L ,  
         ` f a c i l i t y _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L ,  
         ` d e s c r i p t i o n `   T E X T   D E F A U L T   N U L L ,  
         ` c a p a c i t y `   I N T ( 1 1 )   D E F A U L T   0 ,  
         ` h o u r l y _ r a t e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` d a i l y _ r a t e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` h a l f _ d a y _ r a t e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` w e e k l y _ r a t e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` m e m b e r _ r a t e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` r e s o u r c e _ t y p e `   V A R C H A R ( 5 0 )   D E F A U L T   ' f a c i l i t y ' ,  
         ` c a t e g o r y `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L ,  
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' i n a c t i v e ' , ' m a i n t e n a n c e ' )   D E F A U L T   ' a c t i v e ' ,  
         ` i s _ b o o k a b l e `   T I N Y I N T ( 1 )   D E F A U L T   1 ,  
         ` p r i c i n g _ r u l e s `   J S O N   D E F A U L T   N U L L ,  
         ` c r e a t e d _ a t `   D A T E T I M E   D E F A U L T   C U R R E N T _ T I M E S T A M P ,  
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P ,  
         P R I M A R Y   K E Y   ( ` i d ` ) ,  
         U N I Q U E   K E Y   ` u n i q u e _ f a c i l i t y _ c o d e `   ( ` f a c i l i t y _ c o d e ` )  
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ;  
  
 - -   3 .   B O O K I N G S   T A B L E  
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ b o o k i n g s `   (  
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
         ` b o o k i n g _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L ,   - -   R e n a m e d   f r o m   b o o k i n g _ r e f e r e n c e   t o   m a t c h   m o d e l  
         ` f a c i l i t y _ i d `   I N T ( 1 1 )   N U L L ,  
         ` c u s t o m e r _ i d `   I N T ( 1 1 )   N U L L ,  
         ` c u s t o m e r _ n a m e `   V A R C H A R ( 2 5 5 )   N U L L ,  
         ` c u s t o m e r _ e m a i l `   V A R C H A R ( 2 5 5 )   N U L L ,  
         ` c u s t o m e r _ p h o n e `   V A R C H A R ( 5 0 )   N U L L ,  
         ` c u s t o m e r _ a d d r e s s `   V A R C H A R ( 5 0 0 )   N U L L ,  
         ` b o o k i n g _ d a t e `   D A T E   N O T   N U L L ,  
         ` s t a r t _ t i m e `   T I M E   N O T   N U L L ,  
         ` e n d _ t i m e `   T I M E   N O T   N U L L ,  
         ` d u r a t i o n _ h o u r s `   D E C I M A L ( 1 0 , 2 )   D E F A U L T   0 ,  
         ` n u m b e r _ o f _ g u e s t s `   I N T ( 1 1 )   D E F A U L T   0 ,  
         ` b o o k i n g _ t y p e `   E N U M ( ' h o u r l y ' , ' h a l f _ d a y ' , ' f u l l _ d a y ' , ' d a i l y ' , ' m u l t i _ d a y ' , ' w e e k l y ' )   D E F A U L T   ' h o u r l y ' ,  
         ` s t a t u s `   E N U M ( ' p e n d i n g ' , ' c o n f i r m e d ' , ' c a n c e l l e d ' , ' c o m p l e t e d ' , ' n o _ s h o w ' , ' r e f u n d e d ' )   D E F A U L T   ' p e n d i n g ' ,  
         ` p a y m e n t _ s t a t u s `   E N U M ( ' u n p a i d ' , ' p a r t i a l ' , ' p a i d ' , ' r e f u n d e d ' ,   ' o v e r p a i d ' )   D E F A U L T   ' u n p a i d ' ,  
         ` p a y m e n t _ p l a n `   E N U M ( ' f u l l ' , ' d e p o s i t ' , ' i n s t a l l m e n t ' , ' p a y _ l a t e r ' )   D E F A U L T   ' f u l l ' ,  
         ` b a s e _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` s u b t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` d i s c o u n t _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` s e c u r i t y _ d e p o s i t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` t a x _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` t o t a l _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` p a i d _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` b a l a n c e _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` c u r r e n c y `   V A R C H A R ( 1 0 )   D E F A U L T   ' N G N ' ,  
         ` p r o m o _ c o d e `   V A R C H A R ( 5 0 )   N U L L ,  
         ` b o o k i n g _ n o t e s `   T E X T   N U L L ,  
         ` s p e c i a l _ r e q u e s t s `   T E X T   N U L L ,  
         ` b o o k i n g _ s o u r c e `   E N U M ( ' o n l i n e ' , ' d a s h b o a r d ' , ' p h o n e ' , ' w a l k i n ' )   D E F A U L T   ' o n l i n e ' ,  
         ` i n v o i c e _ i d `   I N T ( 1 1 )   N U L L ,  
         ` i s _ r e c u r r i n g `   T I N Y I N T ( 1 )   D E F A U L T   0 ,  
         ` r e c u r r i n g _ p a t t e r n `   E N U M ( ' d a i l y ' , ' w e e k l y ' , ' m o n t h l y ' )   N U L L ,  
         ` r e c u r r i n g _ e n d _ d a t e `   D A T E   N U L L ,  
         ` c r e a t e d _ b y `   I N T ( 1 1 )   N U L L ,  
         ` c r e a t e d _ a t `   D A T E T I M E   D E F A U L T   C U R R E N T _ T I M E S T A M P ,  
         ` u p d a t e d _ a t `   D A T E T I M E   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P ,  
         ` c o n f i r m e d _ a t `   D A T E T I M E   N U L L ,  
         ` c a n c e l l e d _ a t `   D A T E T I M E   N U L L ,  
         ` c o m p l e t e d _ a t `   D A T E T I M E   N U L L ,  
         P R I M A R Y   K E Y   ( ` i d ` ) ,  
         U N I Q U E   K E Y   ` u n i q u e _ b o o k i n g _ n u m `   ( ` b o o k i n g _ n u m b e r ` ) ,  
         K E Y   ` i d x _ f a c i l i t y _ i d `   ( ` f a c i l i t y _ i d ` ) ,  
         K E Y   ` i d x _ c u s t o m e r `   ( ` c u s t o m e r _ i d ` ) ,  
         K E Y   ` i d x _ s t a t u s `   ( ` s t a t u s ` ) ,  
         K E Y   ` i d x _ b o o k i n g _ d a t e `   ( ` b o o k i n g _ d a t e ` )  
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ;  
  
 - -   4 .   I N V O I C E S   T A B L E  
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ i n v o i c e s `   (  
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
         ` i n v o i c e _ n u m b e r `   V A R C H A R ( 5 0 )   N O T   N U L L ,  
         ` c u s t o m e r _ i d `   I N T ( 1 1 )   N O T   N U L L ,   - -   r e f e r e n c e s   c o m p a n i e s   o r   c u s t o m e r s  
         ` i n v o i c e _ d a t e `   D A T E   N O T   N U L L ,  
         ` d u e _ d a t e `   D A T E   N O T   N U L L ,  
         ` s u b t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` t a x _ t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` d i s c o u n t _ t o t a l `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` t o t a l _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` p a i d _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` b a l a n c e _ a m o u n t `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` s t a t u s `   E N U M ( ' d r a f t ' , ' s e n t ' , ' p a r t i a l l y _ p a i d ' , ' p a i d ' , ' o v e r d u e ' , ' c a n c e l l e d ' )   D E F A U L T   ' d r a f t ' ,  
         ` n o t e s `   T E X T   D E F A U L T   N U L L ,  
         ` r e f e r e n c e _ t y p e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L ,  
         ` r e f e r e n c e _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L ,  
         ` c r e a t e d _ b y `   I N T ( 1 1 )   D E F A U L T   N U L L ,  
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P ,  
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P ,  
         P R I M A R Y   K E Y   ( ` i d ` ) ,  
         U N I Q U E   K E Y   ` u n i q u e _ i n v o i c e _ n u m b e r `   ( ` i n v o i c e _ n u m b e r ` ) ,  
         K E Y   ` i d x _ c u s t o m e r _ i d `   ( ` c u s t o m e r _ i d ` ) ,  
         K E Y   ` i d x _ s t a t u s `   ( ` s t a t u s ` )  
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ;  
  
 - -   5 .   C O M P A N I E S   T A B L E   ( U s e d   b y   E n t i t y _ m o d e l   f o r   c u s t o m e r s / e n t i t i e s )  
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ c o m p a n i e s `   (  
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
         ` e n t i t y _ c o d e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L ,  
         ` c o m p a n y _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L ,  
         ` c o n t a c t _ n a m e `   V A R C H A R ( 2 5 5 )   D E F A U L T   N U L L ,  
         ` e m a i l `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L ,  
         ` p h o n e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L ,  
         ` a d d r e s s `   T E X T   D E F A U L T   N U L L ,  
         ` e n t i t y _ t y p e `   V A R C H A R ( 5 0 )   D E F A U L T   ' c u s t o m e r ' ,  
         ` c o m p a n y _ t y p e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L ,  
         ` c u s t o m e r _ t y p e _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L ,  
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' i n a c t i v e ' )   D E F A U L T   ' a c t i v e ' ,  
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P ,  
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P ,  
         P R I M A R Y   K E Y   ( ` i d ` ) ,  
         K E Y   ` i d x _ e m a i l `   ( ` e m a i l ` )  
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ;  
  
 - -   6 .   C U S T O M E R S   T A B L E   ( L e g a c y / C o n c u r r e n t   u s a g e )  
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ c u s t o m e r s `   (  
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
         ` c u s t o m e r _ c o d e `   V A R C H A R ( 5 0 )   N O T   N U L L ,  
         ` c o m p a n y _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L ,  
         ` c o n t a c t _ p e r s o n `   V A R C H A R ( 2 5 5 )   D E F A U L T   N U L L ,  
         ` e m a i l `   V A R C H A R ( 1 0 0 )   D E F A U L T   N U L L ,  
         ` p h o n e `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L ,  
         ` a d d r e s s `   T E X T   D E F A U L T   N U L L ,  
         ` c u s t o m e r _ t y p e _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L ,  
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' i n a c t i v e ' )   D E F A U L T   ' a c t i v e ' ,  
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P ,  
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P ,  
         P R I M A R Y   K E Y   ( ` i d ` ) ,  
         U N I Q U E   K E Y   ` u n i q u e _ c u s t o m e r _ c o d e `   ( ` c u s t o m e r _ c o d e ` )  
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ;  
  
 - -   7 .   C A S H   A C C O U N T S   T A B L E  
 C R E A T E   T A B L E   I F   N O T   E X I S T S   ` e r p _ c a s h _ a c c o u n t s `   (  
         ` i d `   I N T ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
         ` a c c o u n t _ n a m e `   V A R C H A R ( 2 5 5 )   N O T   N U L L ,  
         ` a c c o u n t _ n u m b e r `   V A R C H A R ( 5 0 )   D E F A U L T   N U L L ,  
         ` b a n k _ n a m e `   V A R C H A R ( 2 5 5 )   D E F A U L T   N U L L ,  
         ` c u r r e n c y `   V A R C H A R ( 1 0 )   D E F A U L T   ' N G N ' ,  
         ` c u r r e n t _ b a l a n c e `   D E C I M A L ( 1 5 , 2 )   D E F A U L T   0 ,  
         ` g l _ a c c o u n t _ i d `   I N T ( 1 1 )   D E F A U L T   N U L L ,  
         ` s t a t u s `   E N U M ( ' a c t i v e ' , ' i n a c t i v e ' )   D E F A U L T   ' a c t i v e ' ,  
         ` c r e a t e d _ a t `   D A T E T I M E   N O T   N U L L   D E F A U L T   C U R R E N T _ T I M E S T A M P ,  
         ` u p d a t e d _ a t `   D A T E T I M E   D E F A U L T   N U L L   O N   U P D A T E   C U R R E N T _ T I M E S T A M P ,  
         P R I M A R Y   K E Y   ( ` i d ` )  
 )   E N G I N E = I n n o D B   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ u n i c o d e _ c i ;  
  
 S E T   F O R E I G N _ K E Y _ C H E C K S   =   1 ;  
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =  
 - -   F I X :   A D D   C U S T O M E R _ I D   T O   B O O K I N G S   ( W I T H   C O L L A T I O N   F I X )  
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =  
 - -   1 .   F i x e s   c o l l a t i o n   m i s m a t c h   ( g e n e r a l _ c i   v s   u n i c o d e _ c i )  
 - -   2 .   A d d s   c u s t o m e r _ i d   l i n k  
 - -   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =  
  
 S E T   F O R E I G N _ K E Y _ C H E C K S   =   0 ;  
  
 - -   1 .   S t a n d a r d i z e   C o l l a t i o n   f o r   e r p _ b o o k i n g s   t o   m a t c h   e r p _ c u s t o m e r s  
 - -   T h i s   p r e v e n t s   " I l l e g a l   m i x   o f   c o l l a t i o n s "   e r r o r s   d u r i n g   j o i n s  
 A L T E R   T A B L E   ` e r p _ b o o k i n g s `   C O N V E R T   T O   C H A R A C T E R   S E T   u t f 8 m b 4   C O L L A T E   u t f 8 m b 4 _ u n i c o d e _ c i ;  
  
 - -   2 .   A d d   c u s t o m e r _ i d   c o l u m n   i f   i t   d o e s n ' t   e x i s t  
 S E T   @ d b n a m e   =   D A T A B A S E ( ) ;  
 S E T   @ t a b l e n a m e   =   " e r p _ b o o k i n g s " ;  
 S E T   @ c o l u m n n a m e   =   " c u s t o m e r _ i d " ;  
  
 S E T   @ p r e p a r e d S t a t e m e n t   =   ( S E L E C T   I F (  
     (  
         S E L E C T   C O U N T ( * )   F R O M   I N F O R M A T I O N _ S C H E M A . C O L U M N S  
         W H E R E  
             ( t a b l e _ n a m e   =   @ t a b l e n a m e )  
             A N D   ( t a b l e _ s c h e m a   =   @ d b n a m e )  
             A N D   ( c o l u m n _ n a m e   =   @ c o l u m n n a m e )  
     )   >   0 ,  
     " S E L E C T   1 " ,  
     C O N C A T ( " A L T E R   T A B L E   " ,   @ t a b l e n a m e ,   "   A D D   C O L U M N   " ,   @ c o l u m n n a m e ,   "   I N T ( 1 1 )   N U L L   A F T E R   f a c i l i t y _ i d " )  
 ) ) ;  
  
 P R E P A R E   a l t e r I f N o t E x i s t s   F R O M   @ p r e p a r e d S t a t e m e n t ;  
 E X E C U T E   a l t e r I f N o t E x i s t s ;  
 D E A L L O C A T E   P R E P A R E   a l t e r I f N o t E x i s t s ;  
  
 - -   3 .   A d d   i n d e x   f o r   c u s t o m e r _ i d  
 S E T   @ i n d e x n a m e   =   " i d x _ c u s t o m e r _ i d " ;  
 S E T   @ p r e p a r e d S t a t e m e n t   =   ( S E L E C T   I F (  
     (  
         S E L E C T   C O U N T ( * )   F R O M   I N F O R M A T I O N _ S C H E M A . S T A T I S T I C S  
         W H E R E  
             ( t a b l e _ n a m e   =   @ t a b l e n a m e )  
             A N D   ( t a b l e _ s c h e m a   =   @ d b n a m e )  
             A N D   ( i n d e x _ n a m e   =   @ i n d e x n a m e )  
     )   >   0 ,  
     " S E L E C T   1 " ,  
     C O N C A T ( " C R E A T E   I N D E X   " ,   @ i n d e x n a m e ,   "   O N   " ,   @ t a b l e n a m e ,   "   ( " ,   @ c o l u m n n a m e ,   " ) " )  
 ) ) ;  
  
 P R E P A R E   c r e a t e I n d e x I f N o t E x i s t s   F R O M   @ p r e p a r e d S t a t e m e n t ;  
 E X E C U T E   c r e a t e I n d e x I f N o t E x i s t s ;  
 D E A L L O C A T E   P R E P A R E   c r e a t e I n d e x I f N o t E x i s t s ;  
  
 - -   4 .   A t t e m p t   t o   l i n k   e x i s t i n g   b o o k i n g s   t o   c u s t o m e r s   t a b l e   b y   e m a i l  
 - -   N o w   s a f e   d u e   t o   c o l l a t i o n   f i x  
 U P D A T E   ` e r p _ b o o k i n g s `   b  
 J O I N   ` e r p _ c u s t o m e r s `   c   O N   b . c u s t o m e r _ e m a i l   =   c . e m a i l  
 S E T   b . c u s t o m e r _ i d   =   c . i d  
 W H E R E   b . c u s t o m e r _ i d   I S   N U L L ;  
  
 S E T   F O R E I G N _ K E Y _ C H E C K S   =   1 ;  
 