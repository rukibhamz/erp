<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Property_model extends Base_Model {
    protected $table = 'properties';
    
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
            error_log('Property_model getNextPropertyCode error: ' . $e->getMessage());
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
            error_log('Property_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithSpaces($propertyId) {
        try {
            $property = $this->getById($propertyId);
            if (!$property) {
                return false;
            }
            
            // Load spaces for this property using direct query
            $property['spaces'] = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "spaces` 
                 WHERE property_id = ? 
                 ORDER BY space_number, space_name",
                [$propertyId]
            );
            
            return $property;
        } catch (Exception $e) {
            error_log('Property_model getWithSpaces error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getBookableSpaces($propertyId = null) {
        try {
            $sql = "SELECT s.*, p.property_name, p.property_code 
                    FROM `" . $this->db->getPrefix() . "spaces` s
                    JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                    WHERE s.is_bookable = 1 AND s.operational_status = 'active'";
            $params = [];
            
            if ($propertyId) {
                $sql .= " AND s.property_id = ?";
                $params[] = $propertyId;
            }
            
            $sql .= " ORDER BY p.property_name, s.space_name";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Property_model getBookableSpaces error: ' . $e->getMessage());
            return [];
        }
    }
}

