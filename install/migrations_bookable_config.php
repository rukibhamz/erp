<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration to create bookable_config table
 */
function runBookableConfigMigration($pdo, $prefix = '') {
    try {
        error_log('Running bookable_config table creation migration...');
        
        $tableName = $prefix . 'bookable_config';
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `space_id` int(11) NOT NULL,
            `is_bookable` tinyint(1) DEFAULT 1,
            `booking_types` text DEFAULT NULL,
            `minimum_duration` int(11) DEFAULT 1,
            `maximum_duration` int(11) DEFAULT NULL,
            `advance_booking_days` int(11) DEFAULT 365,
            `cancellation_policy_id` int(11) DEFAULT NULL,
            `pricing_rules` text DEFAULT NULL,
            `availability_rules` text DEFAULT NULL,
            `setup_time_buffer` int(11) DEFAULT 0,
            `cleanup_time_buffer` int(11) DEFAULT 0,
            `simultaneous_limit` int(11) DEFAULT 1,
            `last_synced_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `space_id` (`space_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        error_log("Created table {$tableName}");
        
        return true;
    } catch (PDOException $e) {
        error_log('Bookable config table migration error: ' . $e->getMessage());
        return false;
    }
}
