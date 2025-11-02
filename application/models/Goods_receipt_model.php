<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_receipt_model extends Base_Model {
    protected $table = 'goods_receipts';
    
    public function getNextGRNNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT grn_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE grn_number LIKE 'GRN-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['grn_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "GRN-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "GRN-{$year}-00001";
        } catch (Exception $e) {
            error_log('Goods_receipt_model getNextGRNNumber error: ' . $e->getMessage());
            return 'GRN-' . date('Y') . '-00001';
        }
    }
    
    public function getWithDetails($id) {
        try {
            return $this->db->fetchOne(
                "SELECT gr.*, s.supplier_name, po.po_number, l.location_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` gr
                 LEFT JOIN `" . $this->db->getPrefix() . "suppliers` s ON gr.supplier_id = s.id
                 LEFT JOIN `" . $this->db->getPrefix() . "purchase_orders` po ON gr.po_id = po.id
                 LEFT JOIN `" . $this->db->getPrefix() . "locations` l ON gr.location_id = l.id
                 WHERE gr.id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Goods_receipt_model getWithDetails error: ' . $e->getMessage());
            return false;
        }
    }
}

