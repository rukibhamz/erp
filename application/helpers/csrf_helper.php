<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CSRF Protection Helper Functions
 * SECURITY: Implements CSRF (Cross-Site Request Forgery) protection using token-based validation
 * 
 * This helper provides:
 * - Token generation using cryptographically secure random bytes
 * - Token validation using timing-safe comparison (hash_equals)
 * - Automatic token rotation after successful validation
 * - Support for both form submissions and AJAX requests
 */

/**
 * Generate CSRF token and store in session
 * SECURITY: Uses cryptographically secure random_bytes() for token generation
 * 
 * @return string The generated CSRF token
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        // Generate 32 bytes (64 hex characters) of cryptographically secure random data
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time(); // Track token creation time
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Get current CSRF token
 */
function get_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return generate_csrf_token();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        // If no token exists, generate one (shouldn't happen, but handle gracefully)
        error_log('CSRF validation: No token in session, generating new one');
        generate_csrf_token();
        return false; // Still fail validation, but token is now generated for next attempt
    }
    
    // Use hash_equals for timing attack prevention
    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    
    if (!$isValid) {
        error_log('CSRF validation failed: Token mismatch. Session token exists: ' . (isset($_SESSION['csrf_token']) ? 'yes' : 'no'));
    }
    
    return $isValid;
}

/**
 * Generate CSRF token hidden input field
 */
function csrf_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Check CSRF token from POST request
 * SECURITY: Validates CSRF token for POST requests to prevent CSRF attacks
 * Call this at the start of all POST handlers
 * 
 * @param bool $allowGet Allow GET requests without validation (default: false)
 * @return bool True if validation passes, dies with 403 if it fails
 */
function check_csrf($allowGet = false) {
    // Only validate POST, PUT, PATCH, DELETE requests
    $methodsToValidate = ['POST', 'PUT', 'PATCH', 'DELETE'];
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if (!in_array($requestMethod, $methodsToValidate)) {
        if ($allowGet) {
            return true; // GET requests allowed without validation
        }
        // For other methods, still validate if they contain a body
        if ($requestMethod === 'GET') {
            return true;
        }
    }
    
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Get token from POST or from custom header (for AJAX requests)
    $token = $_POST['csrf_token'] ?? '';
    
    // Also check X-CSRF-Token header for AJAX requests
    if (empty($token) && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    
    // Security logging for failed attempts
    if (empty($token)) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        error_log("CSRF check failed: No token provided. IP: {$ipAddress}, Method: {$requestMethod}, User-Agent: {$userAgent}");
    } elseif (!isset($_SESSION['csrf_token'])) {
        error_log('CSRF check failed: No token in session. Session may have expired.');
    } elseif (!validate_csrf_token($token)) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        error_log("CSRF check failed: Token mismatch. IP: {$ipAddress}, Method: {$requestMethod}");
    }
    
    // Validate token
    if (empty($token) || !validate_csrf_token($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        
        // Return JSON for AJAX requests, HTML for regular form submissions
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            die(json_encode([
                'success' => false,
                'error' => 'Invalid CSRF token. Please refresh the page and try again.',
                'code' => 'CSRF_TOKEN_INVALID'
            ]));
        }
        
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
    
    // Regenerate token after successful validation (token rotation for security)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    
    return true;
}

