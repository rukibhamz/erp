-- ============================================================================
-- ENSURE ADMIN HAS LOCATIONS PERMISSIONS
-- ============================================================================
-- This migration ensures admin role has all locations (properties) permissions
-- Safe to run multiple times (idempotent)
-- ============================================================================

-- Assign all locations permissions to admin role (if not already assigned)
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'admin'
AND p.module IN ('locations', 'properties') -- Include both new and legacy
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

-- Also ensure admin has ALL permissions (in case migration didn't run completely)
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code = 'admin'
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

