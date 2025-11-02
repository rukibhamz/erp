<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wht_return_model extends Base_Model {
    protected $table = 'wht_returns';
    
    public function getById($id) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Wht_return_model getById error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByPeriod($month, $year) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE month = ? AND year = ?",
                [$month, $year]
            );
        } catch (Exception $e) {
            error_log('Wht_return_model getByPeriod error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentReturns($limit = 10) {
        try {
            $limit = intval($limit);
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 ORDER BY year DESC, month DESC 
                 LIMIT " . $limit
            );
        } catch (Exception $e) {
            error_log('Wht_return_model getRecentReturns error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getNextReturnNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(return_number, 5) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE return_number LIKE 'WHT-%'"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            return 'WHT-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Wht_return_model getNextReturnNumber error: ' . $e->getMessage());
            return 'WHT-000001';
        }
    }
    
    public function calculateReturn($month, $year) {
        try {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            $whtTransactions = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "wht_transactions` 
                 WHERE date >= ? AND date <= ?
                 ORDER BY date ASC",
                [$startDate, $endDate]
            );
            
            $totalWHT = 0;
            $byType = [];
            foreach ($whtTransactions as $transaction) {
                $amount = floatval($transaction['wht_amount'] ?? 0);
                $totalWHT += $amount;
                $type = $transaction['wht_type'] ?? 'other';
                if (!isset($byType[$type])) {
                    $byType[$type] = 0;
                }
                $byType[$type] += $amount;
            }
            
            return [
                'total_wht' => $totalWHT,
                'transaction_count' => count($whtTransactions),
                'transactions' => $whtTransactions,
                'by_type' => $byType
            ];
        } catch (Exception $e) {
            error_log('Wht_return_model calculateReturn error: ' . $e->getMessage());
            return [
                'total_wht' => 0,
                'transaction_count' => 0,
                'transactions' => [],
                'by_type' => []
            ];
        }
    }
    
    public function create($data) {
        try {
            return $this->db->insert($this->table, $data);
        } catch (Exception $e) {
            error_log('Wht_return_model create error: ' . $e->getMessage());
            return false;
        }
    }
}

