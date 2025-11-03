<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Security Helper Functions
 */

/**
 * Rate limiting check for login attempts
 */
function checkRateLimit($identifier, $maxAttempts = 5, $windowSeconds = 900) {
    try {
        $db = Database::getInstance();
        $prefix = $db->getPrefix();
        
        // Clean old entries
        $db->execute("DELETE FROM `{$prefix}rate_limits` WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)", [$windowSeconds]);
        
        // Count attempts in window
        $attempts = $db->fetchOne(
            "SELECT COUNT(*) as count FROM `{$prefix}rate_limits` 
             WHERE identifier = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$identifier, $windowSeconds]
        );
        
        if (($attempts['count'] ?? 0) >= $maxAttempts) {
            return false; // Rate limit exceeded
        }
        
        // Record attempt
        $db->insert('rate_limits', [
            'identifier' => $identifier,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    } catch (Exception $e) {
        // If table doesn't exist, allow the request (fail open for usability)
        // Log the error for admin attention
        error_log('Rate limiting check failed: ' . $e->getMessage());
        return true; // Allow request if rate limiting isn't working
    }
}

/**
 * Check IP whitelist/blacklist
 */
function checkIPAccess($ip = null) {
    try {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        
        $db = Database::getInstance();
        $prefix = $db->getPrefix();
        
        // Check blacklist
        $blacklisted = $db->fetchOne(
            "SELECT * FROM `{$prefix}ip_restrictions` 
             WHERE ip_address = ? AND type = 'blacklist' AND is_active = 1",
            [$ip]
        );
        
        if ($blacklisted) {
            return false;
        }
        
        // Check whitelist (if enabled)
        $whitelistEnabled = $db->fetchOne(
            "SELECT * FROM `{$prefix}settings` WHERE setting_key = 'enable_ip_whitelist' AND setting_value = '1'"
        );
        
        if ($whitelistEnabled) {
            $whitelisted = $db->fetchOne(
                "SELECT * FROM `{$prefix}ip_restrictions` 
                 WHERE ip_address = ? AND type = 'whitelist' AND is_active = 1",
                [$ip]
            );
            
            return (bool)$whitelisted;
        }
        
        return true; // Access allowed
    } catch (Exception $e) {
        // If tables don't exist, allow access (fail open)
        error_log('IP access check failed: ' . $e->getMessage());
        return true;
    }
}

/**
 * Enhanced file upload validation
 */
function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Invalid file upload'];
    }
    
    // Check file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['valid' => false, 'error' => 'File size exceeds 10MB limit'];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'File extension not allowed'];
    }
    
    return ['valid' => true];
}
