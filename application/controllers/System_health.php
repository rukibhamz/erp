<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * System Health Controller
 * 
 * Monitors system health and provides health check endpoints
 * 
 * @package    ERP
 * @subpackage Controllers
 * @category   Admin
 */
class System_health extends Base_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
    }
    
    /**
     * System health dashboard
     */
    public function index() {
        $data['page_title'] = 'System Health';
        $data['health'] = $this->getSystemHealth();
        
        $this->load->view('templates/header', $data);
        $this->load->view('system/health_dashboard', $data);
        $this->load->view('templates/footer');
    }
    
    /**
     * Get system health metrics
     */
    private function getSystemHealth() {
        $health = [];
        
        // Database health
        $health['database'] = $this->checkDatabase();
        
        // Disk space
        $health['disk'] = $this->checkDiskSpace();
        
        // Error rate
        $health['errors'] = $this->checkErrorRate();
        
        // Active sessions
        $health['sessions'] = $this->checkActiveSessions();
        
        // Failed logins
        $health['failed_logins'] = $this->checkFailedLogins();
        
        // System load (if available)
        $health['system_load'] = $this->getSystemLoad();
        
        // Overall status
        $health['overall'] = $this->calculateOverallHealth($health);
        
        return $health;
    }
    
    /**
     * Check database connection and performance
     */
    private function checkDatabase() {
        try {
            $start = microtime(true);
            $this->db->query('SELECT 1');
            $response_time = (microtime(true) - $start) * 1000; // ms
            
            // Get database size
            $result = $this->db->query("
                SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
            ")->row_array();
            
            return [
                'status' => 'healthy',
                'response_time' => round($response_time, 2),
                'size_mb' => round($result['size_mb'], 2),
                'message' => 'Database is responding normally'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check disk space
     */
    private function checkDiskSpace() {
        $total = disk_total_space('.');
        $free = disk_free_space('.');
        $used = $total - $free;
        $percent_used = ($used / $total) * 100;
        
        $status = 'healthy';
        if ($percent_used > 90) {
            $status = 'critical';
        } elseif ($percent_used > 80) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'used_gb' => round($used / 1024 / 1024 / 1024, 2),
            'percent_used' => round($percent_used, 2),
            'message' => "Disk is {$percent_used}% full"
        ];
    }
    
    /**
     * Check error rate
     */
    private function checkErrorRate() {
        $this->load->library('error_logger');
        $stats = $this->error_logger->getStatistics(1); // Last hour
        
        $error_count = ($stats['ERROR'] ?? 0) + ($stats['CRITICAL'] ?? 0);
        
        $status = 'healthy';
        if ($error_count > 50) {
            $status = 'critical';
        } elseif ($error_count > 20) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'errors_last_hour' => $error_count,
            'warnings_last_hour' => $stats['WARNING'] ?? 0,
            'message' => "{$error_count} errors in the last hour"
        ];
    }
    
    /**
     * Check active sessions
     */
    private function checkActiveSessions() {
        // Count active sessions from database
        $this->db->where('last_activity >', time() - 3600);
        $active = $this->db->count_all_results('sessions');
        
        return [
            'status' => 'healthy',
            'active_sessions' => $active,
            'message' => "{$active} active sessions"
        ];
    }
    
    /**
     * Check failed login attempts
     */
    private function checkFailedLogins() {
        $this->db->where('failed_login_attempts >', 0);
        $this->db->where('locked_until >', date('Y-m-d H:i:s'));
        $locked = $this->db->count_all_results('users');
        
        $status = $locked > 5 ? 'warning' : 'healthy';
        
        return [
            'status' => $status,
            'locked_accounts' => $locked,
            'message' => "{$locked} accounts currently locked"
        ];
    }
    
    /**
     * Get system load average (Linux only)
     */
    private function getSystemLoad() {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                'status' => 'healthy',
                'load_1min' => round($load[0], 2),
                'load_5min' => round($load[1], 2),
                'load_15min' => round($load[2], 2),
                'message' => 'Load average: ' . round($load[0], 2)
            ];
        }
        
        return [
            'status' => 'unknown',
            'message' => 'System load not available'
        ];
    }
    
    /**
     * Calculate overall health status
     */
    private function calculateOverallHealth($health) {
        $statuses = [];
        foreach ($health as $component) {
            if (isset($component['status'])) {
                $statuses[] = $component['status'];
            }
        }
        
        if (in_array('critical', $statuses)) {
            return 'critical';
        } elseif (in_array('warning', $statuses)) {
            return 'warning';
        }
        
        return 'healthy';
    }
    
    /**
     * Health check endpoint (for monitoring tools)
     */
    public function check() {
        $health = $this->getSystemHealth();
        
        header('Content-Type: application/json');
        http_response_code($health['overall'] === 'healthy' ? 200 : 503);
        
        echo json_encode([
            'status' => $health['overall'],
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => $health
        ]);
    }
    
    /**
     * Get health metrics via AJAX
     */
    public function ajax_get_health() {
        $health = $this->getSystemHealth();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'health' => $health]);
    }
}
