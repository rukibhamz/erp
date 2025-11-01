<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Work_order_model extends Base_Model {
    protected $table = 'work_orders';
    
    public function getNextWorkOrderNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT work_order_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE work_order_number LIKE 'WO-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['work_order_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "WO-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "WO-{$year}-00001";
        } catch (Exception $e) {
            error_log('Work_order_model getNextWorkOrderNumber error: ' . $e->getMessage());
            return 'WO-' . date('Y') . '-00001';
        }
    }
    
    public function getByStatus($status) {
        try {
            return $this->db->fetchAll(
                "SELECT wo.*, s.space_name, p.property_name, mr.request_number
                 FROM `" . $this->db->getPrefix() . $this->table . "` wo
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON wo.space_id = s.id
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . "maintenance_requests` mr ON wo.request_id = mr.id
                 WHERE wo.status = ? 
                 ORDER BY wo.scheduled_date ASC, wo.created_at DESC",
                [$status]
            );
        } catch (Exception $e) {
            error_log('Work_order_model getByStatus error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByVendor($vendorId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE vendor_id = ? 
                 ORDER BY scheduled_date DESC",
                [$vendorId]
            );
        } catch (Exception $e) {
            error_log('Work_order_model getByVendor error: ' . $e->getMessage());
            return [];
        }
    }
}

