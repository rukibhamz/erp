<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_permission_model extends Base_Model {
    protected $table = 'user_permissions';
    
    public function getUserPermissions($userId) {
        $sql = "SELECT p.* FROM `" . $this->db->getPrefix() . "permissions` p
                INNER JOIN `" . $this->db->getPrefix() . $this->table . "` up ON p.id = up.permission_id
                WHERE up.user_id = ?";
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    public function getUserPermissionIds($userId) {
        $sql = "SELECT permission_id FROM `" . $this->db->getPrefix() . $this->table . "` WHERE user_id = ?";
        $result = $this->db->fetchAll($sql, [$userId]);
        return array_column($result, 'permission_id');
    }
    
    /**
     * Check if user has permission (ROLE-BASED PRIMARY SYSTEM)
     * SECURITY: This method uses role-based permissions as the PRIMARY system.
     * User-specific permissions are only checked as explicit overrides.
     * 
     * @param int $userId User ID
     * @param string $module Module name
     * @param string $permission Permission name (read, write, delete, create, update)
     * @return bool True if user has permission, false otherwise
     */
    public function hasPermission($userId, $module, $permission) {
        try {
            $prefix = $this->db->getPrefix();
            
            // First, get user's role
            $user = $this->db->fetchOne(
                "SELECT role FROM `{$prefix}users` WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                error_log("Permission check: User {$userId} not found");
                return false;
            }
            
            $userRole = $user['role'] ?? null;
            
            // Super admin and admin bypass - they have all permissions
            if ($userRole === 'super_admin' || $userRole === 'admin') {
                return true;
            }
            
            // Check if permission exists in permissions table
            $permCheck = $this->db->fetchOne(
                "SELECT id FROM `{$prefix}permissions` WHERE module = ? AND permission = ?",
                [$module, $permission]
            );
            
            if (!$permCheck) {
                error_log("Permission check: Permission '{$module}.{$permission}' does not exist in permissions table!");
                return false;
            }
            
            $permissionId = $permCheck['id'];
            
            // ====================================================================
            // PRIMARY: Check role-based permissions FIRST (role_permissions table)
            // ====================================================================
            $hasRolePermission = false;
            if ($userRole) {
                // Verify required tables exist (no silent failures)
                try {
                    // Check if roles table exists
                    $rolesTableExists = $this->db->fetchOne(
                        "SELECT COUNT(*) as count FROM information_schema.tables 
                         WHERE table_schema = DATABASE() 
                         AND table_name = ?",
                        ["{$prefix}roles"]
                    );
                    
                    // Check if role_permissions table exists
                    $rolePermsTableExists = $this->db->fetchOne(
                        "SELECT COUNT(*) as count FROM information_schema.tables 
                         WHERE table_schema = DATABASE() 
                         AND table_name = ?",
                        ["{$prefix}role_permissions"]
                    );
                    
                    if (($rolesTableExists['count'] ?? 0) == 0 || ($rolePermsTableExists['count'] ?? 0) == 0) {
                        error_log("CRITICAL: Role-based permission tables do not exist!");
                        error_log("Missing tables: " . (($rolesTableExists['count'] ?? 0) == 0 ? "{$prefix}roles " : "") . 
                                 (($rolePermsTableExists['count'] ?? 0) == 0 ? "{$prefix}role_permissions" : ""));
                        error_log("Please run database/migrations/000_complete_system_migration.sql to fix this.");
                        // Security-first: Deny access when permission system is incomplete
                        throw new Exception("Role-based permission system tables are missing. System is not properly configured.");
                    }
                } catch (Exception $tableCheckException) {
                    // Re-throw if it's our explicit exception
                    if (strpos($tableCheckException->getMessage(), "Role-based permission system tables are missing") !== false) {
                        throw $tableCheckException;
                    }
                    error_log("Warning: Could not verify role permission tables exist: " . $tableCheckException->getMessage());
                    // Don't fail silently - return false if we can't verify tables
                    return false;
                }
                
                // Map permission action names (read/write/delete vs create/read/update/delete)
                $permissionAction = $permission;
                if ($permission === 'create') {
                    $permissionAction = 'write'; // Some systems use 'write' for create
                } elseif ($permission === 'update') {
                    $permissionAction = 'write'; // Some systems use 'write' for update
                }
                
                // Check role_permissions via roles table (using role_code) - PRIMARY CHECK
                try {
                    $rolePermSql = "SELECT COUNT(*) as count 
                                   FROM `{$prefix}role_permissions` rp
                                   INNER JOIN `{$prefix}roles` r ON rp.role_id = r.id
                                   INNER JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
                                   WHERE r.role_code = ? AND p.module = ? AND p.permission = ?";
                    $rolePermResult = $this->db->fetchOne($rolePermSql, [$userRole, $module, $permission]);
                    $hasRolePermission = ($rolePermResult['count'] ?? 0) > 0;
                    
                    // Also check with mapped permission action if different
                    if (!$hasRolePermission && $permissionAction !== $permission) {
                        $rolePermResult = $this->db->fetchOne($rolePermSql, [$userRole, $module, $permissionAction]);
                        $hasRolePermission = ($rolePermResult['count'] ?? 0) > 0;
                    }
                } catch (Exception $rolePermException) {
                    // NO SILENT FAILURES - Log and throw
                    $errorMsg = $rolePermException->getMessage();
                    error_log("CRITICAL: Error checking role permissions: " . $errorMsg);
                    error_log("User ID: {$userId}, Role: {$userRole}, Module: {$module}, Permission: {$permission}");
                    
                    if (strpos($errorMsg, "doesn't exist") !== false || strpos($errorMsg, "Base table") !== false) {
                        error_log("CRITICAL: erp_role_permissions table does not exist!");
                        error_log("Please run database/migrations/000_complete_system_migration.sql to fix this.");
                        // Security-first: Deny access when permission system is incomplete
                        throw new Exception("Role-based permission system is not properly configured. Required tables are missing.");
                    }
                    // For other errors, still throw (no silent fallback)
                    throw $rolePermException;
                }
            } else {
                error_log("Permission check: User {$userId} has no role assigned!");
                return false;
            }
            
            // ====================================================================
            // SECONDARY: Check user-specific permissions (only as explicit overrides)
            // ====================================================================
            // User-specific permissions can be used to grant additional permissions
            // beyond what the role provides, but role-based is PRIMARY
            $hasUserPermission = false;
            try {
                $userPermSql = "SELECT COUNT(*) as count FROM `{$prefix}user_permissions` up
                               WHERE up.user_id = ? AND up.permission_id = ?";
                $userPermResult = $this->db->fetchOne($userPermSql, [$userId, $permissionId]);
                $hasUserPermission = ($userPermResult['count'] ?? 0) > 0;
            } catch (Exception $userPermException) {
                // User-specific permissions are optional - log but don't fail
                error_log("Warning: Could not check user-specific permissions: " . $userPermException->getMessage());
                $hasUserPermission = false;
            }
            
            // Final decision: Role-based is PRIMARY, user-specific is secondary override
            $hasPermission = $hasRolePermission || $hasUserPermission;
            
            // Enhanced debug logging
            if (!$hasPermission) {
                error_log("Permission check: User {$userId}, Role: {$userRole}, Module: {$module}, Permission: {$permission}, Result: NO");
                error_log("  - User-specific permission: " . ($hasUserPermission ? 'YES' : 'NO'));
                error_log("  - Role-based permission: " . ($hasRolePermission ? 'YES' : 'NO'));
                error_log("  - Permission ID: {$permissionId}");
                
                // Log all permissions for this role for debugging (only if table exists)
                if ($userRole) {
                    try {
                        $allRolePerms = $this->db->fetchAll(
                            "SELECT p.module, p.permission 
                             FROM `{$prefix}role_permissions` rp
                             INNER JOIN `{$prefix}roles` r ON rp.role_id = r.id
                             INNER JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
                             WHERE r.role_code = ?
                             ORDER BY p.module, p.permission",
                            [$userRole]
                        );
                        error_log("  - All permissions for role '{$userRole}': " . json_encode($allRolePerms));
                    } catch (Exception $e) {
                        error_log("  - Could not fetch role permissions (table may not exist): " . $e->getMessage());
                    }
                }
            } else {
                // Log successful permission check for debugging
                error_log("Permission check: User {$userId}, Role: {$userRole}, Module: {$module}, Permission: {$permission}, Result: YES");
            }
            
            return $hasPermission;
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            
            // Check if it's a missing table error or our explicit exception
            if (strpos($errorMsg, "doesn't exist") !== false || 
                strpos($errorMsg, "Base table") !== false ||
                strpos($errorMsg, "Role-based permission system") !== false) {
                error_log("CRITICAL: Permission system error: " . $errorMsg);
                error_log("User ID: {$userId}, Module: {$module}, Permission: {$permission}");
                error_log("Please run database/migrations/000_complete_system_migration.sql to fix this.");
                // Security-first: Deny access when permission system has critical errors
                return false;
            }
            
            // For other exceptions, log fully and deny access (fail-secure)
            error_log("Error checking permission: " . $errorMsg);
            error_log("User ID: {$userId}, Module: {$module}, Permission: {$permission}");
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // NO SILENT FAILURES - Always return false on errors (fail-secure)
            return false;
        }
    }
    
    public function assignPermissions($userId, $permissionIds) {
        // Remove existing permissions
        $this->db->delete($this->table, "user_id = ?", [$userId]);
        
        // Add new permissions
        if (!empty($permissionIds)) {
            foreach ($permissionIds as $permId) {
                $this->create([
                    'user_id' => $userId,
                    'permission_id' => $permId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
    
    public function removeAllPermissions($userId) {
        return $this->db->delete($this->table, "user_id = ?", [$userId]);
    }
}

