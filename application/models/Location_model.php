<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location_model extends Base_Model {
    protected $table = 'properties'; // Keep old table name for backward compatibility
    
    public function getNextPropertyCode() {
        try {
            $lastCode = $this->db->fetchOne(
                "SELECT property_code FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE property_code LIKE 'PROP-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastCode) {
                $number = intval(substr($lastCode['property_code'], 5)) + 1;
                return 'PROP-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
            return 'PROP-0001';
        } catch (Exception $e) {
            error_log('Location_model getNextPropertyCode error: ' . $e->getMessage());
            return 'PROP-0001';
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'operational' 
                 ORDER BY property_name"
            );
        } catch (Exception $e) {
            error_log('Location_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithSpaces($locationId) {
        try {
            $location = $this->getById($locationId);
            if (!$location) {
                return false;
            }
            
            // Load spaces for this location using direct query
            $location['spaces'] = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "spaces` 
                 WHERE property_id = ? 
                 ORDER BY space_number, space_name",
                [$locationId]
            );
            
            return $location;
        } catch (Exception $e) {
            error_log('Location_model getWithSpaces error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getBookableSpaces($locationId = null) {
        try {
            $sql = "SELECT s.*, p.property_name, p.property_code 
                    FROM `" . $this->db->getPrefix() . "spaces` s
                    JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                    WHERE s.is_bookable = 1 AND s.operational_status = 'active'";
            $params = [];
            
            if ($locationId) {
                $sql .= " AND s.property_id = ?";
                $params[] = $locationId;
            }
            
            $sql .= " ORDER BY p.property_name, s.space_name";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Location_model getBookableSpaces error: ' . $e->getMessage());
            return [];
        }
    }
}
