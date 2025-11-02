<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_provider_model extends Base_Model {
    protected $table = 'utility_providers';
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT p.*, ut.name as utility_type_name, ut.code as utility_type_code
                 FROM `" . $this->db->getPrefix() . $this->table . "` p
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON p.utility_type_id = ut.id
                 WHERE p.is_active = 1 
                 ORDER BY p.provider_name"
            );
        } catch (Exception $e) {
            error_log('Utility_provider_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByUtilityType($utilityTypeId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE utility_type_id = ? AND is_active = 1 
                 ORDER BY provider_name",
                [$utilityTypeId]
            );
        } catch (Exception $e) {
            error_log('Utility_provider_model getByUtilityType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithUtilityType($id) {
        try {
            return $this->db->fetchOne(
                "SELECT p.*, ut.name as utility_type_name, ut.code as utility_type_code, ut.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` p
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON p.utility_type_id = ut.id
                 WHERE p.id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Utility_provider_model getWithUtilityType error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        return parent::getById($id);
    }
}

