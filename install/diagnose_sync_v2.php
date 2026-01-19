<?php
/**
 * Comprehensive Diagnostic and Repair Script for cPanel Sync Issues
 * This script checks for all database schema requirements for the latest updates.
 */

// Load database configuration
$db_config_path = dirname(__DIR__) . '/application/config/database.php';
if (!file_exists($db_config_path)) {
    die("Error: Could not find database configuration at $db_config_path\n");
}

// Mocking CodeIgniter's database config loading
$db = [];
$active_group = 'default';
$query_builder = TRUE;
require($db_config_path);

$config = $db['default'];
$dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset={$config['char_set']}";
$username = $config['username'];
$password = $config['password'];
$prefix = $config['dbprefix'];

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h1>ERP System Diagnostic & Repair</h1>";
    echo "<p>Connected to database: <strong>{$config['database']}</strong></p>";

    $checkColumn = function($table, $column) use ($pdo, $prefix) {
        $fullTable = $prefix . $table;
        $stmt = $pdo->query("SHOW COLUMNS FROM `$fullTable` LIKE '$column'");
        return $stmt->rowCount() > 0;
    };

    $checkTable = function($table) use ($pdo, $prefix) {
        $fullTable = $prefix . $table;
        $stmt = $pdo->query("SHOW TABLES LIKE '$fullTable'");
        return $stmt->rowCount() > 0;
    };

    echo "<h2>1. Checking Database Schema</h2>";
    echo "<ul>";

    // --- SPACES TABLE ---
    $spaces_cols = ['is_bookable', 'facility_id', 'operational_mode', 'category', 'capacity', 'hourly_rate'];
    foreach ($spaces_cols as $col) {
        if ($checkColumn('spaces', $col)) {
            echo "<li>[OK] Table 'spaces' has column '$col'</li>";
        } else {
            echo "<li style='color:red;'>[MISSING] Table 'spaces' is missing column '$col'</li>";
            // Repair
            try {
                if ($col == 'is_bookable') {
                    $pdo->exec("ALTER TABLE `{$prefix}spaces` ADD COLUMN `is_bookable` TINYINT(1) DEFAULT 0 AFTER `operational_mode` ");
                } elseif ($col == 'facility_id') {
                    $pdo->exec("ALTER TABLE `{$prefix}spaces` ADD COLUMN `facility_id` INT(11) DEFAULT NULL AFTER `is_bookable` ");
                } elseif ($col == 'operational_mode') {
                    $pdo->exec("ALTER TABLE `{$prefix}spaces` ADD COLUMN `operational_mode` ENUM('available_for_booking','leased','owner_operated','reserved','vacant') DEFAULT 'vacant' AFTER `operational_status` ");
                } elseif ($col == 'hourly_rate') {
                    $pdo->exec("ALTER TABLE `{$prefix}spaces` ADD COLUMN `hourly_rate` DECIMAL(15,2) DEFAULT 0.00 AFTER `capacity` ");
                } else {
                     // Generic add
                     $pdo->exec("ALTER TABLE `{$prefix}spaces` ADD COLUMN `$col` VARCHAR(255) DEFAULT NULL ");
                }
                echo "<li style='color:green;'>[FIXED] Added '$col' to 'spaces' table</li>";
            } catch (Exception $e) {
                echo "<li style='color:darkred;'>[FAILED] Could not add '$col': " . $e->getMessage() . "</li>";
            }
        }
    }

    // --- SPACE PHOTOS TABLE ---
    if ($checkTable('space_photos')) {
        echo "<li>[OK] Table 'space_photos' exists</li>";
    } else {
        echo "<li style='color:red;'>[MISSING] Table 'space_photos' does not exist</li>";
        // Repair
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}space_photos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `space_id` int(11) NOT NULL,
                `photo_url` varchar(500) NOT NULL,
                `photo_type` enum('photo','floor_plan','virtual_tour') DEFAULT 'photo',
                `is_primary` tinyint(1) DEFAULT 0,
                `caption` varchar(255) DEFAULT NULL,
                `display_order` int(11) DEFAULT 0,
                `uploaded_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `space_id` (`space_id`),
                KEY `is_primary` (`is_primary`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            echo "<li style='color:green;'>[FIXED] Created 'space_photos' table</li>";
        } catch (Exception $e) {
            echo "<li style='color:darkred;'>[FAILED] Could not create 'space_photos': " . $e->getMessage() . "</li>";
        }
    }

    // --- BOOKABLE CONFIG TABLE ---
    if ($checkTable('bookable_config')) {
        echo "<li>[OK] Table 'bookable_config' exists</li>";
        $config_cols = ['pricing_rules', 'booking_types', 'availability_rules', 'cancellation_policy_id'];
        foreach ($config_cols as $col) {
            if (!$checkColumn('bookable_config', $col)) {
                echo "<li style='color:red;'>[MISSING] Table 'bookable_config' missing column '$col'</li>";
                try {
                    $pdo->exec("ALTER TABLE `{$prefix}bookable_config` ADD COLUMN `$col` TEXT DEFAULT NULL");
                    echo "<li style='color:green;'>[FIXED] Added '$col' to 'bookable_config'</li>";
                } catch (Exception $e) {
                    echo "<li style='color:darkred;'>[FAILED] Could not add '$col': " . $e->getMessage() . "</li>";
                }
            }
        }
    } else {
        echo "<li style='color:red;'>[MISSING] Table 'bookable_config' does not exist</li>";
        // It should be created by the migration script
    }

    // --- FACILITIES TABLE ---
    $fac_cols = ['half_day_rate', 'weekly_rate', 'is_bookable', 'resource_type', 'max_duration'];
    foreach ($fac_cols as $col) {
        if ($checkColumn('facilities', $col)) {
            echo "<li>[OK] Table 'facilities' has column '$col'</li>";
        } else {
            echo "<li style='color:red;'>[MISSING] Table 'facilities' missing column '$col'</li>";
            try {
                if ($col == 'is_bookable') {
                    $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `is_bookable` TINYINT(1) DEFAULT 1 AFTER `status` ");
                } else {
                    $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `$col` VARCHAR(255) DEFAULT NULL ");
                }
                echo "<li style='color:green;'>[FIXED] Added '$col' to 'facilities'</li>";
            } catch (Exception $e) {
                echo "<li style='color:darkred;'>[FAILED] Could not add '$col': " . $e->getMessage() . "</li>";
            }
        }
    }

    echo "</ul>";

    echo "<h2>2. Checking File Integrity</h2>";
    echo "<ul>";
    $view_files = [
        'application/views/locations/bookings/calendar.php',
        'application/views/spaces/view.php',
        'application/views/spaces/edit.php',
        'assets/css/calendar-timeslots.css'
    ];
    $root = dirname(__DIR__);
    foreach ($view_files as $file) {
        if (file_exists($root . '/' . $file)) {
            echo "<li>[OK] File exists: $file</li>";
        } else {
            echo "<li style='color:red;'>[MISSING] File missing: $file - Ensure you have uploaded all files from the local update.</li>";
        }
    }
    echo "</ul>";

    echo "<h2>Final Status</h2>";
    echo "<p>If you saw [FIXED] messages above, the database has been updated. If you saw [MISSING] files, please re-upload your application files to cPanel.</p>";

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
