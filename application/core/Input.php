<?php
/**
 * Input Class
 * 
 * Provides safe access to $_GET, $_POST, and $_SESSION variables.
 * Designed to mimic the CodeIgniter 3 Input class for ported controllers.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Input {
    
    /**
     * Get a value from $_GET
     * 
     * @param string|null $index Index name, or null for the entire array
     * @param bool $xss_clean Whether to clean the input (placeholder for now)
     * @return mixed
     */
    public function get($index = null, $xss_clean = false) {
        if ($index === null) {
            return $_GET;
        }
        
        $value = $_GET[$index] ?? null;
        
        if ($xss_clean) {
            $value = $this->xss_clean($value);
        }
        
        return $value;
    }
    
    /**
     * Get a value from $_POST
     * 
     * @param string|null $index Index name, or null for the entire array
     * @param bool $xss_clean Whether to clean the input (placeholder for now)
     * @return mixed
     */
    public function post($index = null, $xss_clean = false) {
        if ($index === null) {
            return $_POST;
        }
        
        $value = $_POST[$index] ?? null;
        
        if ($xss_clean) {
            $value = $this->xss_clean($value);
        }
        
        return $value;
    }
    
    /**
     * Get a value from $_SERVER
     * 
     * @param string|null $index Index name
     * @return mixed
     */
    public function server($index = null) {
        if ($index === null) {
            return $_SERVER;
        }
        return $_SERVER[$index] ?? null;
    }
    
    /**
     * Placeholder for XSS cleaning
     * 
     * @param mixed $value
     * @return mixed
     */
    private function xss_clean($value) {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->xss_clean($v);
            }
            return $value;
        }
        
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
