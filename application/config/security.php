<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Security Configuration
 * 
 * Configuration for password policies, session management,
 * and other security-related settings
 */

// Password Policy
$config['password_policy'] = [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special' => true,
    'expiry_days' => 90,  // Password expires after 90 days
    'history_count' => 5,  // Can't reuse last 5 passwords
    'max_failed_attempts' => 5,
    'lockout_duration' => 900  // 15 minutes in seconds
];

// Session Security
$config['session_security'] = [
    'timeout' => 3600,  // 1 hour in seconds
    'warning_time' => 300,  // Show warning 5 minutes before timeout
    'regenerate_on_login' => true,
    'validate_ip' => false,  // Set to true for stricter security
    'validate_user_agent' => true,
    'max_concurrent_sessions' => 3
];

// Rate Limiting
$config['rate_limiting'] = [
    'enabled' => true,
    'login_attempts' => [
        'max_attempts' => 5,
        'window' => 300  // 5 minutes
    ],
    'api_requests' => [
        'max_requests' => 100,
        'window' => 60  // 1 minute
    ]
];

// Audit Trail
$config['audit_trail'] = [
    'enabled' => true,
    'log_all_actions' => false,  // If true, logs all actions; if false, only critical ones
    'critical_modules' => [
        'users',
        'accounts',
        'journal_entries',
        'invoices',
        'bills',
        'payroll',
        'settings'
    ],
    'critical_actions' => [
        'CREATE',
        'UPDATE',
        'DELETE',
        'LOGIN',
        'LOGOUT',
        'PASSWORD_CHANGE',
        'PERMISSION_CHANGE'
    ]
];

// File Upload Security
$config['file_upload'] = [
    'allowed_types' => 'jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx|csv',
    'max_size' => 5120,  // 5MB in KB
    'max_width' => 2048,
    'max_height' => 2048,
    'scan_for_malware' => false,  // Set to true if you have ClamAV or similar
    'sanitize_filename' => true
];

// CSRF Protection
$config['csrf_protection'] = [
    'enabled' => true,
    'token_name' => 'csrf_token',
    'cookie_name' => 'csrf_cookie',
    'expire' => 7200,  // 2 hours
    'regenerate' => true
];

// XSS Protection
$config['xss_protection'] = [
    'global_xss_filtering' => true,
    'sanitize_filename' => true,
    'allowed_html_tags' => '<p><br><strong><em><u><a><ul><ol><li>',
    'strip_image_tags' => true
];

// Encryption
$config['encryption'] = [
    'enabled' => true,
    'key' => '',  // Set this in your environment-specific config
    'driver' => 'openssl',
    'cipher' => 'AES-256-CBC'
];

// Security Headers
$config['security_headers'] = [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';"
];
