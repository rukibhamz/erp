<?php
/**
 * BMS Database Repair Tool v1.0
 * 
 * Specifically fixes missing columns in bookable_config table.
 */

if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/application/');
require_once __DIR__ . '/application/core/Database.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "--- BMS DATABASE REPAIR ---\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $prefix = $db->getPrefix();
    
    $table = $prefix . "bookable_config";
    
    // Check columns
    $cols = $db->fetchAll("DESCRIBE `{$table}`");
    $col_names = array_column($cols, 'Field');
    
    $to_fix = [
        'pricing_rules' => "ALTER TABLE `{$table}` ADD COLUMN `pricing_rules` TEXT DEFAULT NULL AFTER `cancellation_policy_id`",
        'booking_types' => "ALTER TABLE `{$table}` ADD COLUMN `booking_types` TEXT DEFAULT NULL AFTER `is_bookable`"
    ];
    
    foreach ($to_fix as $col => $sql) {
        if (!in_array($col, $col_names)) {
            echo "Fixing missing column: {$col}... ";
            $conn->exec($sql);
            echo "DONE\n";
        } else {
            echo "Column {$col} already exists.\n";
        }
    }
    
    echo "\nREPAIR COMPLETE. Please run diagnostics again to verify.\n";

} catch (Exception $e) {
    echo "\nFATAL ERROR DURING REPAIR: " . $e->getMessage() . "\n";
}
