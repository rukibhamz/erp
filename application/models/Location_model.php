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
            $locations = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'operational' 
                 ORDER BY property_name"
            );
            // Map fields for views
            return array_map([$this, 'mapFieldsForView'], $locations);
        } catch (Exception $e) {
            error_log('Location_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Override getAll to map fields for views
     */
    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        $locations = parent::getAll($limit, $offset, $orderBy);
        // Map fields for views
        return array_map([$this, 'mapFieldsForView'], $locations);
    }
    
    /**
     * Override getById to map fields for views
     */
    public function getById($id) {
        $location = parent::getById($id);
        if ($location) {
            return $this->mapFieldsForView($location);
        }
        return $location;
    }
    
    public function getWithSpaces($locationId) {
        try {
            $location = $this->getById($locationId);
            if (!$location) {
                return false;
            }
            
            // Map database fields to view fields for backward compatibility
            $location = $this->mapFieldsForView($location);
            
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
    
    /**
     * Map database field names to view field names for backward compatibility
     * Maps property_* to Location_* for views
     */
    public function mapFieldsForView($location) {
        if (isset($location['property_code'])) {
            $location['Location_code'] = $location['property_code'];
            $location['location_code'] = $location['property_code']; // Lowercase for consistency
        }
        if (isset($location['property_name'])) {
            $location['Location_name'] = $location['property_name'];
            $location['location_name'] = $location['property_name']; // Lowercase for consistency
        }
        if (isset($location['property_type'])) {
            $location['Location_type'] = $location['property_type'];
            $location['location_type'] = $location['property_type']; // Lowercase for consistency
        }
        if (isset($location['property_value'])) {
            $location['Location_value'] = $location['property_value'];
            $location['location_value'] = $location['property_value']; // Lowercase for consistency
        }
        return $location;
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
