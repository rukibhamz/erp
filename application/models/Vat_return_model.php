<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vat_return_model extends Base_Model {
    protected $table = 'vat_returns';
    
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE id = ?",
            [$id]
        );
    }
    
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    public function getNextReturnNumber() {
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(return_number, 5) AS UNSIGNED)) as max_num 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE return_number LIKE 'VAT-%'"
        );
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return 'VAT-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    public function getByPeriod($periodStart, $periodEnd) {
        return $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE period_start = ? AND period_end = ?",
            [$periodStart, $periodEnd]
        );
    }
    
    public function getRecentReturns($limit = 10) {
        try {
            $limit = intval($limit);
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 ORDER BY period_start DESC 
                 LIMIT " . $limit
            );
        } catch (Exception $e) {
            error_log('Vat_return_model getRecentReturns error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function calculateReturn($periodStart, $periodEnd) {
        // Get output VAT (from sales)
        $outputVAT = $this->db->fetchOne(
            "SELECT COALESCE(SUM(vat_amount), 0) as total 
             FROM `" . $this->db->getPrefix() . "vat_transactions` 
             WHERE transaction_type = 'sale' 
             AND date >= ? AND date <= ?",
            [$periodStart, $periodEnd]
        );
        
        // Get input VAT (from purchases)
        $inputVAT = $this->db->fetchOne(
            "SELECT COALESCE(SUM(vat_amount), 0) as total 
             FROM `" . $this->db->getPrefix() . "vat_transactions` 
             WHERE transaction_type IN ('purchase', 'import')
             AND date >= ? AND date <= ?",
            [$periodStart, $periodEnd]
        );
        
        return [
            'output_vat' => floatval($outputVAT['total'] ?? 0),
            'input_vat' => floatval($inputVAT['total'] ?? 0),
            'net_vat' => floatval($outputVAT['total'] ?? 0) - floatval($inputVAT['total'] ?? 0)
        ];
    }
}

