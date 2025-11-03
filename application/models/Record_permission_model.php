<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Record_permission_model extends Base_Model {
    protected $table = 'record_permissions';
    
    /**
     * Check if user can access a specific record
     */
    public function canAccessRecord($userId, $module, $tableName, $recordId, $action = 'read') {
        try {
            // Check explicit record permission
            $sql = "SELECT permission_type FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE user_id = ? AND module = ? AND table_name = ? AND record_id = ?
                    AND (expires_at IS NULL OR expires_at > NOW())";
            $result = $this->db->fetchOne($sql, [$userId, $module, $tableName, $recordId]);
            
            if ($result) {
                $permissionType = $result['permission_type'];
                
                if ($permissionType === 'own') {
                    // Check if user owns the record
                    return $this->isOwner($userId, $tableName, $recordId);
                }
                
                if ($action === 'read') {
                    return in_array($permissionType, ['read', 'write', 'delete']);
                }
                if ($action === 'update') {
                    return in_array($permissionType, ['write', 'delete']);
                }
                if ($action === 'delete') {
                    return $permissionType === 'delete';
                }
            }
            
            // Check if user owns the record (for 'own' permissions)
            if ($this->isOwner($userId, $tableName, $recordId)) {
                return true;
            }
            
            // Default: deny if no explicit permission
            return false;
        } catch (Exception $e) {
            error_log('Record_permission_model canAccessRecord error: ' . $e->getMessage());
            return false; // Default to deny for security
        }
    }
    
    /**
     * Check if user owns the record
     */
    private function isOwner($userId, $tableName, $recordId) {
        try {
            // Check common ownership fields
            $ownershipFields = ['user_id', 'created_by', 'owner_id', 'assigned_to'];
            
            foreach ($ownershipFields as $field) {
                $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $tableName . "` 
                        WHERE id = ? AND {$field} = ?";
                $result = $this->db->fetchOne($sql, [$recordId, $userId]);
                
                if ($result && $result['count'] > 0) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Record_permission_model isOwner error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Grant record permission to user
     */
    public function grantPermission($userId, $module, $tableName, $recordId, $permissionType, $grantedBy, $expiresAt = null) {
        try {
            // Check if permission already exists
            $existing = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE user_id = ? AND module = ? AND table_name = ? AND record_id = ? AND permission_type = ?",
                [$userId, $module, $tableName, $recordId, $permissionType]
            );
            
            $data = [
                'user_id' => $userId,
                'module' => $module,
                'table_name' => $tableName,
                'record_id' => $recordId,
                'permission_type' => $permissionType,
                'granted_by' => $grantedBy,
                'expires_at' => $expiresAt,
                'granted_at' => date('Y-m-d H:i:s')
            ];
            
            if ($existing) {
                return $this->update($existing['id'], $data);
            } else {
                return $this->create($data);
            }
        } catch (Exception $e) {
            error_log('Record_permission_model grantPermission error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all records user can access
     */
    public function getUserAccessibleRecords($userId, $module, $tableName, $action = 'read') {
        try {
            $sql = "SELECT DISTINCT record_id FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE user_id = ? AND module = ? AND table_name = ?
                    AND (expires_at IS NULL OR expires_at > NOW())";
            
            $records = $this->db->fetchAll($sql, [$userId, $module, $tableName]);
            return array_column($records, 'record_id');
        } catch (Exception $e) {
            error_log('Record_permission_model getUserAccessibleRecords error: ' . $e->getMessage());
            return [];
        }
    }
}


