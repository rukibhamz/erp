<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity_model extends Base_Model {
    protected $table = 'activity_log';
    
    public function log($userId, $action, $module = null, $description = null) {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($data);
    }
    
    /**
     * Get recent activity log entries
     * SECURITY: Uses parameterized query to prevent SQL injection
     * 
     * @param int $limit Maximum number of records to return (default: 50, max: 1000)
     * @return array
     */
    public function getRecent($limit = 50) {
        // Validate and sanitize limit to prevent SQL injection
        $limit = max(1, min(1000, intval($limit)));
        
        $sql = "SELECT a.*, u.username, u.email 
                FROM `" . $this->db->getPrefix() . $this->table . "` a
                LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * Get activity log entries for a specific user
     * SECURITY: Uses parameterized query to prevent SQL injection
     * 
     * @param int $userId User ID
     * @param int $limit Maximum number of records to return (default: 50, max: 1000)
     * @return array
     */
    public function getByUser($userId, $limit = 50) {
        // Validate and sanitize limit to prevent SQL injection
        $limit = max(1, min(1000, intval($limit)));
        
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                WHERE user_id = ? 
                ORDER BY created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }
    
    /**
     * Get all activity log entries with optional filtering
     * SECURITY: Uses parameterized queries and validates ORDER BY to prevent SQL injection
     * 
     * @param string|null $where WHERE clause (must use ? placeholders for values)
     * @param array $whereParams Parameters for WHERE clause placeholders
     * @param int $offset Number of records to skip (default: 0)
     * @param string $orderBy ORDER BY clause (validated against whitelist)
     * @param int $limit Maximum number of records to return (default: 50, max: 1000)
     * @return array
     */
    public function getAll($where = null, $whereParams = [], $offset = 0, $orderBy = 'created_at DESC', $limit = 50) {
        // Validate and sanitize numeric parameters to prevent SQL injection
        $limit = max(1, min(1000, intval($limit)));
        $offset = max(0, intval($offset));
        
        // Validate ORDER BY to prevent SQL injection (whitelist approach)
        $allowedOrderColumns = ['created_at', 'user_id', 'action', 'module', 'id'];
        $allowedOrderDirections = ['ASC', 'DESC'];
        $orderByParts = explode(' ', trim($orderBy));
        $orderColumn = $orderByParts[0] ?? 'created_at';
        $orderDirection = strtoupper($orderByParts[1] ?? 'DESC');
        
        // Only allow whitelisted columns and directions
        if (!in_array($orderColumn, $allowedOrderColumns)) {
            $orderColumn = 'created_at';
        }
        if (!in_array($orderDirection, $allowedOrderDirections)) {
            $orderDirection = 'DESC';
        }
        $safeOrderBy = "{$orderColumn} {$orderDirection}";
        
        $sql = "SELECT a.*, u.username, u.email 
                FROM `" . $this->db->getPrefix() . $this->table . "` a
                LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id";
        
        $params = [];
        
        // If where clause provided, it must be parameterized
        if ($where) {
            // Basic validation: WHERE clause should contain ? placeholders if it has values
            // For security, we require parameterized where clauses
            // Log warning if WHERE clause doesn't look parameterized
            if (preg_match('/[^a-zA-Z0-9_\.\s=<>!?(),\']/', $where)) {
                error_log('Activity_model getAll: Potentially unsafe WHERE clause detected: ' . $where);
                throw new Exception('Invalid WHERE clause format. Use parameterized queries with ? placeholders.');
            }
            $sql .= " WHERE " . $where;
            $params = array_merge($params, $whereParams);
        }
        
        $sql .= " ORDER BY {$safeOrderBy} LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
}

