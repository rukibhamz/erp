<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wht_transaction_model extends Base_Model {
    protected $table = 'wht_transactions';
    
    public function getByPeriod($startDate, $endDate) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE date >= ? AND date <= ?
                 ORDER BY date DESC",
                [$startDate, $endDate]
            );
        } catch (Exception $e) {
            error_log('Wht_transaction_model getByPeriod error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByBeneficiary($beneficiaryId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE beneficiary_id = ?
                 ORDER BY date DESC",
                [$beneficiaryId]
            );
        } catch (Exception $e) {
            error_log('Wht_transaction_model getByBeneficiary error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function createTransaction($data) {
        try {
            return $this->db->insert($this->table, $data);
        } catch (Exception $e) {
            error_log('Wht_transaction_model createTransaction error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getTotalByPeriod($startDate, $endDate) {
        try {
            $result = $this->db->fetchOne(
                "SELECT COALESCE(SUM(wht_amount), 0) as total, COUNT(*) as count
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE date >= ? AND date <= ?",
                [$startDate, $endDate]
            );
            return [
                'total_wht' => floatval($result['total'] ?? 0),
                'transaction_count' => intval($result['count'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log('Wht_transaction_model getTotalByPeriod error: ' . $e->getMessage());
            return ['total_wht' => 0, 'transaction_count' => 0];
        }
    }
}

