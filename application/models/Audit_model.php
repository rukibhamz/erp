<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit_model extends Base_Model {
    protected $table = 'audit_trail';
    
    /**
     * Log an audit trail entry with before/after values
     */
    public function logChange($userId, $action, $module, $recordId = null, $tableName = null, $oldData = null, $newData = null, $description = null, $metadata = null) {
        try {
            // Calculate changes
            $changes = [];
            $fieldChanges = [];
            
            if ($oldData && $newData && is_array($oldData) && is_array($newData)) {
                foreach ($newData as $field => $newValue) {
                    $oldValue = $oldData[$field] ?? null;
                    
                    // Only log if value actually changed
                    if ($oldValue !== $newValue) {
                        $fieldChanges[$field] = [
                            'old' => $oldValue,
                            'new' => $newValue
                        ];
                        
                        // Store as separate record for field-level tracking
                        $this->logFieldChange($userId, $action, $module, $recordId, $tableName, $field, $oldValue, $newValue);
                    }
                }
            }
            
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'table_name' => $tableName,
                'changes_json' => !empty($fieldChanges) ? json_encode($fieldChanges) : null,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'metadata' => $metadata ? json_encode($metadata) : null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->create($data);
        } catch (Exception $e) {
            error_log('Audit_model logChange error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log a field-level change
     */
    private function logFieldChange($userId, $action, $module, $recordId, $tableName, $fieldName, $oldValue, $newValue) {
        try {
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'table_name' => $tableName,
                'field_name' => $fieldName,
                'old_value' => is_array($oldValue) ? json_encode($oldValue) : (string)$oldValue,
                'new_value' => is_array($newValue) ? json_encode($newValue) : (string)$newValue,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->create($data);
        } catch (Exception $e) {
            error_log('Audit_model logFieldChange error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get audit trail for a specific record
     */
    public function getRecordHistory($module, $recordId, $limit = 100) {
        try {
            $sql = "SELECT a.*, u.username, u.email, u.first_name, u.last_name
                    FROM `" . $this->db->getPrefix() . $this->table . "` a
                    LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id
                    WHERE a.module = ? AND a.record_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [$module, $recordId]);
        } catch (Exception $e) {
            error_log('Audit_model getRecordHistory error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audit trail by user
     */
    public function getUserAuditTrail($userId, $limit = 100) {
        try {
            $sql = "SELECT a.*, u.username, u.email
                    FROM `" . $this->db->getPrefix() . $this->table . "` a
                    LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id
                    WHERE a.user_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [$userId]);
        } catch (Exception $e) {
            error_log('Audit_model getUserAuditTrail error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audit trail by module
     */
    public function getModuleAuditTrail($module, $limit = 100) {
        try {
            $sql = "SELECT a.*, u.username, u.email
                    FROM `" . $this->db->getPrefix() . $this->table . "` a
                    LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id
                    WHERE a.module = ?
                    ORDER BY a.created_at DESC
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [$module]);
        } catch (Exception $e) {
            error_log('Audit_model getModuleAuditTrail error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audit trail with filters
     */
    public function getAuditTrail($filters = [], $limit = 100, $offset = 0) {
        try {
            $sql = "SELECT a.*, u.username, u.email, u.first_name, u.last_name
                    FROM `" . $this->db->getPrefix() . $this->table . "` a
                    LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id";
            
            $where = [];
            $params = [];
            
            if (!empty($filters['user_id'])) {
                $where[] = "a.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['module'])) {
                $where[] = "a.module = ?";
                $params[] = $filters['module'];
            }
            
            if (!empty($filters['action'])) {
                $where[] = "a.action = ?";
                $params[] = $filters['action'];
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = "DATE(a.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "DATE(a.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['record_id'])) {
                $where[] = "a.record_id = ?";
                $params[] = $filters['record_id'];
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $sql .= " ORDER BY a.created_at DESC LIMIT {$limit} OFFSET {$offset}";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Audit_model getAuditTrail error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audit summary statistics
     */
    public function getAuditSummary($dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        DATE(created_at) as audit_date,
                        action,
                        module,
                        COUNT(*) as action_count,
                        COUNT(DISTINCT user_id) as unique_users,
                        COUNT(DISTINCT record_id) as records_affected
                    FROM `" . $this->db->getPrefix() . $this->table . "`";
            
            $where = [];
            $params = [];
            
            if ($dateFrom) {
                $where[] = "DATE(created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $where[] = "DATE(created_at) <= ?";
                $params[] = $dateTo;
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $sql .= " GROUP BY DATE(created_at), action, module
                      ORDER BY audit_date DESC, action_count DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Audit_model getAuditSummary error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get changes for a specific field
     */
    public function getFieldHistory($tableName, $recordId, $fieldName, $limit = 50) {
        try {
            $sql = "SELECT a.*, u.username, u.email
                    FROM `" . $this->db->getPrefix() . $this->table . "` a
                    LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id
                    WHERE a.table_name = ? AND a.record_id = ? AND a.field_name = ?
                    ORDER BY a.created_at DESC
                    LIMIT {$limit}";
            
            return $this->db->fetchAll($sql, [$tableName, $recordId, $fieldName]);
        } catch (Exception $e) {
            error_log('Audit_model getFieldHistory error: ' . $e->getMessage());
            return [];
        }
    }
}
