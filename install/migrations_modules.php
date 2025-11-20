<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modules Management Migration
 * Creates table for module configuration (activation, renaming)
 */

function migrations_modules($prefix) {
    $migrations = [
        'modules' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}modules` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module_key` varchar(50) NOT NULL COMMENT 'Internal module identifier (e.g., accounting, inventory)',
                `display_name` varchar(100) NOT NULL COMMENT 'Display name shown in navigation',
                `description` text DEFAULT NULL,
                `is_active` tinyint(1) DEFAULT 1 COMMENT '1=active, 0=inactive',
                `sort_order` int(11) DEFAULT 0 COMMENT 'Order in navigation menu',
                `icon` varchar(50) DEFAULT NULL COMMENT 'Bootstrap icon class',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `module_key` (`module_key`),
                KEY `is_active` (`is_active`),
                KEY `sort_order` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "
    ];
    
    // Insert default modules (works for both new and existing installations)
    $inserts = [
        "INSERT INTO `{$prefix}modules` (`module_key`, `display_name`, `description`, `is_active`, `sort_order`, `icon`) VALUES
        ('accounting', 'Accounting', 'Financial management, accounts, ledgers, and reports', 1, 1, 'bi-calculator'),
        ('staff_management', 'Staff Management', 'Employee and payroll management', 1, 2, 'bi-people-fill'),
        ('bookings', 'Bookings', 'Facility and resource booking management', 1, 3, 'bi-calendar-check'),
        ('properties', 'Properties', 'Property, space, and lease management', 1, 4, 'bi-building'),
        ('utilities', 'Utilities', 'Utility bills, meters, and consumption tracking', 1, 5, 'bi-lightning-charge'),
        ('inventory', 'Inventory', 'Stock management, items, and inventory tracking', 1, 6, 'bi-boxes'),
        ('tax', 'Tax', 'Tax compliance, VAT, PAYE, CIT, WHT management', 1, 7, 'bi-file-earmark-text'),
        ('pos', 'POS', 'Point of Sale system for retail transactions', 1, 8, 'bi-cash-register')
        ON DUPLICATE KEY UPDATE 
            `display_name` = VALUES(`display_name`),
            `description` = VALUES(`description`),
            `icon` = VALUES(`icon`),
            `is_active` = VALUES(`is_active`),
            `sort_order` = VALUES(`sort_order`);"
    ];
    
    return [
        'tables' => $migrations,
        'inserts' => $inserts
    ];
}

