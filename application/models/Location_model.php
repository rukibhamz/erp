<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location_model extends Base_Model {
    protected $table = 'locations';
    
    public function getNextLocationCode($prefix = 'LOC') {
        try {
            $year = date('Y');
            $lastCode = $this->db->fetchOne(
                "SELECT location_code FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE location_code LIKE '{$prefix}-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastCode) {
                $parts = explode('-', $lastCode['location_code']);
                $number = intval($parts[2] ?? 0) + 1;
                return "{$prefix}-{$year}-" . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
            return "{$prefix}-{$year}-0001";
        } catch (Exception $e) {
            error_log('Location_model getNextLocationCode error: ' . $e->getMessage());
            return $prefix . '-' . date('Y') . '-0001';
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_active = 1 
                 ORDER BY location_name"
            );
        } catch (Exception $e) {
            error_log('Location_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getHierarchy($parentId = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE is_active = 1";
            
            $params = [];
            if ($parentId === null) {
                $sql .= " AND parent_id IS NULL";
            } else {
                $sql .= " AND parent_id = ?";
                $params[] = $parentId;
            }
            
            $sql .= " ORDER BY location_name";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Location_model getHierarchy error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getChildren($parentId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE parent_id = ? AND is_active = 1
                 ORDER BY location_name",
                [$parentId]
            );
        } catch (Exception $e) {
            error_log('Location_model getChildren error: ' . $e->getMessage());
            return [];
        }
    }
}

