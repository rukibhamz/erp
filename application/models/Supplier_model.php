<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Supplier_model extends Base_Model {
    protected $table = 'suppliers';
    
    public function getNextSupplierCode($prefix = 'SUP') {
        try {
            $year = date('Y');
            $lastCode = $this->db->fetchOne(
                "SELECT supplier_code FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE supplier_code LIKE '{$prefix}-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastCode) {
                $parts = explode('-', $lastCode['supplier_code']);
                $number = intval($parts[2] ?? 0) + 1;
                return "{$prefix}-{$year}-" . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
            return "{$prefix}-{$year}-0001";
        } catch (Exception $e) {
            error_log('Supplier_model getNextSupplierCode error: ' . $e->getMessage());
            return $prefix . '-' . date('Y') . '-0001';
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_active = 1 
                 ORDER BY supplier_name"
            );
        } catch (Exception $e) {
            error_log('Supplier_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
}

