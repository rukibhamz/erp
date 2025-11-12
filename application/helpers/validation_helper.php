<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Validation Helper Functions
 * Provides centralized validation for common field types
 */

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
if (!function_exists('validate_email')) {
    function validate_email($email) {
        if (empty($email)) {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Validate phone number (international format)
 * Accepts: +1234567890, 123-456-7890, (123) 456-7890, 123.456.7890, etc.
 * @param string $phone
 * @return bool
 */
if (!function_exists('validate_phone')) {
    function validate_phone($phone) {
        if (empty($phone)) {
            return false;
        }
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        // Check if it has at least 10 digits (minimum for phone numbers)
        $digits = preg_replace('/[^\d]/', '', $cleaned);
        return strlen($digits) >= 10 && strlen($digits) <= 15;
    }
}

/**
 * Validate name (letters, spaces, hyphens, apostrophes)
 * @param string $name
 * @param int $minLength Minimum length (default 2)
 * @param int $maxLength Maximum length (default 100)
 * @return bool
 */
if (!function_exists('validate_name')) {
    function validate_name($name, $minLength = 2, $maxLength = 100) {
        if (empty($name)) {
            return false;
        }
        $length = strlen(trim($name));
        if ($length < $minLength || $length > $maxLength) {
            return false;
        }
        // Allow letters, spaces, hyphens, apostrophes, and common name characters
        return preg_match('/^[\p{L}\s\-\'\.]+$/u', $name) === 1;
    }
}

/**
 * Validate numeric code (account numbers, SKUs, etc.)
 * @param string $code
 * @param bool $allowEmpty If true, empty string is valid
 * @return bool
 */
if (!function_exists('validate_numeric_code')) {
    function validate_numeric_code($code, $allowEmpty = true) {
        if (empty($code)) {
            return $allowEmpty;
        }
        return preg_match('/^\d+$/', $code) === 1;
    }
}

/**
 * Validate alphanumeric code (codes that may contain letters and numbers)
 * @param string $code
 * @param bool $allowEmpty If true, empty string is valid
 * @param string $allowedChars Additional allowed characters (default: -_)
 * @return bool
 */
if (!function_exists('validate_alphanumeric_code')) {
    function validate_alphanumeric_code($code, $allowEmpty = true, $allowedChars = '-_') {
        if (empty($code)) {
            return $allowEmpty;
        }
        $pattern = '/^[a-zA-Z0-9' . preg_quote($allowedChars, '/') . ']+$/';
        return preg_match($pattern, $code) === 1;
    }
}

/**
 * Validate URL
 * @param string $url
 * @return bool
 */
if (!function_exists('validate_url')) {
    function validate_url($url) {
        if (empty($url)) {
            return false;
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

/**
 * Validate date
 * @param string $date Date string
 * @param string $format Date format (default: Y-m-d)
 * @return bool
 */
if (!function_exists('validate_date')) {
    function validate_date($date, $format = 'Y-m-d') {
        if (empty($date)) {
            return false;
        }
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

/**
 * Validate password strength
 * @param string $password
 * @param int $minLength Minimum length (default: 8)
 * @param bool $requireUppercase
 * @param bool $requireLowercase
 * @param bool $requireNumber
 * @param bool $requireSpecial
 * @return array ['valid' => bool, 'errors' => array]
 */
if (!function_exists('validate_password')) {
    function validate_password($password, $minLength = 8, $requireUppercase = true, $requireLowercase = true, $requireNumber = true, $requireSpecial = true) {
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long.";
        }
        
        if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        
        if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter.";
        }
        
        if ($requireNumber && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number.";
        }
        
        if ($requireSpecial && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

/**
 * Check if a value is empty (including whitespace-only strings)
 * Used for auto-generate fields
 * @param mixed $value
 * @return bool True if value is empty or whitespace-only
 */
if (!function_exists('is_empty_or_whitespace')) {
    function is_empty_or_whitespace($value) {
        if ($value === null || $value === false) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        return empty($value);
    }
}

/**
 * Sanitize phone number (remove non-digit characters except +)
 * @param string $phone
 * @return string
 */
if (!function_exists('sanitize_phone')) {
    function sanitize_phone($phone) {
        if (empty($phone)) {
            return '';
        }
        // Keep + at the start if present, then digits
        $phone = trim($phone);
        if (strpos($phone, '+') === 0) {
            return '+' . preg_replace('/[^\d]/', '', substr($phone, 1));
        }
        return preg_replace('/[^\d]/', '', $phone);
    }
}

/**
 * Sanitize name (remove special characters except allowed ones)
 * @param string $name
 * @return string
 */
if (!function_exists('sanitize_name')) {
    function sanitize_name($name) {
        if (empty($name)) {
            return '';
        }
        // Allow letters, spaces, hyphens, apostrophes, periods
        return preg_replace('/[^\p{L}\s\-\'\.]/u', '', trim($name));
    }
}

/**
 * Validate and sanitize integer value
 * SECURITY: Prevents SQL injection by ensuring value is a valid integer within bounds
 * 
 * @param mixed $value Value to validate
 * @param int|null $min Minimum allowed value (null = no minimum)
 * @param int|null $max Maximum allowed value (null = no maximum)
 * @param int $default Default value if validation fails
 * @return int Validated integer value
 */
if (!function_exists('validate_integer')) {
    function validate_integer($value, $min = null, $max = null, $default = 0) {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int === false) {
            return $default;
        }
        if ($min !== null && $int < $min) {
            return $min;
        }
        if ($max !== null && $int > $max) {
            return $max;
        }
        return $int;
    }
}

