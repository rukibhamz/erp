<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bookable_config_model extends Base_Model {
    protected $table = 'bookable_config';
    
    public function getBySpace($spaceId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE space_id = ?",
                [$spaceId]
            );
        } catch (Exception $e) {
            error_log('Bookable_config_model getBySpace error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete bookable config by conditions
     */
    public function deleteBy($conditions) {
        try {
            $where = [];
            $params = [];
            foreach ($conditions as $key => $value) {
                $where[] = "`$key` = ?";
                $params[] = $value;
            }
            
            return $this->db->delete(
                $this->table,
                implode(' AND ', $where),
                $params
            );
        } catch (Exception $e) {
            error_log('Bookable_config_model deleteBy error: ' . $e->getMessage());
            return false;
        }
    }
}

