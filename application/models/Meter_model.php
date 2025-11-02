<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meter_model extends Base_Model {
    protected $table = 'meters';
    
    public function getNextMeterNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT meter_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE meter_number LIKE 'MTR-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['meter_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "MTR-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "MTR-{$year}-00001";
        } catch (Exception $e) {
            error_log('Meter_model getNextMeterNumber error: ' . $e->getMessage());
            return 'MTR-' . date('Y') . '-00001';
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT m.*, ut.name as utility_type_name, ut.code as utility_type_code, ut.unit_of_measure,
                        p.property_name, s.space_name, t.business_name as tenant_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` m
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 LEFT JOIN `" . $this->db->getPrefix() . "properties` p ON m.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . "spaces` s ON m.space_id = s.id
                 LEFT JOIN `" . $this->db->getPrefix() . "tenants` t ON m.tenant_id = t.id
                 WHERE m.status = 'active' 
                 ORDER BY m.meter_number"
            );
        } catch (Exception $e) {
            error_log('Meter_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByProperty($propertyId) {
        try {
            return $this->db->fetchAll(
                "SELECT m.*, ut.name as utility_type_name, ut.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` m
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 WHERE m.property_id = ? AND m.status = 'active'
                 ORDER BY m.meter_number",
                [$propertyId]
            );
        } catch (Exception $e) {
            error_log('Meter_model getByProperty error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getBySpace($spaceId) {
        try {
            return $this->db->fetchAll(
                "SELECT m.*, ut.name as utility_type_name, ut.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` m
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 WHERE m.space_id = ? AND m.status = 'active'
                 ORDER BY m.meter_number",
                [$spaceId]
            );
        } catch (Exception $e) {
            error_log('Meter_model getBySpace error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByUtilityType($utilityTypeId) {
        try {
            return $this->db->fetchAll(
                "SELECT m.*, ut.name as utility_type_name, ut.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` m
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 WHERE m.utility_type_id = ? AND m.status = 'active'
                 ORDER BY m.meter_number",
                [$utilityTypeId]
            );
        } catch (Exception $e) {
            error_log('Meter_model getByUtilityType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getSubMeters($masterMeterId) {
        try {
            return $this->db->fetchAll(
                "SELECT m.*, ut.name as utility_type_name, ut.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` m
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 WHERE m.parent_meter_id = ? AND m.status = 'active'
                 ORDER BY m.meter_number",
                [$masterMeterId]
            );
        } catch (Exception $e) {
            error_log('Meter_model getSubMeters error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithDetails($meterId) {
        try {
            return $this->db->fetchOne(
                "SELECT m.*, ut.name as utility_type_name, ut.code as utility_type_code, ut.unit_of_measure,
                        p.property_name, p.property_code, s.space_name, s.space_number,
                        t.business_name as tenant_name, t.contact_person as tenant_contact
                 FROM `" . $this->db->getPrefix() . $this->table . "` m
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 LEFT JOIN `" . $this->db->getPrefix() . "properties` p ON m.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . "spaces` s ON m.space_id = s.id
                 LEFT JOIN `" . $this->db->getPrefix() . "tenants` t ON m.tenant_id = t.id
                 WHERE m.id = ?",
                [$meterId]
            );
        } catch (Exception $e) {
            error_log('Meter_model getWithDetails error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getLastReading($meterId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "meter_readings` 
                 WHERE meter_id = ? 
                 ORDER BY reading_date DESC, id DESC 
                 LIMIT 1",
                [$meterId]
            );
        } catch (Exception $e) {
            error_log('Meter_model getLastReading error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateLastReading($meterId, $reading, $readingDate) {
        try {
            return $this->update($meterId, [
                'last_reading' => $reading,
                'last_reading_date' => $readingDate,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Meter_model updateLastReading error: ' . $e->getMessage());
            return false;
        }
    }
}

