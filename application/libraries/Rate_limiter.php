<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rate Limiter Library
 * 
 * Prevents abuse by limiting the number of requests to specific endpoints
 * 
 * @package    ERP
 * @subpackage Libraries
 * @category   Security
 * @author     ERP Development Team
 */
class Rate_limiter {
    
    private $CI;
    private $enabled = true;
    private $config = [];
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Load configuration
        if (file_exists(APPPATH . 'config/security.php')) {
            $this->CI->config->load('security', TRUE);
            $security_config = $this->CI->config->item('security');
            $this->config = $security_config['rate_limiting'] ?? [];
            $this->enabled = $this->config['enabled'] ?? true;
        }
        
        // Check if rate_limits table exists
        if ($this->enabled && $this->CI->db) {
            try {
                $prefix = $this->CI->db->getPrefix();
                $tableExists = $this->CI->db->fetchOne(
                    "SHOW TABLES LIKE ?",
                    [$prefix . 'rate_limits']
                );
                if (!$tableExists) {
                    error_log('Rate limiter disabled: rate_limits table does not exist');
                    $this->enabled = false;
                }
            } catch (Exception $e) {
                error_log('Rate limiter table check failed: ' . $e->getMessage());
                $this->enabled = false;
            }
        }
    }
    
    /**
     * Check if request is allowed
     * 
     * @param string $endpoint Endpoint being accessed
     * @param string $identifier IP address or user ID
     * @param int $max_attempts Maximum attempts allowed
     * @param int $window Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => timestamp]
     */
    public function check($endpoint, $identifier = null, $max_attempts = null, $window = null) {
        if (!$this->enabled) {
            return ['allowed' => true, 'remaining' => 999, 'reset_at' => time() + 3600];
        }
        
        // Use IP address if no identifier provided
        if ($identifier === null) {
            $identifier = $this->CI->input->ip_address();
        }
        
        // Use default limits if not specified
        if ($max_attempts === null || $window === null) {
            $defaults = $this->getDefaultLimits($endpoint);
            $max_attempts = $max_attempts ?? $defaults['max_attempts'];
            $window = $window ?? $defaults['window'];
        }
        
        try {
            // Get or create rate limit record
            $this->CI->db->where('identifier', $identifier);
            $this->CI->db->where('endpoint', $endpoint);
            $limit = $this->CI->db->get('rate_limits')->row_array();
            
            $now = time();
            
            if (!$limit) {
                // Create new limit record
                $reset_at = date('Y-m-d H:i:s', $now + $window);
                $this->CI->db->insert('rate_limits', [
                    'identifier' => $identifier,
                    'endpoint' => $endpoint,
                    'attempts' => 1,
                    'reset_at' => $reset_at
                ]);
                
                return [
                    'allowed' => true,
                    'remaining' => $max_attempts - 1,
                    'reset_at' => $now + $window
                ];
            }
            
            $reset_time = strtotime($limit['reset_at']);
            
            // Check if window has expired
            if ($now >= $reset_time) {
                // Reset the counter
                $reset_at = date('Y-m-d H:i:s', $now + $window);
                $this->CI->db->where('id', $limit['id']);
                $this->CI->db->update('rate_limits', [
                    'attempts' => 1,
                    'reset_at' => $reset_at
                ]);
                
                return [
                    'allowed' => true,
                    'remaining' => $max_attempts - 1,
                    'reset_at' => $now + $window
                ];
            }
            
            // Check if limit exceeded
            if ($limit['attempts'] >= $max_attempts) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'reset_at' => $reset_time
                ];
            }
            
            // Increment attempts
            $this->CI->db->where('id', $limit['id']);
            $this->CI->db->set('attempts', 'attempts + 1', FALSE);
            $this->CI->db->update('rate_limits');
            
            return [
                'allowed' => true,
                'remaining' => $max_attempts - ($limit['attempts'] + 1),
                'reset_at' => $reset_time
            ];
            
        } catch (Exception $e) {
            error_log("Rate limiter error: " . $e->getMessage());
            // Allow request if rate limiter fails
            return ['allowed' => true, 'remaining' => 999, 'reset_at' => time() + 3600];
        }
    }
    
    /**
     * Check login rate limit
     * 
     * @param string $identifier IP address or username
     * @return array Rate limit status
     */
    public function checkLogin($identifier = null) {
        $config = $this->config['login_attempts'] ?? [];
        $max_attempts = $config['max_attempts'] ?? 5;
        $window = $config['window'] ?? 300; // 5 minutes
        
        return $this->check('login', $identifier, $max_attempts, $window);
    }
    
    /**
     * Check API rate limit
     * 
     * @param string $identifier API key or user ID
     * @return array Rate limit status
     */
    public function checkAPI($identifier = null) {
        $config = $this->config['api_requests'] ?? [];
        $max_requests = $config['max_requests'] ?? 100;
        $window = $config['window'] ?? 60; // 1 minute
        
        return $this->check('api', $identifier, $max_requests, $window);
    }
    
    /**
     * Reset rate limit for an identifier
     * 
     * @param string $endpoint Endpoint
     * @param string $identifier Identifier
     * @return bool Success status
     */
    public function reset($endpoint, $identifier) {
        try {
            $this->CI->db->where('identifier', $identifier);
            $this->CI->db->where('endpoint', $endpoint);
            $this->CI->db->delete('rate_limits');
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to reset rate limit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean expired rate limit records
     * 
     * @return int Number of records deleted
     */
    public function cleanExpired() {
        try {
            $this->CI->db->where('reset_at <', date('Y-m-d H:i:s'));
            $this->CI->db->delete('rate_limits');
            
            return $this->CI->db->affected_rows();
        } catch (Exception $e) {
            error_log("Failed to clean expired rate limits: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get default limits for an endpoint
     * 
     * @param string $endpoint Endpoint name
     * @return array ['max_attempts' => int, 'window' => int]
     */
    private function getDefaultLimits($endpoint) {
        // Default limits based on endpoint type
        $defaults = [
            'login' => ['max_attempts' => 5, 'window' => 300],
            'api' => ['max_attempts' => 100, 'window' => 60],
            'password_reset' => ['max_attempts' => 3, 'window' => 3600],
            'default' => ['max_attempts' => 60, 'window' => 60]
        ];
        
        return $defaults[$endpoint] ?? $defaults['default'];
    }
    
    /**
     * Get rate limit status for an identifier
     * 
     * @param string $endpoint Endpoint
     * @param string $identifier Identifier
     * @return array|null Rate limit info or null if not found
     */
    public function getStatus($endpoint, $identifier) {
        try {
            $this->CI->db->where('identifier', $identifier);
            $this->CI->db->where('endpoint', $endpoint);
            $limit = $this->CI->db->get('rate_limits')->row_array();
            
            if (!$limit) {
                return null;
            }
            
            return [
                'attempts' => $limit['attempts'],
                'reset_at' => strtotime($limit['reset_at']),
                'is_expired' => time() >= strtotime($limit['reset_at'])
            ];
        } catch (Exception $e) {
            error_log("Failed to get rate limit status: " . $e->getMessage());
            return null;
        }
    }
}
