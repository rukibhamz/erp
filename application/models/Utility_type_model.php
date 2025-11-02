<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_type_model extends Base_Model {
    protected $table = 'utility_types';
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_active = 1 
                 ORDER BY name"
            );
        } catch (Exception $e) {
            error_log('Utility_type_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByCode($code) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE code = ?",
                [$code]
            );
        } catch (Exception $e) {
            error_log('Utility_type_model getByCode error: ' . $e->getMessage());
            return false;
        }
    }
}

