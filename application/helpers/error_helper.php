<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Standardized Error Logging Helper
 * 
 * Provides consistent error logging across the application
 * Uses Error_logger library when available, falls back to error_log()
 * 
 * @package    ERP
 * @subpackage Helpers
 * @category   Logging
 */

/**
 * Log an error message with optional context
 * 
 * @param string $message Error message
 * @param array $context Additional context data
 * @param string $level Log level (error, warning, info, debug)
 * @return void
 */
function log_error($message, $context = [], $level = 'error') {
    // Use Error_logger if available
    if (class_exists('Error_logger')) {
        try {
            $logger = new Error_logger();
            $logger->log($level, $message, $context);
            return;
        } catch (Exception $e) {
            // Fall through to error_log if Error_logger fails
        }
    }
    
    // Fallback to error_log
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $levelStr = strtoupper($level);
    error_log("[{$levelStr}] {$message}{$contextStr}");
}

/**
 * Log a warning message
 * 
 * @param string $message Warning message
 * @param array $context Additional context data
 * @return void
 */
function log_warning($message, $context = []) {
    log_error($message, $context, 'warning');
}

/**
 * Log an info message
 * 
 * @param string $message Info message
 * @param array $context Additional context data
 * @return void
 */
function log_info($message, $context = []) {
    log_error($message, $context, 'info');
}

/**
 * Log a debug message
 * 
 * @param string $message Debug message
 * @param array $context Additional context data
 * @return void
 */
function log_debug($message, $context = []) {
    log_error($message, $context, 'debug');
}
