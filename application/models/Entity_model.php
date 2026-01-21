<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Entity_model extends Base_Model {
    protected $table = 'companies'; // Keep old table name for backward compatibility
    
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::create($data);
    }
    
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
    
    /**
     * Get entity by email
     */
    public function getByEmail($email) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE email = ?",
                [$email]
            );
        } catch (Exception $e) {
            error_log('Entity_model getByEmail error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get customers only
     */
    public function getCustomers() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE entity_type = 'customer' OR company_type = 'customer'
                 ORDER BY company_name ASC"
            );
        } catch (Exception $e) {
            error_log('Entity_model getCustomers error: ' . $e->getMessage());
            return [];
        }
    }
}

