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
    


    public function getBookable() {
        try {
            // Try 1: Locations explicitly marked as bookable
            try {
                $locations = $this->db->fetchAll(
                    "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                     WHERE status = 'operational' AND is_bookable = 1
                     ORDER BY property_name"
                );
            } catch (Exception $e) {
                // is_bookable column may not exist
                $locations = [];
            }
            
            // Try 2: Locations that have bookable spaces under them
            if (empty($locations)) {
                $locations = $this->db->fetchAll(
                    "SELECT DISTINCT p.* FROM `" . $this->db->getPrefix() . $this->table . "` p
                     INNER JOIN `" . $this->db->getPrefix() . "spaces` s ON s.property_id = p.id
                     WHERE p.status = 'operational' AND s.is_bookable = 1
                     ORDER BY p.property_name"
                );
            }
            
            // Try 3: All operational locations (last resort)
            if (empty($locations)) {
                $locations = $this->db->fetchAll(
                    "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                     WHERE status = 'operational'
                     ORDER BY property_name"
                );
            }
            
            return array_map([$this, 'mapFieldsForView'], $locations);
        } catch (Exception $e) {
            error_log('Location_model getBookable error: ' . $e->getMessage());
            return [];
        }
    }

    public function create($data) {
        $id = parent::create($data);
        if ($id && !empty($data['is_bookable'])) {
            $this->syncToBookingModule($id);
        }
        return $id;
    }

    public function update($id, $data) {
        $result = parent::update($id, $data);
        if ($result && isset($data['is_bookable'])) {
            if ($data['is_bookable']) {
                $this->syncToBookingModule($id);
            } else {
                // Optional: Disable facility if no longer bookable?
                // For now, we just leave it.
            }
        }
        return $result;
    }

    public function syncToBookingModule($locationId) {
        try {
            $location = $this->getById($locationId);
            if (!$location) return false;

            // Check if already has facility_id
            if (!empty($location['facility_id'])) {
                // Update existing facility
                $this->db->update('facilities', [
                    'facility_name' => $location['Location_name'],
                    'facility_code' => $location['Location_code'],
                    // 'description' => $location['description'] ?? '', // Location doesn't have description in view map, but maybe in DB?
                    'status' => 'active'
                ], ['id' => $location['facility_id']]);
                return $location['facility_id'];
            }

            // Check if facility exists with same code (to avoid duplicates)
            $existing = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . "facilities` WHERE facility_code = ?",
                [$location['Location_code']]
            );

            if ($existing) {
                $facilityId = $existing['id'];
            } else {
                // Create new facility
                $this->db->insert('facilities', [
                    'facility_code' => $location['Location_code'],
                    'facility_name' => $location['Location_name'],
                    'description' => 'Auto-generated from Location',
                    'resource_type' => 'other', // Default
                    'status' => 'active',
                    'is_bookable' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $facilityId = $this->db->lastInsertId();
            }

            // Update location with facility_id
            $this->db->update($this->table, ['facility_id' => $facilityId], ['id' => $locationId]);

            // Create default "Whole Location" space if no spaces exist
            $existingSpace = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . "spaces` WHERE property_id = ?",
                [$locationId]
            );

            if (!$existingSpace) {
                $this->db->insert('spaces', [
                    'property_id' => $locationId,
                    'facility_id' => $facilityId,
                    'space_name' => 'Whole Location',
                    'space_number' => $location['Location_code'] . '-MAIN',
                    'space_type' => 'entire_property',
                    'capacity' => 0, // Should be updated by user
                    'is_bookable' => 1,
                    'operational_status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            return $facilityId;
        } catch (Exception $e) {
            error_log('Location_model syncToBookingModule error: ' . $e->getMessage());
            return false;
        }
    }
}
