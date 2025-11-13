<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all records with optional ordering and pagination
     * 
     * SECURITY: orderBy parameter is validated against whitelist to prevent SQL injection
     * 
     * @param int|null $limit Maximum number of records to return
     * @param int $offset Number of records to skip
     * @param string|array|null $orderBy Column name or array with 'column' and 'order' keys
     * @return array Array of records
     */
    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`";
            
            // SECURITY: Validate and sanitize orderBy parameter
            if ($orderBy) {
                $orderClause = $this->buildOrderByClause($orderBy);
                if ($orderClause) {
                    $sql .= " ORDER BY " . $orderClause;
                }
            }
            
            // SECURITY: Validate limit and offset are integers
            if ($limit !== null) {
                $limit = intval($limit);
                $offset = intval($offset);
                if ($limit > 0 && $offset >= 0) {
                    $sql .= " LIMIT {$limit} OFFSET {$offset}";
                }
            }
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Base_Model getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build ORDER BY clause with validation
     * 
     * SECURITY: Whitelist validation prevents SQL injection
     * 
     * @param string|array $orderBy Column name or array with 'column' and 'order'
     * @return string|null Safe ORDER BY clause or null if invalid
     */
    protected function buildOrderByClause($orderBy) {
        // Get allowed columns from child class or use default
        $allowedColumns = $this->getAllowedOrderColumns();
        
        $column = null;
        $order = 'ASC';
        
        if (is_array($orderBy)) {
            $column = $orderBy['column'] ?? null;
            $orderInput = strtoupper($orderBy['order'] ?? 'ASC');
            if (in_array($orderInput, ['ASC', 'DESC'])) {
                $order = $orderInput;
            }
        } else {
            // Parse string format like "column ASC" or "column DESC"
            $parts = explode(' ', trim($orderBy), 2);
            $column = $parts[0];
            if (isset($parts[1])) {
                $orderInput = strtoupper(trim($parts[1]));
                if (in_array($orderInput, ['ASC', 'DESC'])) {
                    $order = $orderInput;
                }
            }
        }
        
        // SECURITY: Validate column name against whitelist
        if ($column && in_array($column, $allowedColumns)) {
            // Escape column name to prevent SQL injection
            return "`{$column}` {$order}";
        }
        
        // Invalid column - return null to skip ORDER BY
        error_log("Base_Model: Invalid orderBy column '{$column}' - not in whitelist");
        return null;
    }
    
    /**
     * Get allowed columns for ORDER BY clause
     * 
     * Override this method in child classes to specify allowed columns.
     * Default allows common columns: id, created_at, updated_at
     * 
     * @return array Array of allowed column names
     */
    protected function getAllowedOrderColumns() {
        return ['id', 'created_at', 'updated_at', 'username', 'email', 'name', 'title', 'status'];
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE `{$this->primaryKey}` = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function getBy($field, $value) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE `{$field}` = ?";
        return $this->db->fetchOne($sql, [$value]);
    }
    
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    public function update($id, $data) {
        return $this->db->update($this->table, $data, "`{$this->primaryKey}` = ?", [$id]);
    }
    
    public function delete($id) {
        return $this->db->delete($this->table, "`{$this->primaryKey}` = ?", [$id]);
    }
    
    /**
     * Count records with WHERE clause
     * 
     * @deprecated This method is vulnerable to SQL injection. Use countBy() instead.
     * 
     * @param string $where WHERE clause (DEPRECATED - use countBy instead)
     * @param array $params Parameters for WHERE clause
     * @return int Number of records
     */
    public function count($where = '1=1', $params = []) {
        // SECURITY WARNING: Direct WHERE clause concatenation is dangerous
        // This method is kept for backward compatibility but should be avoided
        $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "` WHERE {$where}";
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
    
    /**
     * Count records by conditions (safe method)
     * 
     * SECURITY: Uses parameterized queries to prevent SQL injection
     * 
     * @param array $conditions Associative array of field => value pairs
     * @return int Number of matching records
     */
    public function countBy(array $conditions) {
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "`";
            $result = $this->db->fetchOne($sql);
            return $result['count'] ?? 0;
        }
        
        // SECURITY: Build WHERE clause using parameterized queries
        $whereClauses = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            // Validate field name (basic check - should be alphanumeric/underscore)
            if (preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
                $whereClauses[] = "`{$field}` = ?";
                $params[] = $value;
            } else {
                error_log("Base_Model countBy: Invalid field name '{$field}' - skipping");
            }
        }
        
        if (empty($whereClauses)) {
            $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "`";
            $result = $this->db->fetchOne($sql);
            return $result['count'] ?? 0;
        }
        
        $whereSql = implode(' AND ', $whereClauses);
        $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "` WHERE {$whereSql}";
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
}

