<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account_model extends Base_Model {
    protected $table = 'accounts';
    private static $accountNumberColumnExists = null;
    
    const ACCOUNT_RANGES = [
        'Assets' => ['start' => 1000, 'end' => 1999],
        'Liabilities' => ['start' => 2000, 'end' => 2999],
        'Equity' => ['start' => 3000, 'end' => 3999],
        'Revenue' => ['start' => 4000, 'end' => 4999],
        'Expenses' => ['start' => 5000, 'end' => 9999]
    ];
    
    /**
     * Check if account_number column exists (cached per request)
     */
    public function hasAccountNumberColumn() {
        if (self::$accountNumberColumnExists !== null) {
            return self::$accountNumberColumnExists;
        }
        
        try {
            $table = $this->db->getPrefix() . $this->table;
            $sql = "SHOW COLUMNS FROM `{$table}` LIKE 'account_number'";
            $result = $this->db->fetchOne($sql);
            self::$accountNumberColumnExists = !empty($result);
        } catch (Exception $e) {
            error_log('Account_model hasAccountNumberColumn error: ' . $e->getMessage());
            self::$accountNumberColumnExists = false;
        }
        
        return self::$accountNumberColumnExists;
    }
    
    public function getFiltered($type = null, $search = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE status = 'active'";
            $params = [];
            
            if (!empty($type)) {
                $sql .= " AND account_type = ?";
                $params[] = $type;
            }
            
            if (!empty($search)) {
                $sql .= " AND (account_name LIKE ? OR account_code LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($this->hasAccountNumberColumn()) {
                $sql .= " ORDER BY COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code";
            } else {
                $sql .= " ORDER BY account_code";
            }
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Account_model getFiltered error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByType($type) {
        try {
            // Normalize type and handle aliases for DB schema compatibility
            $normalizedType = strtolower($type);
            if ($normalizedType === 'revenue') $normalizedType = 'income';
            else if ($normalizedType === 'assets') $normalizedType = 'asset';
            else if ($normalizedType === 'liabilities') $normalizedType = 'liability';
            else if ($normalizedType === 'expenses') $normalizedType = 'expense';

            // Order by account_number if column exists, otherwise by account_code
            $orderBy = $this->hasAccountNumberColumn()
                ? "COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code"
                : "account_code";

            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE (account_type = ? OR account_type = ?) AND status = 'active' 
                 ORDER BY {$orderBy}",
                [$normalizedType, $type]
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
            $sql .= "parent_account_id IS NULL";
        } else {
            $sql .= "parent_account_id = ?";
            $params[] = $parentId;
        }
        
        $orderBy = $this->hasAccountNumberColumn()
            ? "COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code"
            : "account_code";
        $sql .= " AND status = 'active' ORDER BY {$orderBy}";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getChildren($parentId) {
        try {
            $orderBy = $this->hasAccountNumberColumn()
                ? "COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code"
                : "account_code";
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE parent_account_id = ? AND status = 'active' 
                 ORDER BY {$orderBy}",
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
        // Normalize type for comparison (lowercase)
        $normalizedType = strtolower($account['account_type']);
        $increasesWithDebit = in_array($normalizedType, ['assets', 'expenses', 'asset', 'expense']);
        
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
                    AND (parent_account_id IS NULL OR parent_account_id = 0)";
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
    
    /**
     * Get account by account_code
     * @param string $code Account code
     * @return array|false Account data or false if not found
     */
    public function getByCode($code) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE account_code = ? AND status = 'active'",
                [$code]
            );
        } catch (Exception $e) {
            error_log('Account_model getByCode error: ' . $e->getMessage());
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
            if ($this->hasAccountNumberColumn()) {
                return $this->db->fetchAll(
                    "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                     WHERE (account_code LIKE ? OR account_name LIKE ? OR account_number LIKE ?) AND status = 'active' 
                     ORDER BY COALESCE(CAST(account_number AS UNSIGNED), 9999), account_code
                     LIMIT 50",
                    [$searchTerm, $searchTerm, $searchTerm]
                );
            } else {
                return $this->db->fetchAll(
                    "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                     WHERE (account_code LIKE ? OR account_name LIKE ?) AND status = 'active' 
                     ORDER BY account_code
                     LIMIT 50",
                    [$searchTerm, $searchTerm]
                );
            }
        } catch (Exception $e) {
            error_log('Account_model search error: ' . $e->getMessage());
            return [];
        }
    }

    public function validateAccountCode($code, $type) {
        // If code is empty, it's valid (will be auto-generated)
        if (empty($code)) {
            return true;
        }
        
        // Check if code is numeric
        if (!is_numeric($code)) {
            // Allow non-numeric codes for legacy support, but warn?
            // For now, strict numeric validation for standard codes
            // return false; 
        }
        
        $codeNum = intval($code);
        
        // Find range with case-insensitive and alias matching
        $range = null;
        $searchType = strtolower($type);
        foreach (self::ACCOUNT_RANGES as $key => $r) {
            $lowerKey = strtolower($key);
            if ($lowerKey === $searchType || 
                ($lowerKey === 'revenue' && $searchType === 'income') ||
                ($lowerKey === 'income' && $searchType === 'revenue') ||
                ($lowerKey === 'assets' && $searchType === 'asset') ||
                ($lowerKey === 'liabilities' && $searchType === 'liability')) {
                $range = $r;
                break;
            }
        }
        
        if ($range) {
            if ($codeNum < $range['start'] || $codeNum > $range['end']) {
                return false;
            }
        }
        
        return true;
    }

    public function getParent($accountId) {
        try {
            $account = $this->getById($accountId);
            if (!$account || empty($account['parent_account_id'])) {
                return false;
            }
            return $this->getById($account['parent_account_id']);
        } catch (Exception $e) {
            error_log('Account_model getParent error: ' . $e->getMessage());
            return false;
        }
    }

    public function getTreeWithDepth($type = null) {
        $accounts = $this->getFiltered($type);
        return $this->flattenTree($this->buildTree($accounts));
    }

    private function buildTree(array $elements, $parentId = null) {
        $branch = array();
        foreach ($elements as $element) {
            $elementParentId = $element['parent_account_id'] ?? null;
            // Handle 0 as null for parent_id
            if ($elementParentId == 0) $elementParentId = null;
            
            if ($elementParentId == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    private function flattenTree($tree, $depth = 0) {
        $flat = [];
        foreach ($tree as $node) {
            $node['depth'] = $depth;
            $children = $node['children'] ?? [];
            unset($node['children']); // Remove children from the flat node
            $flat[] = $node;
            if (!empty($children)) {
                $flat = array_merge($flat, $this->flattenTree($children, $depth + 1));
            }
        }
        return $flat;
    }

    public function create($data) {
        // Validate account code if provided
        if (!empty($data['account_code']) && !empty($data['account_type'])) {
            if (!$this->validateAccountCode($data['account_code'], $data['account_type'])) {
                $range = self::ACCOUNT_RANGES[$data['account_type']];
                throw new Exception("Account code {$data['account_code']} is invalid for type {$data['account_type']}. Valid range: {$range['start']}-{$range['end']}");
            }
            
            // Check for duplicate code
            if ($this->getByCode($data['account_code'])) {
                throw new Exception("Account code {$data['account_code']} already exists.");
            }
        }
        
        return parent::create($data);
    }

    public function update($id, $data, $where = null, $params = []) {
        // Validate account code if changing
        if (!empty($data['account_code']) && !empty($data['account_type'])) {
            if (!$this->validateAccountCode($data['account_code'], $data['account_type'])) {
                $range = self::ACCOUNT_RANGES[$data['account_type']];
                throw new Exception("Account code {$data['account_code']} is invalid for type {$data['account_type']}. Valid range: {$range['start']}-{$range['end']}");
            }
            
            // Check for duplicate code (exclude current account)
            $existing = $this->getByCode($data['account_code']);
            if ($existing && $existing['id'] != $id) {
                throw new Exception("Account code {$data['account_code']} already exists.");
            }
        }
        
        return parent::update($id, $data, $where, $params);
    }
}

