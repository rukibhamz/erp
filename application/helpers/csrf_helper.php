<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CSRF Protection Helper Functions
 */

/**
 * Generate CSRF token and store in session
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
 * Call this at the start of all POST handlers
 */
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // Only validate POST requests
    }
    
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = $_POST['csrf_token'] ?? '';
    
    // Debug: Log token validation attempt (remove in production)
    if (empty($token)) {
        error_log('CSRF check failed: No token provided. POST data: ' . json_encode($_POST));
    } elseif (!isset($_SESSION['csrf_token'])) {
        error_log('CSRF check failed: No token in session. Session keys: ' . json_encode(array_keys($_SESSION ?? [])));
    } elseif (!validate_csrf_token($token)) {
        error_log('CSRF check failed: Token mismatch. Expected: ' . substr($_SESSION['csrf_token'] ?? '', 0, 10) . '... Got: ' . substr($token, 0, 10) . '...');
    }
    
    if (empty($token) || !validate_csrf_token($token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
    
    // Regenerate token after successful validation (token rotation)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    return true;
}

