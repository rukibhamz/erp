<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account_model extends Base_Model {
    protected $table = 'accounts';
    
    public function getByType($type) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE account_type = ? AND status = 'active' ORDER BY account_code",
                [$type]
            );
        } catch (Exception $e) {
            error_log('Account_model getByType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getHierarchy($parentId = null) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE ";
        $params = [];
        
        if ($parentId === null) {
            $sql .= "parent_id IS NULL";
        } else {
            $sql .= "parent_id = ?";
            $params[] = $parentId;
        }
        
        $sql .= " AND status = 'active' ORDER BY account_code";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getChildren($parentId) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE parent_id = ? AND status = 'active' ORDER BY account_code",
            [$parentId]
        );
    }
    
    public function updateBalance($accountId, $amount, $type = 'debit') {
        $account = $this->getById($accountId);
        if (!$account) return false;
        
        $currentBalance = floatval($account['balance']);
        
        // For Assets and Expenses: debit increases, credit decreases
        // For Liabilities, Equity, and Revenue: credit increases, debit decreases
        $increasesWithDebit = in_array($account['account_type'], ['Assets', 'Expenses']);
        
        if ($increasesWithDebit) {
            $newBalance = $type === 'debit' ? $currentBalance + $amount : $currentBalance - $amount;
        } else {
            $newBalance = $type === 'credit' ? $currentBalance + $amount : $currentBalance - $amount;
        }
        
        return $this->update($accountId, ['balance' => $newBalance]);
    }
    
    public function getNextAccountCode($type, $parentId = null) {
        try {
            // Get the highest account code for this type
            $sql = "SELECT MAX(CAST(SUBSTRING(account_code, 1) AS UNSIGNED)) as max_code 
                    FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE account_type = ?";
            $params = [$type];
            
            if ($parentId !== null) {
                $sql .= " AND parent_id = ?";
                $params[] = $parentId;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            $nextNum = ($result && isset($result['max_code'])) ? intval($result['max_code']) + 1 : 1;
            
            // Format: ASSETS-001, LIABILITIES-001, etc.
            $prefix = strtoupper(substr($type, 0, 4));
            return $prefix . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Account_model getNextAccountCode error: ' . $e->getMessage());
            // Return a default code if query fails
            $prefix = strtoupper(substr($type, 0, 4));
            return $prefix . '-001';
        }
    }
    
    public function search($query) {
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE (account_code LIKE ? OR account_name LIKE ?) AND status = 'active' 
             ORDER BY account_code 
             LIMIT 50",
            [$searchTerm, $searchTerm]
        );
    }
}

