-- ============================================================================
-- FIX STAFF PERMISSIONS - POS, Bookings, Inventory, Utilities
-- ============================================================================
-- This migration grants staff role read and update permissions for:
-- 1. POS module
-- 2. Bookings module
-- 3. Inventory module
-- 4. Utilities module
-- ============================================================================

-- Step 1: Ensure POS permissions exist
-- ============================================================================
INSERT IGNORE INTO `erp_permissions` (`module`, `permission`, `description`, `created_at`) VALUES
('pos', 'read', 'View POS', NOW()),
('pos', 'write', 'Create/edit POS transactions', NOW()),
('pos', 'delete', 'Delete POS transactions', NOW()),
('pos', 'create', 'Create POS transactions', NOW()),
('pos', 'update', 'Update POS transactions', NOW());

-- Step 2: Remove existing staff permissions (clean slate)
-- ============================================================================
DELETE rp FROM `erp_role_permissions` rp
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'staff';

-- Step 3: Grant staff read and update permissions for POS, Bookings, Inventory, Utilities
-- ============================================================================
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

-- Step 4: Also grant create permission (for creating new records)
-- ============================================================================
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
-- VERIFICATION QUERIES
-- ============================================================================

-- Check staff permissions by module
SELECT p.module, p.permission, COUNT(*) as count
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'staff'
AND p.module IN ('pos', 'bookings', 'inventory', 'utilities', 'dashboard', 'notifications')
GROUP BY p.module, p.permission
ORDER BY p.module, p.permission;

-- Count total staff permissions
SELECT COUNT(*) as total_staff_permissions
FROM `erp_role_permissions` rp
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'staff';

-- List all staff permissions
SELECT p.module, p.permission
FROM `erp_role_permissions` rp
JOIN `erp_permissions` p ON rp.permission_id = p.id
JOIN `erp_roles` r ON rp.role_id = r.id
WHERE r.role_code = 'staff'
ORDER BY p.module, p.permission;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================

