-- ============================================================================
-- VERIFY AND FIX MANAGER PERMISSIONS - Complete Fix
-- ============================================================================
-- This script verifies and ensures manager has correct permissions:
-- 1. Verifies Accounting sub-modules are assigned
-- 2. Verifies POS is assigned
-- 3. Removes tax permissions (if any remain)
-- 4. Provides diagnostic queries
-- ============================================================================

-- Step 1: Ensure all Accounting sub-module permissions exist
-- ============================================================================
INSERT IGNORE INTO `erp_permissions` (`module`, `permission`, `description`, `created_at`) VALUES
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
('pos', 'read', 'View POS', NOW()),
('pos', 'write', 'Create/edit POS transactions', NOW()),
('pos', 'delete', 'Delete POS transactions', NOW()),
('pos', 'create', 'Create POS transactions', NOW()),
('pos', 'update', 'Update POS transactions', NOW());

-- Step 2: Remove ALL tax permissions from manager (ensure complete removal)
-- ============================================================================
DELETE rp FROM `erp_role_permissions` rp
JOIN `erp_roles` r ON rp.role_id = r.id
JOIN `erp_permissions` p ON rp.permission_id = p.id
WHERE r.role_code = 'manager'
AND p.module = 'tax';

-- Step 3: Add ALL Accounting sub-module permissions to manager
-- ============================================================================
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'manager'
AND p.module IN ('accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates')
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- Step 4: Add ALL POS permissions to manager
-- ============================================================================
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'manager'
AND p.module = 'pos'
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- ============================================================================
-- DIAGNOSTIC QUERIES
-- ============================================================================

-- Check manager role ID
SELECT id, role_code, role_name FROM `erp_roles` WHERE role_code = 'manager';

-- Count total manager permissions
SELECT COUNT(*) as total_permissions
FROM `erp_role_permissions` rp
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager';

-- Check Accounting sub-module permissions (should be 30: 6 modules × 5 permissions)
SELECT p.module, COUNT(*) as permission_count
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
AND p.module IN ('accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates')
GROUP BY p.module
ORDER BY p.module;

-- Check POS permissions (should be 5)
SELECT COUNT(*) as pos_permissions
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
AND p.module = 'pos';

-- Verify tax permissions are removed (should be 0)
SELECT COUNT(*) as tax_permissions
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
AND p.module = 'tax';

-- List ALL manager permissions by module
SELECT p.module, p.permission
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
ORDER BY p.module, p.permission;

-- ============================================================================
-- END OF VERIFICATION SCRIPT
-- ============================================================================

