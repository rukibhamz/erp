<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 012: Fix role permissions for existing deployments
 *
 * Ensures:
 * 1. manager role has pos.write (required by Pos::terminals() after the pos.manage fix)
 * 2. admin role has payables.delete and receivables.delete (required for vendor/customer deletion)
 * 3. super_admin role has all permissions (idempotent catch-all)
 *
 * Safe to run multiple times — uses INSERT IGNORE.
 */
class Migration_Fix_role_permissions {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        $prefix = $this->db->getPrefix();

        // Helper: grant a permission to a role (idempotent)
        $grant = function($roleCode, $module, $permission) use ($prefix) {
            // Look up role id
            $role = $this->db->fetchOne(
                "SELECT id FROM `{$prefix}roles` WHERE role_code = ?",
                [$roleCode]
            );
            if (!$role) return;

            // Look up permission id
            $perm = $this->db->fetchOne(
                "SELECT id FROM `{$prefix}permissions` WHERE module = ? AND permission = ?",
                [$module, $permission]
            );
            if (!$perm) return;

            // Insert if not already present
            $this->db->query(
                "INSERT IGNORE INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
                 VALUES (?, ?, NOW())",
                [$role['id'], $perm['id']]
            );
        };

        // 1. manager needs pos.write so Pos::terminals() grants access
        $grant('manager', 'pos', 'write');

        // 2. admin needs delete permissions for vendors and customers
        $grant('admin', 'payables', 'delete');
        $grant('admin', 'receivables', 'delete');

        // 3. super_admin catch-all — ensure it has every permission
        $superAdmin = $this->db->fetchOne(
            "SELECT id FROM `{$prefix}roles` WHERE role_code = 'super_admin'"
        );
        if ($superAdmin) {
            $this->db->query(
                "INSERT IGNORE INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
                 SELECT ?, p.id, NOW()
                 FROM `{$prefix}permissions` p
                 WHERE NOT EXISTS (
                     SELECT 1 FROM `{$prefix}role_permissions` rp
                     WHERE rp.role_id = ? AND rp.permission_id = p.id
                 )",
                [$superAdmin['id'], $superAdmin['id']]
            );
        }
    }

    public function down() {
        // Intentionally left empty — removing permissions is destructive
        // and should be done manually if needed.
    }
}
