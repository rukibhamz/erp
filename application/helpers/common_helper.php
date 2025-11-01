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

function format_currency($amount, $currency = 'USD') {
    $formats = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥'
    ];
    $symbol = $formats[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
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

