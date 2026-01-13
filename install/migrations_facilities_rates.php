<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration to add missing rate columns to facilities table
 * Fixes: half_day_rate, weekly_rate, is_bookable, max_duration columns
 */
function runFacilitiesRatesMigrations($pdo, $prefix = '') {
    try {
        error_log('Running facilities rates migration...');
        
        // Helper function to check if column exists
        $columnExists = function($table, $column) use ($pdo, $prefix) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$prefix}{$table}` LIKE '{$column}'");
                return $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                return false;
            }
        };
        
        // Add half_day_rate column if missing
        if (!$columnExists('facilities', 'half_day_rate')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `half_day_rate` DECIMAL(15,2) DEFAULT 0.00 AFTER `daily_rate`");
            error_log('Added half_day_rate column to facilities table');
        }
        
        // Add weekly_rate column if missing
        if (!$columnExists('facilities', 'weekly_rate')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `weekly_rate` DECIMAL(15,2) DEFAULT 0.00 AFTER `half_day_rate`");
            error_log('Added weekly_rate column to facilities table');
        }
        
        // Add is_bookable column if missing
        if (!$columnExists('facilities', 'is_bookable')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `is_bookable` TINYINT(1) DEFAULT 1 AFTER `status`");
            error_log('Added is_bookable column to facilities table');
        }
        
        // Add max_duration column if missing
        if (!$columnExists('facilities', 'max_duration')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `max_duration` INT(11) DEFAULT NULL COMMENT 'Max duration in hours' AFTER `minimum_duration`");
            error_log('Added max_duration column to facilities table');
        }
        
        // Add resource_type column if missing
        if (!$columnExists('facilities', 'resource_type')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `resource_type` VARCHAR(50) DEFAULT 'other' AFTER `features`");
            error_log('Added resource_type column to facilities table');
        }
        
        // Add category column if missing
        if (!$columnExists('facilities', 'category')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `category` VARCHAR(100) DEFAULT NULL AFTER `resource_type`");
            error_log('Added category column to facilities table');
        }
        
        // Add member_rate column if missing
        if (!$columnExists('facilities', 'member_rate')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `member_rate` DECIMAL(15,2) DEFAULT NULL AFTER `weekly_rate`");
            error_log('Added member_rate column to facilities table');
        }
        
        // Add simultaneous_limit column if missing
        if (!$columnExists('facilities', 'simultaneous_limit')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `simultaneous_limit` INT(11) DEFAULT 1 AFTER `max_duration`");
            error_log('Added simultaneous_limit column to facilities table');
        }
        
        // Add lead_time column if missing
        if (!$columnExists('facilities', 'lead_time')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `lead_time` INT(11) DEFAULT 0 COMMENT 'Days in advance' AFTER `simultaneous_limit`");
            error_log('Added lead_time column to facilities table');
        }
        
        // Add cutoff_time column if missing
        if (!$columnExists('facilities', 'cutoff_time')) {
            $pdo->exec("ALTER TABLE `{$prefix}facilities` 
                        ADD COLUMN `cutoff_time` INT(11) DEFAULT 0 COMMENT 'Hours before booking' AFTER `lead_time`");
            error_log('Added cutoff_time column to facilities table');
        }
        
        error_log('Facilities rates migration completed successfully');
        return true;
    } catch (PDOException $e) {
        error_log('Facilities rates migration error: ' . $e->getMessage());
        return false;
    }
}
