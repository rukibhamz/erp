<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction_model extends Base_Model {
    protected $table = 'transactions';
    
    public function getByAccount($accountId, $startDate = null, $endDate = null) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE account_id = ?";
        $params = [$accountId];
        
        if ($startDate) {
            $sql .= " AND transaction_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND transaction_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY transaction_date, id";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getLedger($accountId, $startDate = null, $endDate = null) {
        $transactions = $this->getByAccount($accountId, $startDate, $endDate);
        $account = $this->loadModel('Account_model')->getById($accountId);
        
        if (!$account) return [];
        
        $runningBalance = floatval($account['opening_balance']);
        $increasesWithDebit = in_array($account['account_type'], ['Assets', 'Expenses']);
        
        foreach ($transactions as &$transaction) {
            if ($increasesWithDebit) {
                $runningBalance += floatval($transaction['debit']) - floatval($transaction['credit']);
            } else {
                $runningBalance += floatval($transaction['credit']) - floatval($transaction['debit']);
            }
            $transaction['running_balance'] = $runningBalance;
        }
        
        return $transactions;
    }
    
    public function getTrialBalance($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    a.id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    COALESCE(SUM(t.debit), 0) as total_debit,
                    COALESCE(SUM(t.credit), 0) as total_credit
                FROM `" . $this->db->getPrefix() . "accounts` a
                LEFT JOIN `" . $this->db->getPrefix() . $this->table . "` t ON a.id = t.account_id 
                    AND t.status = 'posted'";
        
        $params = [];
        if ($startDate) {
            $sql .= " AND t.transaction_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND t.transaction_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " WHERE a.status = 'active'
                GROUP BY a.id, a.account_code, a.account_name, a.account_type
                HAVING total_debit > 0 OR total_credit > 0
                ORDER BY a.account_type, a.account_code";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }
}

