<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends Base_Model {
    protected $table = 'roles';
    
    /**
     * Get all roles with permission count
     */
    public function getAllWithPermissionCount() {
        $sql = "SELECT r.*, 
                       COUNT(rp.id) as permission_count
                FROM `" . $this->db->getPrefix() . $this->table . "` r
                LEFT JOIN `" . $this->db->getPrefix() . "role_permissions` rp ON r.id = rp.role_id
                GROUP BY r.id
                ORDER BY r.role_name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get permission IDs for a role
     */
    public function getPermissionIds($roleId) {
        $sql = "SELECT permission_id FROM `" . $this->db->getPrefix() . "role_permissions` WHERE role_id = ?";
        $result = $this->db->fetchAll($sql, [$roleId]);
        return array_column($result, 'permission_id');
    }
    
    /**
     * Update permissions for a role
     */
    public function updatePermissions($roleId, $permissionIds) {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Remove existing permissions
            $pdo->prepare("DELETE FROM `{$prefix}role_permissions` WHERE role_id = ?")->execute([$roleId]);
            
            // Add new permissions
            if (!empty($permissionIds)) {
                $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at) VALUES (?, ?, NOW())");
                foreach ($permissionIds as $permId) {
                    $stmt->execute([$roleId, $permId]);
                }
            }
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get role by code
     */
    public function getByCode($code) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE role_code = ?";
        return $this->db->fetchOne($sql, [$code]);
    }
    
    /**
     * Get all active roles
     */
    public function getActiveRoles() {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE is_active = 1 ORDER BY role_name";
        return $this->db->fetchAll($sql);
    }
}
