<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Common Helper Functions
 */

/**
 * Sanitize input data
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Format currency
 */
if (!function_exists('format_currency')) {
    function format_currency($amount, $currency = 'NGN') {
        return '₦' . number_format((float)$amount, 2);
    }
}

/**
 * Time ago function
 */
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (empty($datetime)) return '';
        
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
        if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
        return floor($diff / 31536000) . ' years ago';
    }
}
