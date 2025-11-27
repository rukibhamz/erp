<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Error Logger Library
 * 
 * Centralized error logging system with multiple log levels,
 * database logging, file logging, and email notifications
 * 
 * @package    ERP
 * @subpackage Libraries
 * @category   Logging
 * @author     ERP Development Team
 */
class Error_logger {
    
    private $CI;
    private $log_path;
    private $log_to_database = true;
    private $log_to_file = true;
    private $email_on_critical = false;
    private $max_log_size = 10485760; // 10MB
    
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Set log path
        $this->log_path = APPPATH . 'logs/';
        
        // Create logs directory if it doesn't exist
        if (!is_dir($this->log_path)) {
            mkdir($this->log_path, 0755, true);
        }
        
        // Load configuration if exists
        if (file_exists(APPPATH . 'config/logging.php')) {
            $this->CI->config->load('logging', TRUE);
            $config = $this->CI->config->item('logging');
            
            $this->log_to_database = $config['log_to_database'] ?? true;
            $this->log_to_file = $config['log_to_file'] ?? true;
            $this->email_on_critical = $config['email_on_critical'] ?? false;
        }
    }
    
    /**
     * Log a debug message
     */
    public function debug($message, $context = []) {
        return $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log an info message
     */
    public function info($message, $context = []) {
        return $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log a warning message
     */
    public function warning($message, $context = []) {
        return $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log an error message
     */
    public function error($message, $context = []) {
        return $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log a critical error message
     */
    public function critical($message, $context = []) {
        $result = $this->log(self::CRITICAL, $message, $context);
        
        // Send email notification for critical errors
        if ($this->email_on_critical) {
            $this->sendCriticalErrorEmail($message, $context);
        }
        
        return $result;
    }
    
    /**
     * Main logging method
     */
    private function log($level, $message, $context = []) {
        try {
            // Gather context information
            $logData = $this->prepareLogData($level, $message, $context);
            
            // Log to file
            if ($this->log_to_file) {
                $this->logToFile($logData);
            }
            
            // Log to database
            if ($this->log_to_database) {
                $this->logToDatabase($logData);
            }
            
            return true;
        } catch (Exception $e) {
            // Fallback to error_log if our logging fails
            error_log("Error_logger failed: " . $e->getMessage());
            error_log("Original message: " . $message);
            return false;
        }
    }
    
    /**
     * Prepare log data with context
     */
    private function prepareLogData($level, $message, $context) {
        return [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => $this->CI->session->userdata('user_id') ?? null,
            'ip_address' => $this->CI->input->ip_address(),
            'user_agent' => $this->CI->input->user_agent(),
            'module' => $this->CI->router->fetch_class(),
            'url' => current_url(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Log to file
     */
    private function logToFile($logData) {
        $filename = $this->log_path . 'log-' . date('Y-m-d') . '.php';
        
        // Check file size and rotate if needed
        if (file_exists($filename) && filesize($filename) > $this->max_log_size) {
            $this->rotateLogFile($filename);
        }
        
        // Format log entry
        $logEntry = sprintf(
            "[%s] %s: %s | User: %s | IP: %s | Module: %s | URL: %s\n",
            $logData['timestamp'],
            $logData['level'],
            $logData['message'],
            $logData['user_id'] ?? 'guest',
            $logData['ip_address'],
            $logData['module'],
            $logData['url']
        );
        
        // Add context if present
        if (!empty($logData['context'])) {
            $logEntry .= "Context: " . json_encode($logData['context']) . "\n";
        }
        
        $logEntry .= str_repeat('-', 80) . "\n";
        
        // Write to file
        if (!file_exists($filename)) {
            $header = "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n";
            file_put_contents($filename, $header, LOCK_EX);
        }
        
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log to database
     */
    private function logToDatabase($logData) {
        try {
            $this->CI->db->insert('system_logs', [
                'level' => $logData['level'],
                'message' => $logData['message'],
                'context' => !empty($logData['context']) ? json_encode($logData['context']) : null,
                'user_id' => $logData['user_id'],
                'ip_address' => $logData['ip_address'],
                'user_agent' => $logData['user_agent'],
                'module' => $logData['module'],
                'url' => $logData['url']
            ]);
        } catch (Exception $e) {
            // Silently fail database logging
            error_log("Database logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Rotate log file when it gets too large
     */
    private function rotateLogFile($filename) {
        $rotated = $filename . '.' . time();
        rename($filename, $rotated);
        
        // Optionally compress old log files
        if (function_exists('gzencode')) {
            $content = file_get_contents($rotated);
            file_put_contents($rotated . '.gz', gzencode($content, 9));
            unlink($rotated);
        }
    }
    
    /**
     * Send email notification for critical errors
     */
    private function sendCriticalErrorEmail($message, $context) {
        try {
            $this->CI->load->library('email');
            
            $to = $this->CI->config->item('admin_email') ?? 'admin@example.com';
            $subject = '[CRITICAL ERROR] ' . $this->CI->config->item('app_name', 'ERP System');
            
            $body = "A critical error has occurred in the system:\n\n";
            $body .= "Message: " . $message . "\n";
            $body .= "Time: " . date('Y-m-d H:i:s') . "\n";
            $body .= "User: " . ($this->CI->session->userdata('user_id') ?? 'guest') . "\n";
            $body .= "IP: " . $this->CI->input->ip_address() . "\n";
            $body .= "URL: " . current_url() . "\n\n";
            
            if (!empty($context)) {
                $body .= "Context:\n" . print_r($context, true);
            }
            
            $this->CI->email->from('noreply@' . $_SERVER['HTTP_HOST'], 'ERP System');
            $this->CI->email->to($to);
            $this->CI->email->subject($subject);
            $this->CI->email->message($body);
            $this->CI->email->send();
        } catch (Exception $e) {
            error_log("Failed to send critical error email: " . $e->getMessage());
        }
    }
    
    /**
     * Clean old logs from database
     */
    public function cleanOldLogs($days = 90) {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $this->CI->db->where('created_at <', $cutoff);
            $this->CI->db->delete('system_logs');
            
            return $this->CI->db->affected_rows();
        } catch (Exception $e) {
            error_log("Failed to clean old logs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent logs
     */
    public function getRecentLogs($limit = 100, $level = null) {
        try {
            $this->CI->db->select('*');
            $this->CI->db->from('system_logs');
            
            if ($level) {
                $this->CI->db->where('level', $level);
            }
            
            $this->CI->db->order_by('created_at', 'DESC');
            $this->CI->db->limit($limit);
            
            return $this->CI->db->get()->result_array();
        } catch (Exception $e) {
            error_log("Failed to get recent logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get log statistics
     */
    public function getStatistics($hours = 24) {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            $this->CI->db->select('level, COUNT(*) as count');
            $this->CI->db->from('system_logs');
            $this->CI->db->where('created_at >=', $cutoff);
            $this->CI->db->group_by('level');
            
            $results = $this->CI->db->get()->result_array();
            
            $stats = [
                'DEBUG' => 0,
                'INFO' => 0,
                'WARNING' => 0,
                'ERROR' => 0,
                'CRITICAL' => 0
            ];
            
            foreach ($results as $row) {
                $stats[$row['level']] = (int)$row['count'];
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Failed to get log statistics: " . $e->getMessage());
            return [];
        }
    }
}
