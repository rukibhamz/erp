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
             WHERE code = ?",
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
    
    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        try {
            $orderBy = $orderBy ?: 'authority ASC, name ASC';
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`";
            $sql .= " ORDER BY " . $orderBy;
            
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
                if ($offset > 0) {
                    $sql .= " OFFSET " . intval($offset);
                }
            }
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Tax_type_model getAll error: ' . $e->getMessage());
            return [];
        }
    }
}

