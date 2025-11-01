<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tenant_model extends Base_Model {
    protected $table = 'tenants';
    
    public function getNextTenantCode() {
        try {
            $lastCode = $this->db->fetchOne(
                "SELECT tenant_code FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE tenant_code LIKE 'TEN-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastCode) {
                $number = intval(substr($lastCode['tenant_code'], 4)) + 1;
                return 'TEN-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
            return 'TEN-0001';
        } catch (Exception $e) {
            error_log('Tenant_model getNextTenantCode error: ' . $e->getMessage());
            return 'TEN-0001';
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'active' 
                 ORDER BY business_name, contact_person"
            );
        } catch (Exception $e) {
            error_log('Tenant_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithLeases($tenantId) {
        try {
            $tenant = $this->getById($tenantId);
            if (!$tenant) {
                return false;
            }
            
            // Load leases using direct query
            $tenant['leases'] = $this->db->fetchAll(
                "SELECT l.*, s.space_name, s.space_number, p.property_name 
                 FROM `" . $this->db->getPrefix() . "leases` l
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON l.space_id = s.id
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 WHERE l.tenant_id = ? 
                 ORDER BY l.start_date DESC",
                [$tenantId]
            );
            
            return $tenant;
        } catch (Exception $e) {
            error_log('Tenant_model getWithLeases error: ' . $e->getMessage());
            return false;
        }
    }
}

