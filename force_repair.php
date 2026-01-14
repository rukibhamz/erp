<?php
/**
 * BMS Database Repair Tool v2.0 (Ultra-Robust)
 * 
 * This script performs a granular check of the bookable_config table
 * and adds any missing columns one by one without relying on column order.
 */

if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/application/');
require_once __DIR__ . '/application/core/Database.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "====================================================\n";
echo "   BMS DATABASE REPAIR TOOL v2.0 (Ultra-Robust)     \n";
echo "====================================================\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $prefix = $db->getPrefix();
    $table = $prefix . "bookable_config";
    
    echo "Checking table: {$table}\n";

    // 1. Get current columns
    $cols = $db->fetchAll("DESCRIBE `{$table}`");
    if (!$cols) {
        throw new Exception("Could not find table '{$table}'. Please ensure the table exists or run main migrations first.");
    }
    
    $col_names = array_map('strtolower', array_column($cols, 'Field'));
    
    // 2. Define ALL required columns and their definitions
    $required_columns = [
        'is_bookable' => "TINYINT(1) DEFAULT 1",
        'booking_types' => "TEXT DEFAULT NULL",
        'minimum_duration' => "INT(11) DEFAULT 1",
        'maximum_duration' => "INT(11) DEFAULT NULL",
        'advance_booking_days' => "INT(11) DEFAULT 365",
        'cancellation_policy_id' => "INT(11) DEFAULT NULL",
        'pricing_rules' => "TEXT DEFAULT NULL",
        'availability_rules' => "TEXT DEFAULT NULL",
        'setup_time_buffer' => "INT(11) DEFAULT 0",
        'cleanup_time_buffer' => "INT(11) DEFAULT 0",
        'simultaneous_limit' => "INT(11) DEFAULT 1",
        'last_synced_at' => "DATETIME DEFAULT NULL",
        'created_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];

    $changes_made = 0;
    
    // 3. Granularly add missing columns
    foreach ($required_columns as $col => $definition) {
        if (!in_array(strtolower($col), $col_names)) {
            echo "Column '{$col}' is MISSING. Attempting to add... ";
            try {
                $conn->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition}");
                echo "SUCCESS\n";
                $changes_made++;
            } catch (Exception $e) {
                echo "FAILED: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Column '{$col}' is Present. OK.\n";
        }
    }

    echo "\n----------------------------------------------------\n";
    if ($changes_made > 0) {
        echo "REPAIR COMPLETE: {$changes_made} column(s) were added.\n";
    } else {
        echo "NO CHANGES NEEDED: All columns are already present.\n";
    }
    echo "----------------------------------------------------\n\n";
    
    echo "Now checking erp_facilities table for completeness...\n";
    $f_cols = $db->fetchAll("DESCRIBE `{$prefix}facilities` ");
    $f_col_names = array_map('strtolower', array_column($f_cols, 'Field'));
    
    $f_required = [
        'half_day_rate' => "DECIMAL(15,2) DEFAULT 0.00",
        'weekly_rate' => "DECIMAL(15,2) DEFAULT 0.00",
        'is_bookable' => "TINYINT(1) DEFAULT 1",
        'max_duration' => "INT(11) DEFAULT NULL",
        'simultaneous_limit' => "INT(11) DEFAULT 1"
    ];
    
    foreach ($f_required as $col => $definition) {
        if (!in_array(strtolower($col), $f_col_names)) {
            echo "Facility Column '{$col}' is MISSING. Adding... ";
            try {
                $conn->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `{$col}` {$definition}");
                echo "SUCCESS\n";
            } catch (Exception $e) {
                echo "FAILED: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nAll checks complete. Please run diagnose_booking_system.php again to verify.\n";

} catch (Exception $e) {
    echo "\nFATAL ERROR DURING REPAIR: " . $e->getMessage() . "\n";
}
