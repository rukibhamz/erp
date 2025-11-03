<?php
/**
 * CSRF Protection Implementation
 * 
 * This file provides CSRF token generation and validation functions
 * Add this to application/helpers/csrf_helper.php
 */

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Generate CSRF token and store in session
 */
function generate_csrf_token() {
    if (!isset($_SESSION)) {
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
    if (!isset($_SESSION)) {
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
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token hidden input field
 */
function csrf_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Check CSRF token from POST request
 */
function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // Only validate POST requests
    }
    
    $token = $_POST['csrf_token'] ?? '';
    
    if (empty($token) || !validate_csrf_token($token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
    
    return true;
}

