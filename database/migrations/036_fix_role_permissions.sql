-- ============================================================================
-- Migration 036: Fix role permissions for existing deployments
-- ============================================================================
-- Ensures:
--   1. manager role has pos.write (required by Pos::terminals() after pos.manage fix)
--   2. admin role has payables.delete and receivables.delete (vendor/customer deletion)
--   3. super_admin has all permissions (idempotent catch-all)
-- Safe to run multiple times — uses INSERT IGNORE.
-- ============================================================================

-- 1. Grant pos.write to manager
INSERT IGNORE INTO `erp_role_permissions` (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'manager'
  AND p.module = 'pos'
  AND p.permission = 'write';

-- 2. Grant payables.delete to admin
INSERT IGNORE INTO `erp_role_permissions` (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'admin'
  AND p.module = 'payables'
  AND p.permission = 'delete';

-- 3. Grant receivables.delete to admin
INSERT IGNORE INTO `erp_role_permissions` (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'admin'
  AND p.module = 'receivables'
  AND p.permission = 'delete';

-- 4. super_admin catch-all — ensure it has every permission
INSERT IGNORE INTO `erp_role_permissions` (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'super_admin';
