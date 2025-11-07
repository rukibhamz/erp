-- ============================================================================
-- CRITICAL PERMISSION BUG FIX - Manager Role Missing Multiple Module Permissions
-- ============================================================================
-- This script fixes the missing permissions for the "manager" role
-- Run this script immediately to grant all required permissions
-- ============================================================================

-- Step 1: Ensure all permissions exist in erp_permissions table
-- ============================================================================
INSERT IGNORE INTO erp_permissions (module, permission, description, created_at) VALUES
('accounting', 'read', 'Read accounting data', NOW()),
('accounting', 'write', 'Create/edit accounting data', NOW()),
('accounting', 'delete', 'Delete accounting data', NOW()),
('accounting', 'create', 'Create accounting data', NOW()),
('accounting', 'update', 'Update accounting data', NOW()),
('bookings', 'read', 'Read bookings', NOW()),
('bookings', 'write', 'Create/edit bookings', NOW()),
('bookings', 'delete', 'Delete bookings', NOW()),
('bookings', 'create', 'Create bookings', NOW()),
('bookings', 'update', 'Update bookings', NOW()),
('properties', 'read', 'Read properties', NOW()),
('properties', 'write', 'Create/edit properties', NOW()),
('properties', 'delete', 'Delete properties', NOW()),
('properties', 'create', 'Create properties', NOW()),
('properties', 'update', 'Update properties', NOW()),
('inventory', 'read', 'Read inventory', NOW()),
('inventory', 'write', 'Create/edit inventory', NOW()),
('inventory', 'delete', 'Delete inventory', NOW()),
('inventory', 'create', 'Create inventory', NOW()),
('inventory', 'update', 'Update inventory', NOW()),
('utilities', 'read', 'Read utilities', NOW()),
('utilities', 'write', 'Create/edit utilities', NOW()),
('utilities', 'delete', 'Delete utilities', NOW()),
('utilities', 'create', 'Create utilities', NOW()),
('utilities', 'update', 'Update utilities', NOW()),
('settings', 'read', 'Read settings', NOW()),
('settings', 'write', 'Create/edit settings', NOW()),
('settings', 'delete', 'Delete settings', NOW()),
('settings', 'create', 'Create settings', NOW()),
('settings', 'update', 'Update settings', NOW());

-- Step 2: Get the manager role ID
-- ============================================================================
SET @manager_role_id = (SELECT id FROM erp_roles WHERE role_code = 'manager' LIMIT 1);

-- Step 3: Assign ALL these permissions to manager role via role_permissions
-- ============================================================================
INSERT INTO erp_role_permissions (role_id, permission_id, created_at) 
SELECT @manager_role_id, p.id, NOW()
FROM erp_permissions p 
WHERE p.module IN ('accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings')
AND NOT EXISTS (
    SELECT 1 FROM erp_role_permissions rp 
    WHERE rp.role_id = @manager_role_id AND rp.permission_id = p.id
);

-- Step 4: Verification Query - Should return multiple rows
-- ============================================================================
-- Run this to verify the fix worked:
SELECT p.module, p.permission, r.role_code as role, r.role_name
FROM erp_role_permissions rp
JOIN erp_permissions p ON rp.permission_id = p.id
JOIN erp_roles r ON rp.role_id = r.id
WHERE r.role_code = 'manager' 
AND p.module IN ('accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings')
ORDER BY p.module, p.permission;

-- Expected: Should return at least 15 rows (5 modules × 3 permissions each)
-- Plus settings module permissions

-- Step 5: Verify User ID 4 has manager role
-- ============================================================================
SELECT u.id, u.username, u.email, u.role as user_role, r.role_code, r.role_name
FROM erp_users u
LEFT JOIN erp_roles r ON u.role = r.role_code
WHERE u.id = 4;

-- ============================================================================
-- END OF FIX SCRIPT
-- ============================================================================

