<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_allocation_model extends Base_Model {
    protected $table = 'utility_allocations';
    
    public function getByBill($billId) {
        try {
            return $this->db->fetchAll(
                "SELECT ua.*, t.business_name as tenant_name, t.contact_person,
                        s.space_name, s.space_number
                 FROM `" . $this->db->getPrefix() . $this->table . "` ua
                 LEFT JOIN `" . $this->db->getPrefix() . "tenants` t ON ua.tenant_id = t.id
                 LEFT JOIN `" . $this->db->getPrefix() . "spaces` s ON ua.space_id = s.id
                 WHERE ua.bill_id = ?",
                [$billId]
            );
        } catch (Exception $e) {
            error_log('Utility_allocation_model getByBill error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByTenant($tenantId) {
        try {
            return $this->db->fetchAll(
                "SELECT ua.*, ub.bill_number, ub.billing_period_start, ub.billing_period_end,
                        ub.total_amount, ub.status, m.meter_number, ut.name as utility_type_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` ua
                 JOIN `" . $this->db->getPrefix() . "utility_bills` ub ON ua.bill_id = ub.id
                 JOIN `" . $this->db->getPrefix() . "meters` m ON ub.meter_id = m.id
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 WHERE ua.tenant_id = ?
                 ORDER BY ub.billing_period_start DESC",
                [$tenantId]
            );
        } catch (Exception $e) {
            error_log('Utility_allocation_model getByTenant error: ' . $e->getMessage());
            return [];
        }
    }
}

