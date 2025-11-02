<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_type_model extends Base_Model {
    protected $table = 'tax_types';
    
    public function getAllActive() {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE is_active = 1 
             ORDER BY name ASC"
        );
    }
    
    public function getByCode($code) {
        return $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE code = ? AND is_active = 1",
            [$code]
        );
    }
    
    public function getByAuthority($authority) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE authority = ? AND is_active = 1 
             ORDER BY name ASC",
            [$authority]
        );
    }
}

