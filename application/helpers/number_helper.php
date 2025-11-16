<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Format large numbers with M (million) or B (billion) suffix
 * Displays full number on hover via title attribute
 * 
 * @param float|int $number The number to format
 * @param int $decimals Number of decimal places (default: 1)
 * @return string Formatted number with HTML title attribute
 */
function format_large_number($number, $decimals = 1) {
    $number = floatval($number);
    $fullNumber = number_format($number, 2);
    
    if ($number >= 1000000000) {
        // Billions
        $formatted = number_format($number / 1000000000, $decimals) . 'B';
    } elseif ($number >= 1000000) {
        // Millions
        $formatted = number_format($number / 1000000, $decimals) . 'M';
    } else {
        // Less than million - show full number
        $formatted = number_format($number, $decimals);
        $fullNumber = $formatted; // No need for title if showing full number
    }
    
    // Add title attribute with full number for hover
    if ($number >= 1000000) {
        return '<span title="' . htmlspecialchars($fullNumber) . '">' . htmlspecialchars($formatted) . '</span>';
    }
    
    return htmlspecialchars($formatted);
}

