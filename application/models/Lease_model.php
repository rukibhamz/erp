<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lease_model extends Base_Model {
    protected $table = 'leases';
    
    public function getNextLeaseNumber() {
        try {
            $lastNumber = $this->db->fetchOne(
                "SELECT lease_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE lease_number LIKE 'LEASE-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $number = intval(substr($lastNumber['lease_number'], 6)) + 1;
                return 'LEASE-' . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return 'LEASE-00001';
        } catch (Exception $e) {
            error_log('Lease_model getNextLeaseNumber error: ' . $e->getMessage());
            return 'LEASE-00001';
        }
    }
    
    public function getByTenant($tenantId) {
        try {
            return $this->db->fetchAll(
                "SELECT l.*, s.space_name, s.space_number, p.property_name 
                 FROM `" . $this->db->getPrefix() . $this->table . "` l
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON l.space_id = s.id
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 WHERE l.tenant_id = ? 
                 ORDER BY l.start_date DESC",
                [$tenantId]
            );
        } catch (Exception $e) {
            error_log('Lease_model getByTenant error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getBySpace($spaceId) {
        try {
            return $this->db->fetchAll(
                "SELECT l.*, t.business_name, t.contact_person, t.email, t.phone 
                 FROM `" . $this->db->getPrefix() . $this->table . "` l
                 JOIN `" . $this->db->getPrefix() . "tenants` t ON l.tenant_id = t.id
                 WHERE l.space_id = ? 
                 ORDER BY l.start_date DESC",
                [$spaceId]
            );
        } catch (Exception $e) {
            error_log('Lease_model getBySpace error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT l.*, s.space_name, p.property_name, t.business_name, t.contact_person 
                 FROM `" . $this->db->getPrefix() . $this->table . "` l
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON l.space_id = s.id
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 JOIN `" . $this->db->getPrefix() . "tenants` t ON l.tenant_id = t.id
                 WHERE l.status = 'active' 
                 ORDER BY l.end_date ASC",
                []
            );
        } catch (Exception $e) {
            error_log('Lease_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getExpiring($daysAhead = 90) {
        try {
            $date = date('Y-m-d', strtotime("+{$daysAhead} days"));
            return $this->db->fetchAll(
                "SELECT l.*, s.space_name, p.property_name, t.business_name, t.contact_person 
                 FROM `" . $this->db->getPrefix() . $this->table . "` l
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON l.space_id = s.id
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 JOIN `" . $this->db->getPrefix() . "tenants` t ON l.tenant_id = t.id
                 WHERE l.status = 'active' 
                 AND l.end_date IS NOT NULL 
                 AND l.end_date <= ? 
                 AND l.end_date >= CURDATE()
                 ORDER BY l.end_date ASC",
                [$date]
            );
        } catch (Exception $e) {
            error_log('Lease_model getExpiring error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getWithDetails($leaseId) {
        try {
            return $this->db->fetchOne(
                "SELECT l.*, 
                        s.space_name, s.space_number, s.area, s.category,
                        p.property_name, p.property_code, p.address,
                        t.business_name, t.contact_person, t.email, t.phone, t.tenant_type
                 FROM `" . $this->db->getPrefix() . $this->table . "` l
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON l.space_id = s.id
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 JOIN `" . $this->db->getPrefix() . "tenants` t ON l.tenant_id = t.id
                 WHERE l.id = ?",
                [$leaseId]
            );
        } catch (Exception $e) {
            error_log('Lease_model getWithDetails error: ' . $e->getMessage());
            return false;
        }
    }
}

