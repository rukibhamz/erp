<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JSON Helper Functions
 * 
 * Provides functions for safe JSON decoding and validation
 */

/**
 * Safely decode JSON string with validation
 * 
 * @param string $json JSON string to decode
 * @param bool $assoc Return associative array instead of object
 * @param mixed $default Default value to return if decoding fails
 * @return mixed Decoded JSON data or default value
 */
function safe_json_decode($json, $assoc = true, $default = []) {
    if (empty($json) || !is_string($json)) {
        return $default;
    }
    
    $decoded = json_decode($json, $assoc);
    
    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg() . ' - Input: ' . substr($json, 0, 100));
        return $default;
    }
    
    return $decoded;
}

/**
 * Validate and sanitize JSON array data
 * 
 * @param array $data Array data to validate
 * @param array $allowedKeys Optional whitelist of allowed keys
 * @param callable $sanitizer Optional sanitization function for values
 * @return array Validated and sanitized array
 */
function validate_json_array($data, $allowedKeys = null, $sanitizer = null) {
    if (!is_array($data)) {
        return [];
    }
    
    $result = [];
    
    foreach ($data as $key => $value) {
        // If allowedKeys is provided, only include whitelisted keys
        if ($allowedKeys !== null && !in_array($key, $allowedKeys)) {
            continue;
        }
        
        // Apply sanitizer if provided
        if ($sanitizer !== null && is_callable($sanitizer)) {
            $value = $sanitizer($value);
        } elseif (is_string($value)) {
            // Default: sanitize strings
            $value = sanitize_input($value);
        } elseif (is_array($value)) {
            // Recursively validate nested arrays
            $value = validate_json_array($value, null, $sanitizer);
        } elseif (is_numeric($value)) {
            // Ensure numeric values are properly typed
            $value = is_float($value) ? floatval($value) : intval($value);
        }
        
        $result[$key] = $value;
    }
    
    return $result;
}

/**
 * Safely decode and validate JSON from POST data
 * 
 * @param string $key POST key containing JSON string
 * @param array $allowedKeys Optional whitelist of allowed keys
 * @param mixed $default Default value if key doesn't exist or decode fails
 * @return array Validated array data
 */
function safe_json_post($key, $allowedKeys = null, $default = []) {
    $json = $_POST[$key] ?? '';
    $decoded = safe_json_decode($json, true, $default);
    
    if (!is_array($decoded)) {
        return $default;
    }
    
    return validate_json_array($decoded, $allowedKeys);
}

