<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_adjustment_model extends Base_Model {
    protected $table = 'stock_adjustments';
    
    public function getNextAdjustmentNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT adjustment_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE adjustment_number LIKE 'ADJ-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['adjustment_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "ADJ-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "ADJ-{$year}-00001";
        } catch (Exception $e) {
            error_log('Stock_adjustment_model getNextAdjustmentNumber error: ' . $e->getMessage());
            return 'ADJ-' . date('Y') . '-00001';
        }
    }
    
    public function getPending() {
        try {
            return $this->db->fetchAll(
                "SELECT sa.*, i.item_name, i.sku, l.location_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` sa
                 JOIN `" . $this->db->getPrefix() . "items` i ON sa.item_id = i.id
                 JOIN `" . $this->db->getPrefix() . "locations` l ON sa.location_id = l.id
                 WHERE sa.status = 'pending'
                 ORDER BY sa.adjustment_date DESC"
            );
        } catch (Exception $e) {
            error_log('Stock_adjustment_model getPending error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByStatus($status) {
        try {
            return $this->db->fetchAll(
                "SELECT sa.*, i.item_name, i.sku, l.location_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` sa
                 JOIN `" . $this->db->getPrefix() . "items` i ON sa.item_id = i.id
                 JOIN `" . $this->db->getPrefix() . "locations` l ON sa.location_id = l.id
                 WHERE sa.status = ?
                 ORDER BY sa.adjustment_date DESC",
                [$status]
            );
        } catch (Exception $e) {
            error_log('Stock_adjustment_model getByStatus error: ' . $e->getMessage());
            return [];
        }
    }
}

