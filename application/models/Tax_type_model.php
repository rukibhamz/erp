<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_type_model extends Base_Model {
    protected $table = 'tax_types';
    
    public function getAllActive() {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE is_active = 1 
             ORDER BY name ASC"
        );
    }
    
    public function getByCode($code) {
        return $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE code = ?",
            [$code]
        );
    }
    
    public function getByAuthority($authority) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE authority = ? AND is_active = 1 
             ORDER BY name ASC",
            [$authority]
        );
    }
    
    /**
     * Get all tax types
     * SECURITY: Uses parameterized queries and validates ORDER BY to prevent SQL injection
     * 
     * @param int|null $limit Maximum number of records to return
     * @param int $offset Number of records to skip
     * @param string|null $orderBy ORDER BY clause (validated against whitelist)
     * @return array
     */
    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`";
            
            // SECURITY: Validate ORDER BY to prevent SQL injection (whitelist approach)
            $defaultOrderBy = 'authority ASC, name ASC';
            if ($orderBy) {
                // Whitelist approach for ORDER BY
                $allowedOrderColumns = ['authority', 'name', 'code', 'rate', 'is_active', 'created_at', 'id'];
                $allowedOrderDirections = ['ASC', 'DESC'];
                
                // Handle multiple columns in ORDER BY (e.g., "authority ASC, name ASC")
                $orderParts = explode(',', $orderBy);
                $safeOrderParts = [];
                
                foreach ($orderParts as $part) {
                    $part = trim($part);
                    $partSplit = explode(' ', $part);
                    $column = $partSplit[0] ?? '';
                    $direction = strtoupper($partSplit[1] ?? 'ASC');
                    
                    // Only allow whitelisted columns and directions
                    if (in_array($column, $allowedOrderColumns)) {
                        if (!in_array($direction, $allowedOrderDirections)) {
                            $direction = 'ASC';
                        }
                        $safeOrderParts[] = "{$column} {$direction}";
                    }
                }
                
                if (!empty($safeOrderParts)) {
                    $orderBy = implode(', ', $safeOrderParts);
                } else {
                    $orderBy = $defaultOrderBy;
                }
            } else {
                $orderBy = $defaultOrderBy;
            }
            
            $sql .= " ORDER BY {$orderBy}";
            $params = [];
            
            // SECURITY: Validate and sanitize limit and offset to prevent SQL injection
            if ($limit) {
                $limit = max(1, min(10000, intval($limit)));
                $offset = max(0, intval($offset));
                $sql .= " LIMIT ? OFFSET ?";
                $params = [$limit, $offset];
            }
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Tax_type_model getAll error: ' . $e->getMessage());
            return [];
        }
    }
}

