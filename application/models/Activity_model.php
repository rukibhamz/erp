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
    
    public function getRecent($limit = 50) {
        $sql = "SELECT a.*, u.username, u.email 
                FROM `" . $this->db->getPrefix() . $this->table . "` a
                LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT {$limit}";
        
        return $this->db->fetchAll($sql);
    }
    
    public function getByUser($userId, $limit = 50) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                WHERE user_id = ? 
                ORDER BY created_at DESC
                LIMIT {$limit}";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    public function getAll($where = null, $offset = 0, $orderBy = 'created_at DESC', $limit = 50) {
        $sql = "SELECT a.*, u.username, u.email 
                FROM `" . $this->db->getPrefix() . $this->table . "` a
                LEFT JOIN `" . $this->db->getPrefix() . "users` u ON a.user_id = u.id";
        
        if ($where) {
            $sql .= " WHERE " . $where;
        }
        
        $sql .= " ORDER BY " . $orderBy . " LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->fetchAll($sql);
    }
}

