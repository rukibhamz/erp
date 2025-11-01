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
        $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "` up
                INNER JOIN `" . $this->db->getPrefix() . "permissions` p ON up.permission_id = p.id
                WHERE up.user_id = ? AND p.module = ? AND p.permission = ?";
        $result = $this->db->fetchOne($sql, [$userId, $module, $permission]);
        return ($result['count'] ?? 0) > 0;
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

