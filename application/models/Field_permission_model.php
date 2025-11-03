<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Field_permission_model extends Base_Model {
    protected $table = 'field_permissions';
    
    /**
     * Check if user has permission to view/edit a field
     */
    public function canAccessField($userId, $module, $tableName, $fieldName, $action = 'read') {
        try {
            $userModel = $this->loadModel('User_model');
            $user = $userModel->getById($userId);
            if (!$user) {
                return false;
            }
            
            $userRole = $user['role'] ?? null;
            
            // Check user-specific field permission
            $sql = "SELECT permission_type FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE user_id = ? AND module = ? AND table_name = ? AND field_name = ?";
            $result = $this->db->fetchOne($sql, [$userId, $module, $tableName, $fieldName]);
            
            if ($result) {
                $permissionType = $result['permission_type'];
                if ($permissionType === 'hidden') {
                    return false;
                }
                if ($action === 'read') {
                    return true;
                }
                return $permissionType === 'write';
            }
            
            // Check role-based field permission
            if ($userRole) {
                $sql = "SELECT permission_type FROM `" . $this->db->getPrefix() . $this->table . "` 
                        WHERE role = ? AND module = ? AND table_name = ? AND field_name = ? AND user_id IS NULL";
                $result = $this->db->fetchOne($sql, [$userRole, $module, $tableName, $fieldName]);
                
                if ($result) {
                    $permissionType = $result['permission_type'];
                    if ($permissionType === 'hidden') {
                        return false;
                    }
                    if ($action === 'read') {
                        return true;
                    }
                    return $permissionType === 'write';
                }
            }
            
            // Default: allow if no restriction
            return true;
        } catch (Exception $e) {
            error_log('Field_permission_model canAccessField error: ' . $e->getMessage());
            return true; // Default to allow
        }
    }
    
    /**
     * Get all field permissions for a user
     */
    public function getUserFieldPermissions($userId, $module = null) {
        try {
            $userModel = $this->loadModel('User_model');
            $user = $userModel->getById($userId);
            $userRole = $user['role'] ?? null;
            
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE (user_id = ? OR (role = ? AND user_id IS NULL))";
            $params = [$userId, $userRole];
            
            if ($module) {
                $sql .= " AND module = ?";
                $params[] = $module;
            }
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Field_permission_model getUserFieldPermissions error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Set field permission for user or role
     */
    public function setFieldPermission($module, $tableName, $fieldName, $permissionType, $userId = null, $role = null) {
        try {
            // Check if permission already exists
            $where = "module = ? AND table_name = ? AND field_name = ?";
            $params = [$module, $tableName, $fieldName];
            
            if ($userId) {
                $where .= " AND user_id = ?";
                $params[] = $userId;
            } else {
                $where .= " AND user_id IS NULL";
            }
            
            if ($role) {
                $where .= " AND role = ?";
                $params[] = $role;
            } else {
                $where .= " AND role IS NULL";
            }
            
            $existing = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . $this->table . "` WHERE {$where}",
                $params
            );
            
            $data = [
                'module' => $module,
                'table_name' => $tableName,
                'field_name' => $fieldName,
                'permission_type' => $permissionType,
                'user_id' => $userId,
                'role' => $role
            ];
            
            if ($existing) {
                return $this->update($existing['id'], $data);
            } else {
                return $this->create($data);
            }
        } catch (Exception $e) {
            error_log('Field_permission_model setFieldPermission error: ' . $e->getMessage());
            return false;
        }
    }
}


