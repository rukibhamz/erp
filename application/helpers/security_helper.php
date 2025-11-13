<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Security Helper Functions
 * 
 * Provides security-related helper functions for rate limiting,
 * IP access control, and file upload validation.
 */

/**
 * Rate limiting check for login attempts
 * 
 * SECURITY: Fails closed - returns false if database check fails
 * This prevents attackers from bypassing rate limiting by causing errors.
 * 
 * @param string $identifier Unique identifier (e.g., username|ip)
 * @param int $maxAttempts Maximum attempts allowed in time window
 * @param int $windowSeconds Time window in seconds (default: 15 minutes)
 * @return bool True if under limit, false if exceeded or check failed
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
        // SECURITY: Fail closed - block request if rate limit check fails
        // This prevents attackers from bypassing rate limiting by causing database errors
        // Log detailed error for monitoring and admin attention
        error_log('Rate limiting check failed: ' . $e->getMessage());
        error_log('Rate limiting failure details: ' . print_r([
            'identifier' => $identifier,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ], true));
        
        // TODO: Consider adding admin notification/alerting for persistent failures
        // This could indicate database issues or potential attack attempts
        
        // Fail closed for security - block request if rate limit check fails
        return false;
    }
}

/**
 * Check IP whitelist/blacklist
 * 
 * Checks if IP address is blacklisted or whitelisted.
 * Returns true if whitelist is enabled and IP is whitelisted, or if whitelist is disabled.
 * Returns false if IP is blacklisted.
 * 
 * @param string|null $ip IP address to check (defaults to REMOTE_ADDR)
 * @return bool True if access allowed, false if denied
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
        // SECURITY: Fail closed - deny access if IP check fails
        // This prevents attackers from bypassing IP restrictions by causing database errors
        error_log('IP access check failed: ' . $e->getMessage());
        error_log('IP access failure details: ' . print_r([
            'ip_address' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ], true));
        
        // Fail closed for security - deny access if IP check fails
        return false;
    }
}

/**
 * Enhanced file upload validation with server-side MIME type detection
 * 
 * SECURITY: Uses finfo_file() for accurate MIME type detection instead of trusting client data
 * - Verifies file is actually uploaded using is_uploaded_file()
 * - Matches file extension to detected MIME type
 * - Enforces 10MB file size limit
 * 
 * @param array $file $_FILES array element
 * @param array $allowedTypes Allowed MIME types (defaults to common types)
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateFileUpload($file, $allowedTypes = [
    'image/jpeg', 
    'image/png', 
    'image/gif', 
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
]) {
    // SECURITY: Verify file is actually uploaded (prevents path traversal)
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Invalid file upload - file must be uploaded via HTTP POST'];
    }
    
    // Check file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['valid' => false, 'error' => 'File size exceeds 10MB limit'];
    }
    
    // SECURITY: Use server-side MIME type detection (don't trust client data)
    if (!function_exists('finfo_open')) {
        return ['valid' => false, 'error' => 'File validation not available - finfo extension missing'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        return ['valid' => false, 'error' => 'File validation failed - cannot open fileinfo'];
    }
    
    $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!$detectedMimeType) {
        return ['valid' => false, 'error' => 'File validation failed - cannot detect MIME type'];
    }
    
    // MIME type to extension mapping for validation
    $allowedMappings = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx']
    ];
    
    // Check if detected MIME type is allowed
    if (!in_array($detectedMimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'File type not allowed - detected MIME type: ' . $detectedMimeType];
    }
    
    // Get file extension from filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (empty($extension)) {
        return ['valid' => false, 'error' => 'File must have a valid extension'];
    }
    
    // SECURITY: Verify extension matches detected MIME type
    if (!isset($allowedMappings[$detectedMimeType])) {
        return ['valid' => false, 'error' => 'File type validation failed - unknown MIME type mapping'];
    }
    
    if (!in_array($extension, $allowedMappings[$detectedMimeType])) {
        return [
            'valid' => false, 
            'error' => 'File extension does not match detected MIME type. Detected: ' . $detectedMimeType . ', Extension: .' . $extension
        ];
    }
    
    // SECURITY: Filename sanitization recommendation
    // Note: The actual filename should be sanitized when saving:
    // - Remove path components (../, /, \)
    // - Remove or replace special characters
    // - Use a unique filename (UUID or timestamp) to prevent conflicts
    // - Store original filename separately if needed for display
    
    return ['valid' => true];
}
