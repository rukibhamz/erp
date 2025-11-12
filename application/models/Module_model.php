<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Module_model extends Base_Model {
    protected $table = 'modules';
    
    /**
     * Get all modules
     * Override parent method to add activeOnly filter
     */
    public function getAll($limit = null, $offset = 0, $orderBy = null, $activeOnly = false) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`";
            
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            
            // Use provided orderBy or default
            // SECURITY: Validate ORDER BY to prevent SQL injection
            if ($orderBy) {
                // Whitelist approach for ORDER BY
                $allowedOrderColumns = ['sort_order', 'display_name', 'module_key', 'is_active', 'created_at', 'id'];
                $allowedOrderDirections = ['ASC', 'DESC'];
                $orderByParts = explode(' ', trim($orderBy));
                $orderColumn = $orderByParts[0] ?? 'sort_order';
                $orderDirection = strtoupper($orderByParts[1] ?? 'ASC');
                
                // Only allow whitelisted columns and directions
                if (!in_array($orderColumn, $allowedOrderColumns)) {
                    $orderColumn = 'sort_order';
                }
                if (!in_array($orderDirection, $allowedOrderDirections)) {
                    $orderDirection = 'ASC';
                }
                $safeOrderBy = "{$orderColumn} {$orderDirection}";
                $sql .= " ORDER BY {$safeOrderBy}";
            } else {
                $sql .= " ORDER BY sort_order ASC, display_name ASC";
            }
            
            if ($limit) {
                // Validate and sanitize limit and offset to prevent SQL injection
                $limit = max(1, min(10000, intval($limit)));
                $offset = max(0, intval($offset));
                $sql .= " LIMIT ? OFFSET ?";
                $params = [$limit, $offset];
                return $this->db->fetchAll($sql, $params);
            }
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Module_model getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all modules (convenience method with activeOnly as first param)
     */
    public function getAllModules($activeOnly = false) {
        return $this->getAll(null, 0, null, $activeOnly);
    }
    
    /**
     * Get module by key
     */
    public function getByKey($moduleKey) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE module_key = ?";
            return $this->db->fetchOne($sql, [$moduleKey]);
        } catch (Exception $e) {
            error_log('Module_model getByKey error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if module is active
     */
    public function isActive($moduleKey) {
        try {
            $module = $this->getByKey($moduleKey);
            return $module && $module['is_active'] == 1;
        } catch (Exception $e) {
            error_log('Module_model isActive error: ' . $e->getMessage());
            return true; // Default to active if check fails
        }
    }
    
    /**
     * Activate/deactivate module
     */
    public function setActive($moduleKey, $isActive) {
        try {
            return $this->db->update(
                $this->table,
                ['is_active' => $isActive ? 1 : 0],
                "module_key = ?",
                [$moduleKey]
            );
        } catch (Exception $e) {
            error_log('Module_model setActive error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update module display name
     */
    public function updateDisplayName($moduleKey, $displayName) {
        try {
            return $this->db->update(
                $this->table,
                ['display_name' => sanitize_input($displayName)],
                "module_key = ?",
                [$moduleKey]
            );
        } catch (Exception $e) {
            error_log('Module_model updateDisplayName error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update module details
     */
    public function updateModule($moduleKey, $data) {
        try {
            $allowedFields = ['display_name', 'description', 'icon', 'sort_order'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = sanitize_input($data[$field]);
                }
            }
            
            if (empty($updateData)) {
                return false;
            }
            
            return $this->db->update(
                $this->table,
                $updateData,
                "module_key = ?",
                [$moduleKey]
            );
        } catch (Exception $e) {
            error_log('Module_model updateModule error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get active modules for navigation
     */
    public function getActiveModules() {
        try {
            $sql = "SELECT module_key, display_name, icon FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE is_active = 1 
                    ORDER BY sort_order ASC, display_name ASC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Module_model getActiveModules error: ' . $e->getMessage());
            return [];
        }
    }
}



