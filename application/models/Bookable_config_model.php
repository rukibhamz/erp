<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bookable_config_model extends Base_Model {
    protected $table = 'bookable_config';
    
    /**
     * Constructor - accepts optional database connection
     */
    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }
    
    /**
     * Create a new bookable config record
     */
    public function create($data) {
        try {
            error_log('Bookable_config_model create: ' . json_encode($data));
            $result = $this->db->insert($this->table, $data);
            error_log('Bookable_config_model create result: ' . var_export($result, true));
            return $result;
        } catch (Exception $e) {
            error_log('Bookable_config_model create error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a bookable config record
     */
    public function update($id, $data) {
        try {
            error_log('Bookable_config_model update ID ' . $id . ': ' . json_encode($data));
            $result = $this->db->update($this->table, $data, "`id` = ?", [$id]);
            error_log('Bookable_config_model update result: ' . var_export($result, true));
            return $result;
        } catch (Exception $e) {
            error_log('Bookable_config_model update error: ' . $e->getMessage());
            return false;
        }
    }
    
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

