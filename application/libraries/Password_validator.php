<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Password Validator Library
 * 
 * Validates passwords against security policy and manages password history
 * 
 * @package    ERP
 * @subpackage Libraries
 * @category   Security
 * @author     ERP Development Team
 */
class Password_validator {
    
    private $CI;
    private $policy = [];
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Load password policy from config
        if (file_exists(APPPATH . 'config/security.php')) {
            $this->CI->config->load('security', TRUE);
            $config = $this->CI->config->item('security');
            $this->policy = $config['password_policy'] ?? [];
        }
        
        // Set defaults if not configured
        $this->policy = array_merge([
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special' => true,
            'expiry_days' => 90,
            'history_count' => 5
        ], $this->policy);
    }
    
    /**
     * Validate password against policy
     * 
     * @param string $password Password to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate($password) {
        $errors = [];
        
        // Check minimum length
        if (strlen($password) < $this->policy['min_length']) {
            $errors[] = "Password must be at least {$this->policy['min_length']} characters long";
        }
        
        // Check for uppercase letter
        if ($this->policy['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        // Check for lowercase letter
        if ($this->policy['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        // Check for number
        if ($this->policy['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        // Check for special character
        if ($this->policy['require_special'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Check if password has been used recently
     * 
     * @param int $user_id User ID
     * @param string $password Password to check
     * @return bool True if password was used recently
     */
    public function isPasswordReused($user_id, $password) {
        try {
            $history_count = $this->policy['history_count'];
            
            // Get recent password hashes
            $this->CI->db->select('password_hash');
            $this->CI->db->from('password_history');
            $this->CI->db->where('user_id', $user_id);
            $this->CI->db->order_by('created_at', 'DESC');
            $this->CI->db->limit($history_count);
            
            $history = $this->CI->db->get()->result_array();
            
            // Check if new password matches any recent passwords
            foreach ($history as $entry) {
                if (password_verify($password, $entry['password_hash'])) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Failed to check password history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add password to history
     * 
     * @param int $user_id User ID
     * @param string $password_hash Hashed password
     * @return bool Success status
     */
    public function addToHistory($user_id, $password_hash) {
        try {
            // Add to history
            $this->CI->db->insert('password_history', [
                'user_id' => $user_id,
                'password_hash' => $password_hash
            ]);
            
            // Clean old history entries (keep only the configured number)
            $history_count = $this->policy['history_count'];
            
            $this->CI->db->query("
                DELETE FROM password_history
                WHERE user_id = ? 
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM password_history
                        WHERE user_id = ?
                        ORDER BY created_at DESC
                        LIMIT ?
                    ) AS recent
                )
            ", [$user_id, $user_id, $history_count]);
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to add password to history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if password has expired
     * 
     * @param int $user_id User ID
     * @return bool True if password has expired
     */
    public function isPasswordExpired($user_id) {
        try {
            $this->CI->db->select('password_expires_at');
            $this->CI->db->from('users');
            $this->CI->db->where('id', $user_id);
            
            $user = $this->CI->db->get()->row_array();
            
            if (!$user || !$user['password_expires_at']) {
                return false;
            }
            
            return strtotime($user['password_expires_at']) < time();
        } catch (Exception $e) {
            error_log("Failed to check password expiry: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update password expiry date
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function updatePasswordExpiry($user_id) {
        try {
            $expiry_days = $this->policy['expiry_days'];
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
            
            $this->CI->db->where('id', $user_id);
            $this->CI->db->update('users', [
                'password_changed_at' => date('Y-m-d H:i:s'),
                'password_expires_at' => $expires_at
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to update password expiry: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get password policy requirements as a string
     * 
     * @return string Human-readable policy requirements
     */
    public function getPolicyDescription() {
        $requirements = [];
        
        $requirements[] = "At least {$this->policy['min_length']} characters";
        
        if ($this->policy['require_uppercase']) {
            $requirements[] = "One uppercase letter";
        }
        
        if ($this->policy['require_lowercase']) {
            $requirements[] = "One lowercase letter";
        }
        
        if ($this->policy['require_numbers']) {
            $requirements[] = "One number";
        }
        
        if ($this->policy['require_special']) {
            $requirements[] = "One special character";
        }
        
        return "Password must contain: " . implode(', ', $requirements);
    }
    
    /**
     * Generate a strong random password
     * 
     * @param int $length Password length
     * @return string Generated password
     */
    public function generatePassword($length = 12) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        
        // Ensure at least one of each required type
        if ($this->policy['require_uppercase']) {
            $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        }
        if ($this->policy['require_lowercase']) {
            $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        }
        if ($this->policy['require_numbers']) {
            $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        }
        if ($this->policy['require_special']) {
            $password .= $special[random_int(0, strlen($special) - 1)];
        }
        
        // Fill the rest randomly
        $all_chars = $uppercase . $lowercase . $numbers . $special;
        $remaining = $length - strlen($password);
        
        for ($i = 0; $i < $remaining; $i++) {
            $password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }
}
