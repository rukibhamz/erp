<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit Logger Library
 * 
 * Tracks all critical actions in the system for compliance and security
 * 
 * @package    ERP
 * @subpackage Libraries
 * @category   Security
 * @author     ERP Development Team
 */
class Audit_logger {
    
    private $CI;
    private $enabled = true;
    private $critical_modules = [];
    private $critical_actions = [];
    
    // Action types
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';
    const LOGIN = 'LOGIN';
    const LOGOUT = 'LOGOUT';
    const PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    const PERMISSION_CHANGE = 'PERMISSION_CHANGE';
    const VIEW = 'VIEW';
    const EXPORT = 'EXPORT';
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Load configuration
        if (file_exists(APPPATH . 'config/security.php')) {
            $this->CI->config->load('security', TRUE);
            $config = $this->CI->config->item('security');
            
            $audit_config = $config['audit_trail'] ?? [];
            $this->enabled = $audit_config['enabled'] ?? true;
            $this->critical_modules = $audit_config['critical_modules'] ?? [];
            $this->critical_actions = $audit_config['critical_actions'] ?? [];
        }
    }
    
    /**
     * Log an audit trail entry
     * 
     * @param string $action Action performed (CREATE, UPDATE, DELETE, etc.)
     * @param string $module Module/table affected
     * @param int $record_id ID of the affected record
     * @param array $old_values Previous values (for UPDATE/DELETE)
     * @param array $new_values New values (for CREATE/UPDATE)
     * @return bool Success status
     */
    public function log($action, $module, $record_id = null, $old_values = null, $new_values = null) {
        if (!$this->enabled) {
            return false;
        }
        
        // Check if this module/action should be logged
        if (!$this->shouldLog($module, $action)) {
            return false;
        }
        
        try {
            $data = [
                'user_id' => $this->CI->session->userdata('user_id') ?? 0,
                'action' => $action,
                'module' => $module,
                'record_id' => $record_id,
                'old_values' => $old_values ? json_encode($old_values) : null,
                'new_values' => $new_values ? json_encode($new_values) : null,
                'ip_address' => $this->CI->input->ip_address(),
                'user_agent' => $this->CI->input->user_agent()
            ];
            
            $this->CI->db->insert('audit_trail', $data);
            return true;
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log a create action
     */
    public function logCreate($module, $record_id, $values) {
        return $this->log(self::CREATE, $module, $record_id, null, $values);
    }
    
    /**
     * Log an update action
     */
    public function logUpdate($module, $record_id, $old_values, $new_values) {
        return $this->log(self::UPDATE, $module, $record_id, $old_values, $new_values);
    }
    
    /**
     * Log a delete action
     */
    public function logDelete($module, $record_id, $old_values) {
        return $this->log(self::DELETE, $module, $record_id, $old_values, null);
    }
    
    /**
     * Log a login action
     */
    public function logLogin($user_id, $success = true) {
        return $this->log(
            self::LOGIN,
            'users',
            $user_id,
            null,
            ['success' => $success, 'timestamp' => date('Y-m-d H:i:s')]
        );
    }
    
    /**
     * Log a logout action
     */
    public function logLogout($user_id) {
        return $this->log(
            self::LOGOUT,
            'users',
            $user_id,
            null,
            ['timestamp' => date('Y-m-d H:i:s')]
        );
    }
    
    /**
     * Log a password change
     */
    public function logPasswordChange($user_id) {
        return $this->log(
            self::PASSWORD_CHANGE,
            'users',
            $user_id,
            null,
            ['timestamp' => date('Y-m-d H:i:s')]
        );
    }
    
    /**
     * Check if this module/action should be logged
     */
    private function shouldLog($module, $action) {
        // If log_all_actions is true, log everything
        $log_all = $this->CI->config->item('log_all_actions', 'security') ?? false;
        if ($log_all) {
            return true;
        }
        
        // Otherwise, only log critical modules and actions
        $is_critical_module = in_array($module, $this->critical_modules);
        $is_critical_action = in_array($action, $this->critical_actions);
        
        return $is_critical_module || $is_critical_action;
    }
    
    /**
     * Get audit trail for a specific record
     */
    public function getRecordHistory($module, $record_id, $limit = 50) {
        try {
            $this->CI->db->select('audit_trail.*, users.username');
            $this->CI->db->from('audit_trail');
            $this->CI->db->join('users', 'users.id = audit_trail.user_id', 'left');
            $this->CI->db->where('audit_trail.module', $module);
            $this->CI->db->where('audit_trail.record_id', $record_id);
            $this->CI->db->order_by('audit_trail.created_at', 'DESC');
            $this->CI->db->limit($limit);
            
            return $this->CI->db->get()->result_array();
        } catch (Exception $e) {
            error_log("Failed to get record history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get audit trail for a specific user
     */
    public function getUserActivity($user_id, $limit = 100) {
        try {
            $this->CI->db->select('*');
            $this->CI->db->from('audit_trail');
            $this->CI->db->where('user_id', $user_id);
            $this->CI->db->order_by('created_at', 'DESC');
            $this->CI->db->limit($limit);
            
            return $this->CI->db->get()->result_array();
        } catch (Exception $e) {
            error_log("Failed to get user activity: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent audit trail entries
     */
    public function getRecentActivity($limit = 100, $module = null) {
        try {
            $this->CI->db->select('audit_trail.*, users.username');
            $this->CI->db->from('audit_trail');
            $this->CI->db->join('users', 'users.id = audit_trail.user_id', 'left');
            
            if ($module) {
                $this->CI->db->where('audit_trail.module', $module);
            }
            
            $this->CI->db->order_by('audit_trail.created_at', 'DESC');
            $this->CI->db->limit($limit);
            
            return $this->CI->db->get()->result_array();
        } catch (Exception $e) {
            error_log("Failed to get recent activity: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old audit trail entries
     */
    public function cleanOldEntries($days = 365) {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $this->CI->db->where('created_at <', $cutoff);
            $this->CI->db->delete('audit_trail');
            
            return $this->CI->db->affected_rows();
        } catch (Exception $e) {
            error_log("Failed to clean old audit entries: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get audit statistics
     */
    public function getStatistics($days = 30) {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            // Get action counts
            $this->CI->db->select('action, COUNT(*) as count');
            $this->CI->db->from('audit_trail');
            $this->CI->db->where('created_at >=', $cutoff);
            $this->CI->db->group_by('action');
            $action_stats = $this->CI->db->get()->result_array();
            
            // Get module counts
            $this->CI->db->select('module, COUNT(*) as count');
            $this->CI->db->from('audit_trail');
            $this->CI->db->where('created_at >=', $cutoff);
            $this->CI->db->group_by('module');
            $this->CI->db->order_by('count', 'DESC');
            $this->CI->db->limit(10);
            $module_stats = $this->CI->db->get()->result_array();
            
            // Get top users
            $this->CI->db->select('users.username, COUNT(*) as count');
            $this->CI->db->from('audit_trail');
            $this->CI->db->join('users', 'users.id = audit_trail.user_id', 'left');
            $this->CI->db->where('audit_trail.created_at >=', $cutoff);
            $this->CI->db->group_by('audit_trail.user_id');
            $this->CI->db->order_by('count', 'DESC');
            $this->CI->db->limit(10);
            $user_stats = $this->CI->db->get()->result_array();
            
            return [
                'actions' => $action_stats,
                'modules' => $module_stats,
                'users' => $user_stats
            ];
        } catch (Exception $e) {
            error_log("Failed to get audit statistics: " . $e->getMessage());
            return [];
        }
    }
}
