<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank_transaction_model extends Base_Model {
    protected $table = 'bank_transactions';
    
    public function getByAccount($cashAccountId, $cleared = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE cash_account_id = ?";
            $params = [$cashAccountId];
            
            if ($cleared !== null) {
                $sql .= " AND cleared = ?";
                $params[] = $cleared ? 1 : 0;
            }
            
            $sql .= " ORDER BY transaction_date DESC, id DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Bank_transaction_model getByAccount error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getUncleared($cashAccountId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE cash_account_id = ? AND cleared = 0
                 ORDER BY transaction_date DESC",
                [$cashAccountId]
            );
        } catch (Exception $e) {
            error_log('Bank_transaction_model getUncleared error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function markCleared($transactionId, $reconciliationId = null) {
        try {
            return $this->update($transactionId, [
                'cleared' => 1,
                'cleared_date' => date('Y-m-d'),
                'reconciliation_id' => $reconciliationId
            ]);
        } catch (Exception $e) {
            error_log('Bank_transaction_model markCleared error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function markUncleared($transactionId) {
        try {
            return $this->update($transactionId, [
                'cleared' => 0,
                'cleared_date' => null,
                'reconciliation_id' => null
            ]);
        } catch (Exception $e) {
            error_log('Bank_transaction_model markUncleared error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getBalance($cashAccountId, $date = null) {
        try {
            $date = $date ?: date('Y-m-d');
            
            $deposits = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE cash_account_id = ? 
                 AND transaction_type IN ('deposit', 'transfer')
                 AND transaction_date <= ?",
                [$cashAccountId, $date]
            );
            
            $withdrawals = $this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE cash_account_id = ? 
                 AND transaction_type IN ('withdrawal', 'fee')
                 AND transaction_date <= ?",
                [$cashAccountId, $date]
            );
            
            $depositTotal = $deposits ? floatval($deposits['total']) : 0;
            $withdrawalTotal = $withdrawals ? floatval($withdrawals['total']) : 0;
            
            return $depositTotal - $withdrawalTotal;
        } catch (Exception $e) {
            error_log('Bank_transaction_model getBalance error: ' . $e->getMessage());
            return 0;
        }
    }
}

