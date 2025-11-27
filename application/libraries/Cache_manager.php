<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cache Manager Library
 * 
 * Simple file-based caching system for expensive operations
 * 
 * @package    ERP
 * @subpackage Libraries
 * @category   Performance
 */
class Cache_manager {
    
    private $CI;
    private $cache_path;
    private $default_ttl = 3600; // 1 hour
    
    public function __construct() {
        $this->CI =& get_instance();
        
        // Set cache directory
        $this->cache_path = APPPATH . 'cache/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_path)) {
            mkdir($this->cache_path, 0755, true);
        }
    }
    
    /**
     * Get cached data
     * 
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($filename));
        
        // Check if expired
        if ($data['expires'] < time()) {
            unlink($filename);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set cached data
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return bool Success status
     */
    public function set($key, $value, $ttl = null) {
        if ($ttl === null) {
            $ttl = $this->default_ttl;
        }
        
        $filename = $this->getCacheFilename($key);
        
        $data = [
            'key' => $key,
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($filename, serialize($data), LOCK_EX) !== false;
    }
    
    /**
     * Delete cached data
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     * 
     * @return int Number of files deleted
     */
    public function clear() {
        $files = glob($this->cache_path . '*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Clean expired cache files
     * 
     * @return int Number of files deleted
     */
    public function cleanExpired() {
        $files = glob($this->cache_path . '*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            $data = unserialize(file_get_contents($file));
            
            if ($data['expires'] < time()) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Remember: Get from cache or execute callback and cache result
     * 
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int $ttl Time to live in seconds
     * @return mixed Cached or fresh data
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function getStats() {
        $files = glob($this->cache_path . '*.cache');
        $total_size = 0;
        $expired = 0;
        $active = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            $data = unserialize(file_get_contents($file));
            
            if ($data['expires'] < time()) {
                $expired++;
            } else {
                $active++;
            }
        }
        
        return [
            'total_files' => count($files),
            'active_files' => $active,
            'expired_files' => $expired,
            'total_size_mb' => round($total_size / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Get cache filename for a key
     * 
     * @param string $key Cache key
     * @return string Full path to cache file
     */
    private function getCacheFilename($key) {
        return $this->cache_path . md5($key) . '.cache';
    }
    
    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
}
