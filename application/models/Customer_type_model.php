<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_type_model extends Base_Model {
    protected $table = 'customer_types';
    
    public function getActive() {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE is_active = 1"
        );
    }
    
    public function getByCode($code) {
        return $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE code = ?",
            [$code]
        );
    }
    
    public function getAllActive() {
        // Alias for getActive() - used by Receivables controller
        return $this->getActive();
    }
}
