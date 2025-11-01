<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account_model extends Base_Model {
    protected $table = 'accounts';
    
    public function getByType($type) {
        try {
            // Order by account_number if available, otherwise by account_code
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE account_type = ? AND status = 'active' 
                 ORDER BY COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code",
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
        
        $sql .= " AND status = 'active' ORDER BY COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getChildren($parentId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE parent_id = ? AND status = 'active' 
                 ORDER BY COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code",
                [$parentId]
            );
        } catch (Exception $e) {
            error_log('Account_model getChildren error: ' . $e->getMessage());
            return [];
        }
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
            // Standard account numbering: 1000-1999 Assets, 2000-2999 Liabilities, 3000-3999 Equity, 4000-4999 Revenue, 5000-5999 Expenses
            $typeRanges = [
                'Assets' => ['start' => 1000, 'end' => 1999],
                'Liabilities' => ['start' => 2000, 'end' => 2999],
                'Equity' => ['start' => 3000, 'end' => 3999],
                'Revenue' => ['start' => 4000, 'end' => 4999],
                'Expenses' => ['start' => 5000, 'end' => 5999]
            ];
            
            $range = $typeRanges[$type] ?? ['start' => 1000, 'end' => 9999];
            $startNum = $range['start'];
            
            if ($parentId !== null) {
                // For child accounts, find the parent's account number and add to it
                $parent = $this->getById($parentId);
                if ($parent && isset($parent['account_number']) && !empty($parent['account_number'])) {
                    $parentNum = intval($parent['account_number']);
                    // Find the next available number starting from parent + 1
                    $sql = "SELECT MAX(CAST(account_number AS UNSIGNED)) as max_code
                            FROM `" . $this->db->getPrefix() . $this->table . "`
                            WHERE account_number >= ? AND account_number < ?";
                    $result = $this->db->fetchOne($sql, [$parentNum, $parentNum + 100]);
                    $nextNum = ($result && isset($result['max_code'])) ? intval($result['max_code']) + 1 : $parentNum + 1;
                    
                    // Ensure it doesn't exceed parent's range
                    $maxChild = floor($parentNum / 100) * 100 + 99;
                    if ($nextNum > $maxChild) {
                        $nextNum = $parentNum + 1;
                    }
                    
                    return str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                }
            }
            
            // For top-level accounts, use standard numbering
            $sql = "SELECT MAX(CAST(account_number AS UNSIGNED)) as max_code
                    FROM `" . $this->db->getPrefix() . $this->table . "`
                    WHERE account_type = ? AND account_number >= ? AND account_number < ?
                    AND (parent_id IS NULL OR parent_id = 0)";
            $params = [$type, $startNum, $range['end']];
            
            $result = $this->db->fetchOne($sql, $params);
            $nextNum = ($result && isset($result['max_code']) && intval($result['max_code']) >= $startNum) 
                ? intval($result['max_code']) + 1 
                : $startNum;
            
            // Ensure we don't exceed the range
            if ($nextNum > $range['end']) {
                $nextNum = $startNum;
            }
            
            return str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Account_model getNextAccountCode error: ' . $e->getMessage());
            // Return a default code if query fails
            $typeRanges = [
                'Assets' => 1000,
                'Liabilities' => 2000,
                'Equity' => 3000,
                'Revenue' => 4000,
                'Expenses' => 5000
            ];
            $startNum = $typeRanges[$type] ?? 1000;
            return str_pad($startNum, 4, '0', STR_PAD_LEFT);
        }
    }
    
    public function getByAccountNumber($number) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE account_number = ? AND status = 'active'",
                [$number]
            );
        } catch (Exception $e) {
            error_log('Account_model getByAccountNumber error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getDefaultAccount($accountType, $subType = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE account_type = ? AND is_default = 1 AND status = 'active'";
            $params = [$accountType];
            
            if ($subType) {
                // Could add sub-type field later for more granular defaults
            }
            
            return $this->db->fetchOne($sql, $params);
        } catch (Exception $e) {
            error_log('Account_model getDefaultAccount error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function setDefaultAccount($accountId) {
        try {
            // First, unset any existing default for this account type
            $account = $this->getById($accountId);
            if (!$account) return false;
            
            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . $this->table . "` 
                 SET is_default = 0 WHERE account_type = ? AND is_default = 1",
                [$account['account_type']]
            );
            
            // Set this account as default
            return $this->update($accountId, ['is_default' => 1]);
        } catch (Exception $e) {
            error_log('Account_model setDefaultAccount error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function search($query) {
        try {
            $searchTerm = "%{$query}%";
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE (account_code LIKE ? OR account_name LIKE ? OR account_number LIKE ?) AND status = 'active' 
                 ORDER BY COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code
                 LIMIT 50",
                [$searchTerm, $searchTerm, $searchTerm]
            );
        } catch (Exception $e) {
            error_log('Account_model search error: ' . $e->getMessage());
            return [];
        }
    }
}

