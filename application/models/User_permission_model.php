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
            
            // Check user-specific permissions (user_permissions table)
            $userPermSql = "SELECT COUNT(*) as count FROM `{$prefix}user_permissions` up
                           WHERE up.user_id = ? AND up.permission_id = ?";
            $userPermResult = $this->db->fetchOne($userPermSql, [$userId, $permissionId]);
            $hasUserPermission = ($userPermResult['count'] ?? 0) > 0;
            
            // Check role-based permissions (role_permissions table via roles table)
            $hasRolePermission = false;
            if ($userRole) {
                // Map permission action names (read/write/delete vs create/read/update/delete)
                $permissionAction = $permission;
                if ($permission === 'create') {
                    $permissionAction = 'write'; // Some systems use 'write' for create
                } elseif ($permission === 'update') {
                    $permissionAction = 'write'; // Some systems use 'write' for update
                }
                
                // Check role_permissions via roles table (using role_code)
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
            }
            
            $hasPermission = $hasUserPermission || $hasRolePermission;
            
            // Enhanced debug logging
            if (!$hasPermission) {
                error_log("Permission check: User {$userId}, Role: {$userRole}, Module: {$module}, Permission: {$permission}, Result: NO");
                error_log("  - User-specific permission: " . ($hasUserPermission ? 'YES' : 'NO'));
                error_log("  - Role-based permission: " . ($hasRolePermission ? 'YES' : 'NO'));
                error_log("  - Permission ID: {$permissionId}");
                
                // Log all permissions for this role for debugging
                if ($userRole) {
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
                }
            } else {
                // Log successful permission check for debugging
                error_log("Permission check: User {$userId}, Role: {$userRole}, Module: {$module}, Permission: {$permission}, Result: YES");
            }
            
            return $hasPermission;
        } catch (Exception $e) {
            error_log("Error checking permission: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
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

