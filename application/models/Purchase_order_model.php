<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase_order_model extends Base_Model {
    protected $table = 'purchase_orders';
    
    public function getNextPONumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT po_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE po_number LIKE 'PO-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['po_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "PO-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "PO-{$year}-00001";
        } catch (Exception $e) {
            error_log('Purchase_order_model getNextPONumber error: ' . $e->getMessage());
            return 'PO-' . date('Y') . '-00001';
        }
    }
    
    public function getWithSupplier($id) {
        try {
            return $this->db->fetchOne(
                "SELECT po.*, s.supplier_name, s.supplier_code, s.contact_person, s.email, s.phone
                 FROM `" . $this->db->getPrefix() . $this->table . "` po
                 JOIN `" . $this->db->getPrefix() . "suppliers` s ON po.supplier_id = s.id
                 WHERE po.id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Purchase_order_model getWithSupplier error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByStatus($status) {
        try {
            return $this->db->fetchAll(
                "SELECT po.*, s.supplier_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` po
                 JOIN `" . $this->db->getPrefix() . "suppliers` s ON po.supplier_id = s.id
                 WHERE po.status = ?
                 ORDER BY po.order_date DESC",
                [$status]
            );
        } catch (Exception $e) {
            error_log('Purchase_order_model getByStatus error: ' . $e->getMessage());
            return [];
        }
    }
}

