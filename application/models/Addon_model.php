<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Addon_model extends Base_Model {
    protected $table = 'addons';
    
    public function getActive($resourceId = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE is_active = 1";
            $params = [];
            
            if ($resourceId) {
                $sql .= " AND (resource_id IS NULL OR resource_id = ?)";
                $params[] = $resourceId;
            }
            
            $sql .= " ORDER BY display_order ASC, name ASC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Addon_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByType($type, $resourceId = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE addon_type = ? AND is_active = 1";
            $params = [$type];
            
            if ($resourceId) {
                $sql .= " AND (resource_id IS NULL OR resource_id = ?)";
                $params[] = $resourceId;
            }
            
            $sql .= " ORDER BY display_order ASC, name ASC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Addon_model getByType error: ' . $e->getMessage());
            return [];
        }
    }
}

