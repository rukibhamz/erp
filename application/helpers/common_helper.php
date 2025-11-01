<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function show_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

function format_date($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

function format_currency($amount, $currency = 'NGN') {
    // Load currency helper if not loaded
    if (!function_exists('get_currency_symbol')) {
        require_once BASEPATH . 'helpers/currency_helper.php';
    }
    
    $symbol = get_currency_symbol($currency);
    
    // Some currencies don't use decimals (JPY, KRW, etc.)
    $decimal_currencies = ['JPY', 'KRW', 'VND', 'CLP', 'ISK', 'UZS'];
    $decimals = in_array($currency, $decimal_currencies) ? 0 : 2;
    
    // Position of symbol (before or after)
    $symbol_before = ['USD', 'EUR', 'GBP', 'JPY', 'CNY', 'AUD', 'CAD', 'CHF', 'INR', 'BRL', 'MXN', 'SGD', 'HKD', 'NZD', 'TRY', 'RUB', 'SEK', 'NOK', 'DKK', 'PLN', 'ILS', 'THB', 'MYR', 'PHP', 'CZK', 'HUF', 'RON', 'BGN', 'NGN'];
    
    if (in_array($currency, $symbol_before)) {
        return $symbol . number_format($amount, $decimals);
    } else {
        return number_format($amount, $decimals) . ' ' . $symbol;
    }
}

function truncate_string($string, $length = 100, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $suffix;
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function has_permission($permission) {
    if (is_admin()) {
        return true;
    }
    // Add more permission logic here
    return false;
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (empty($datetime)) {
            return 'Unknown';
        }
        
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }
}

