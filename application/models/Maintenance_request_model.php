<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Maintenance_request_model extends Base_Model {
    protected $table = 'maintenance_requests';
    
    public function getNextRequestNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT request_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE request_number LIKE 'MREQ-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['request_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "MREQ-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "MREQ-{$year}-00001";
        } catch (Exception $e) {
            error_log('Maintenance_request_model getNextRequestNumber error: ' . $e->getMessage());
            return 'MREQ-' . date('Y') . '-00001';
        }
    }
    
    public function getByStatus($status) {
        try {
            return $this->db->fetchAll(
                "SELECT mr.*, s.space_name, s.space_number, p.property_name, 
                        t.business_name, t.contact_person
                 FROM `" . $this->db->getPrefix() . $this->table . "` mr
                 JOIN `" . $this->db->getPrefix() . "spaces` s ON mr.space_id = s.id
                 JOIN `" . $this->db->getPrefix() . "properties` p ON s.property_id = p.id
                 LEFT JOIN `" . $this->db->getPrefix() . "tenants` t ON mr.tenant_id = t.id
                 WHERE mr.status = ? 
                 ORDER BY 
                     CASE mr.priority 
                         WHEN 'emergency' THEN 1 
                         WHEN 'high' THEN 2 
                         WHEN 'medium' THEN 3 
                         ELSE 4 
                     END,
                     mr.reported_date DESC",
                [$status]
            );
        } catch (Exception $e) {
            error_log('Maintenance_request_model getByStatus error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getBySpace($spaceId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE space_id = ? 
                 ORDER BY reported_date DESC",
                [$spaceId]
            );
        } catch (Exception $e) {
            error_log('Maintenance_request_model getBySpace error: ' . $e->getMessage());
            return [];
        }
    }
}

