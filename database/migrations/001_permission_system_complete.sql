-- ============================================================================
-- COMPLETE PERMISSION SYSTEM MIGRATION (ALL-IN-ONE)
-- ============================================================================
-- This is the COMPLETE migration for the permission system
-- Creates all tables, seeds all data, and assigns permissions to all roles
-- Includes: Manager permissions (Accounting sub-modules, POS, no Tax)
-- Includes: Staff permissions (POS, Bookings, Inventory, Utilities)
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================
-- Usage: mysql -u username -p database_name < database/migrations/001_permission_system_complete.sql
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

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

-- ============================================================================
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

-- ============================================================================
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

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- STEP 4: Insert all system roles
-- ============================================================================
INSERT IGNORE INTO `erp_roles` (`role_name`, `role_code`, `description`, `is_system`, `is_active`, `created_at`) VALUES
('Super Admin', 'super_admin', 'Full system access with all permissions', 1, 1, NOW()),
('Admin', 'admin', 'Administrator with system access', 1, 1, NOW()),
('Manager', 'manager', 'Management role with full business module access', 1, 1, NOW()),
('Staff', 'staff', 'Staff level access', 1, 1, NOW()),
('User', 'user', 'Basic user role', 1, 1, NOW()),
('Accountant', 'accountant', 'Accounting focused role', 0, 1, NOW());

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
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

-- ============================================================================
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
-- VERIFICATION QUERIES (Run these to verify)
-- ============================================================================
-- This migration includes:
-- ✅ All permission tables (erp_permissions, erp_roles, erp_role_permissions)
-- ✅ All roles (super_admin, admin, manager, staff, user, accountant)
-- ✅ All permissions for all modules including Accounting sub-modules and POS
-- ✅ Manager: All business modules + Accounting sub-modules + POS (Tax excluded)
-- ✅ Staff: POS, Bookings, Inventory, Utilities (read, update, create)
-- ✅ Accountant: All accounting permissions
-- ============================================================================

-- Check all tables exist
SELECT 'Tables Check' as check_type, 
       COUNT(*) as tables_found
FROM information_schema.tables 
WHERE table_schema = DATABASE()
AND table_name IN ('erp_permissions', 'erp_roles', 'erp_role_permissions');

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

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================

