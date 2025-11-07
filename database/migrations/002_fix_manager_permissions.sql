-- ============================================================================
-- FIX MANAGER PERMISSIONS - Accounting Sub-modules, Remove Tax, Add POS
-- ============================================================================
-- This migration fixes manager permissions:
-- 1. Adds all Accounting sub-module permissions (accounts, cash, receivables, payables, ledger, etc.)
-- 2. Removes tax module permissions from manager
-- 3. Adds POS module permissions to manager
-- ============================================================================

-- Step 1: Insert Accounting sub-module permissions (if they don't exist)
-- ============================================================================
INSERT IGNORE INTO `erp_permissions` (`module`, `permission`, `description`, `created_at`) VALUES
-- Accounts (Chart of Accounts)
('accounts', 'read', 'View chart of accounts', NOW()),
('accounts', 'write', 'Create/edit accounts', NOW()),
('accounts', 'delete', 'Delete accounts', NOW()),
('accounts', 'create', 'Create accounts', NOW()),
('accounts', 'update', 'Update accounts', NOW()),

-- Cash Management
('cash', 'read', 'View cash management', NOW()),
('cash', 'write', 'Create/edit cash transactions', NOW()),
('cash', 'delete', 'Delete cash transactions', NOW()),
('cash', 'create', 'Create cash transactions', NOW()),
('cash', 'update', 'Update cash transactions', NOW()),

-- Receivables
('receivables', 'read', 'View receivables', NOW()),
('receivables', 'write', 'Create/edit receivables', NOW()),
('receivables', 'delete', 'Delete receivables', NOW()),
('receivables', 'create', 'Create receivables', NOW()),
('receivables', 'update', 'Update receivables', NOW()),

-- Payables
('payables', 'read', 'View payables', NOW()),
('payables', 'write', 'Create/edit payables', NOW()),
('payables', 'delete', 'Delete payables', NOW()),
('payables', 'create', 'Create payables', NOW()),
('payables', 'update', 'Update payables', NOW()),

-- Ledger
('ledger', 'read', 'View general ledger', NOW()),
('ledger', 'write', 'Create/edit ledger entries', NOW()),
('ledger', 'delete', 'Delete ledger entries', NOW()),
('ledger', 'create', 'Create ledger entries', NOW()),
('ledger', 'update', 'Update ledger entries', NOW()),

-- Estimates
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

-- Step 2: Remove tax module permissions from manager role
-- ============================================================================
DELETE rp FROM `erp_role_permissions` rp
JOIN `erp_roles` r ON rp.role_id = r.id
JOIN `erp_permissions` p ON rp.permission_id = p.id
WHERE r.role_code = 'manager'
AND p.module = 'tax';

-- Step 3: Add Accounting sub-module permissions to manager role
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

-- Step 4: Add POS module permissions to manager role
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
-- VERIFICATION QUERIES
-- ============================================================================

-- Check manager has Accounting sub-module permissions
SELECT p.module, p.permission
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
AND p.module IN ('accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates')
ORDER BY p.module, p.permission;

-- Check manager has POS permissions
SELECT p.module, p.permission
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
AND p.module = 'pos'
ORDER BY p.permission;

-- Verify tax permissions are removed from manager
SELECT COUNT(*) as tax_permissions_count
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'manager'
AND p.module = 'tax';

-- Should return 0

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================

