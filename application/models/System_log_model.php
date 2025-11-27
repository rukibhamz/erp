<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System Log Model
 * 
 * Model for querying system logs
 */
class System_log_model extends CI_Model {
    
    private $table = 'system_logs';
    
    /**
     * Get logs with filters
     */
    public function getLogs($filters = [], $limit = 50, $offset = 0) {
        $this->db->select('system_logs.*, users.username');
        $this->db->from($this->table);
        $this->db->join('users', 'users.id = system_logs.user_id', 'left');
        
        // Apply filters
        if (!empty($filters['level'])) {
            $this->db->where('system_logs.level', $filters['level']);
        }
        
        if (!empty($filters['module'])) {
            $this->db->where('system_logs.module', $filters['module']);
        }
        
        if (!empty($filters['date_from'])) {
            $this->db->where('system_logs.created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('system_logs.created_at <=', $filters['date_to'] . ' 23:59:59');
        }
        
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('system_logs.message', $filters['search']);
            $this->db->or_like('system_logs.url', $filters['search']);
            $this->db->group_end();
        }
        
        $this->db->order_by('system_logs.created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Count logs with filters
     */
    public function countLogs($filters = []) {
        $this->db->from($this->table);
        
        if (!empty($filters['level'])) {
            $this->db->where('level', $filters['level']);
        }
        
        if (!empty($filters['module'])) {
            $this->db->where('module', $filters['module']);
        }
        
        if (!empty($filters['date_from'])) {
            $this->db->where('created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }
        
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('message', $filters['search']);
            $this->db->or_like('url', $filters['search']);
            $this->db->group_end();
        }
        
        return $this->db->count_all_results();
    }
    
    /**
     * Get single log entry
     */
    public function getLog($id) {
        $this->db->select('system_logs.*, users.username, users.email');
        $this->db->from($this->table);
        $this->db->join('users', 'users.id = system_logs.user_id', 'left');
        $this->db->where('system_logs.id', $id);
        
        return $this->db->get()->row_array();
    }
    
    /**
     * Get unique modules for filter dropdown
     */
    public function getUniqueModules() {
        $this->db->select('DISTINCT module');
        $this->db->from($this->table);
        $this->db->where('module IS NOT NULL');
        $this->db->order_by('module', 'ASC');
        
        $result = $this->db->get()->result_array();
        return array_column($result, 'module');
    }
}
