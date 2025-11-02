<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_take_model extends Base_Model {
    protected $table = 'stock_takes';
    
    public function getNextStockTakeNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT stock_take_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE stock_take_number LIKE 'ST-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['stock_take_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "ST-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "ST-{$year}-00001";
        } catch (Exception $e) {
            error_log('Stock_take_model getNextStockTakeNumber error: ' . $e->getMessage());
            return 'ST-' . date('Y') . '-00001';
        }
    }
    
    public function getByStatus($status) {
        try {
            return $this->db->fetchAll(
                "SELECT st.*, l.location_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` st
                 LEFT JOIN `" . $this->db->getPrefix() . "locations` l ON st.location_id = l.id
                 WHERE st.status = ?
                 ORDER BY st.scheduled_date DESC",
                [$status]
            );
        } catch (Exception $e) {
            error_log('Stock_take_model getByStatus error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithDetails($id) {
        try {
            return $this->db->fetchOne(
                "SELECT st.*, l.location_name, l.location_code
                 FROM `" . $this->db->getPrefix() . $this->table . "` st
                 LEFT JOIN `" . $this->db->getPrefix() . "locations` l ON st.location_id = l.id
                 WHERE st.id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Stock_take_model getWithDetails error: ' . $e->getMessage());
            return false;
        }
    }
}

