<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Logging Configuration
 * 
 * Configuration for the Error_logger library
 */

// Enable/disable logging methods
$config['log_to_database'] = true;
$config['log_to_file'] = true;
$config['email_on_critical'] = true;

// Log file settings
$config['max_log_size'] = 10485760;  // 10MB
$config['log_rotation'] = true;
$config['compress_old_logs'] = true;

// Database logging settings
$config['log_retention_days'] = 90;  // Keep logs for 90 days

// Email settings for critical errors
$config['admin_email'] = 'admin@example.com';  // Change this!
$config['critical_error_subject'] = '[CRITICAL ERROR] ERP System';

// Log levels to record (set to false to disable a level)
$config['log_levels'] = [
    'DEBUG' => true,
    'INFO' => true,
    'WARNING' => true,
    'ERROR' => true,
    'CRITICAL' => true
];

// Modules to exclude from logging (for performance)
$config['exclude_modules'] = [
    // 'dashboard',  // Uncomment to exclude dashboard from logging
];
