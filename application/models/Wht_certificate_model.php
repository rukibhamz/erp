<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wht_certificate_model extends Base_Model {
    protected $table = 'wht_certificates';
    
    public function getNextCertificateNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(certificate_number, 5) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE certificate_number LIKE 'WHT-%'"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            return 'WHT-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Wht_certificate_model getNextCertificateNumber error: ' . $e->getMessage());
            return 'WHT-000001';
        }
    }
    
    public function getByBeneficiary($beneficiaryId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE beneficiary_id = ?
                 ORDER BY issue_date DESC",
                [$beneficiaryId]
            );
        } catch (Exception $e) {
            error_log('Wht_certificate_model getByBeneficiary error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function createCertificate($data) {
        try {
            return $this->db->insert($this->table, $data);
        } catch (Exception $e) {
            error_log('Wht_certificate_model createCertificate error: ' . $e->getMessage());
            return false;
        }
    }
}

