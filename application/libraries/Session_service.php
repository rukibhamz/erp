<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Session Service
 * 
 * Wraps $_SESSION access in a service layer for better abstraction
 * and easier testing. Provides type-safe session operations.
 * 
 * @package Application
 * @subpackage Libraries
 */
class Session_service {
    /**
     * Set session value
     * 
     * @param string $key Session key
     * @param mixed $value Value to store
     * @return void
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     * 
     * @param string $key Session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Session value or default
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Delete session value
     * 
     * @param string $key Session key
     * @return void
     */
    public function delete($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Check if session key exists
     * 
     * @param string $key Session key
     * @return bool True if key exists
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Destroy entire session
     * 
     * @return void
     */
    public function destroy() {
        session_destroy();
    }
    
    /**
     * Get all session data
     * 
     * @return array All session data
     */
    public function all() {
        return $_SESSION;
    }
    
    /**
     * Clear all session data (but keep session alive)
     * 
     * @return void
     */
    public function clear() {
        $_SESSION = [];
    }
    
    /**
     * Regenerate session ID
     * 
     * SECURITY: Prevents session fixation attacks
     * 
     * @param bool $deleteOldSession Whether to delete old session
     * @return bool True on success
     */
    public function regenerateId($deleteOldSession = true) {
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Get session ID
     * 
     * @return string Session ID
     */
    public function getId() {
        return session_id();
    }
}

