<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vendor_utility_bill_model extends Base_Model {
    protected $table = 'vendor_utility_bills';
    
    public function getOverdue() {
        try {
            return $this->db->fetchAll(
                "SELECT v.*, p.provider_name, ut.name as utility_type_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` v
                 JOIN `" . $this->db->getPrefix() . "utility_providers` pr ON v.provider_id = pr.id
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON pr.utility_type_id = ut.id
                 LEFT JOIN `" . $this->db->getPrefix() . "utility_providers` p ON v.provider_id = p.id
                 WHERE v.status IN ('pending', 'verified', 'approved') 
                 AND v.due_date < CURDATE()
                 AND v.balance_amount > 0
                 ORDER BY v.due_date ASC"
            );
        } catch (Exception $e) {
            error_log('Vendor_utility_bill_model getOverdue error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithProvider($id) {
        try {
            return $this->db->fetchOne(
                "SELECT v.*, p.provider_name, p.account_number, ut.name as utility_type_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` v
                 JOIN `" . $this->db->getPrefix() . "utility_providers` pr ON v.provider_id = pr.id
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON pr.utility_type_id = ut.id
                 LEFT JOIN `" . $this->db->getPrefix() . "utility_providers` p ON v.provider_id = p.id
                 WHERE v.id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Vendor_utility_bill_model getWithProvider error: ' . $e->getMessage());
            return false;
        }
    }
}

