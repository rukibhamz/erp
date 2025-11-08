-- ============================================================================
-- CREATE MISSING BUSINESS MODULE TABLES
-- ============================================================================
-- This migration creates all business module tables referenced by the Dashboard
-- and other controllers but missing from the database schema.
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================
-- Usage: mysql -u username -p database_name < database/migrations/004_create_business_module_tables.sql
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

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

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
-- POS TRANSACTIONS TABLE (alias for pos_sales if it doesn't exist)
-- Note: This creates a view or table depending on whether pos_sales exists
-- ============================================================================
-- Check if pos_sales exists, if not create pos_transactions
-- If pos_sales exists, we'll use that table name in queries instead

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check all tables exist
SELECT 'Tables Check' as check_type, 
       COUNT(*) as tables_found
FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_spaces', 'erp_stock_levels', 'erp_items', 'erp_leases', 
                   'erp_work_orders', 'erp_tax_deadlines', 'erp_utility_bills');

-- List all created tables
SELECT table_name, table_rows, create_time
FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_spaces', 'erp_stock_levels', 'erp_items', 'erp_leases', 
                   'erp_work_orders', 'erp_tax_deadlines', 'erp_utility_bills')
ORDER BY table_name;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================

