<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paye_return_model extends Base_Model {
    protected $table = 'paye_returns';
    
    public function getNextReturnNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(return_number, 6) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE return_number LIKE 'PAYE-%'"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            return 'PAYE-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Paye_return_model getNextReturnNumber error: ' . $e->getMessage());
            return 'PAYE-000001';
        }
    }
    
    public function getByPeriod($period) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE period = ?",
                [$period]
            );
        } catch (Exception $e) {
            error_log('Paye_return_model getByPeriod error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentReturns($limit = 12) {
        try {
            $limit = intval($limit);
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 ORDER BY period DESC 
                 LIMIT " . $limit
            );
        } catch (Exception $e) {
            error_log('Paye_return_model getRecentReturns error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function create($data) {
        try {
            return $this->db->insert($this->table, $data);
        } catch (Exception $e) {
            error_log('Paye_return_model create error: ' . $e->getMessage());
            return false;
        }
    }
}



