<?php
/**
 * Simple Repair Script for cPanel
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ERP Repair Tool</h1>";

$config_file = __DIR__ . '/application/config/database.php';
if (!file_exists($config_file)) {
    die("Database config not found at $config_file");
}

$db = [];
$active_group = 'default';
$query_builder = TRUE;
require($config_file);

$c = $db['default'];
$dsn = "mysql:host={$c['hostname']};dbname={$c['database']};charset={$c['char_set']}";

try {
    $pdo = new PDO($dsn, $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<p style='color:green;'>Connected to Database</p>";
    $prefix = $c['dbprefix'];

    $tables = [
        'space_photos' => "CREATE TABLE IF NOT EXISTS `{$prefix}space_photos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `space_id` int(11) NOT NULL,
                `photo_url` varchar(500) NOT NULL,
                `photo_type` enum('photo','floor_plan','virtual_tour') DEFAULT 'photo',
                `is_primary` tinyint(1) DEFAULT 0,
                `caption` varchar(255) DEFAULT NULL,
                `display_order` int(11) DEFAULT 0,
                `uploaded_at` datetime NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'bookable_config' => "CREATE TABLE IF NOT EXISTS `{$prefix}bookable_config` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `space_id` int(11) NOT NULL,
                `is_bookable` tinyint(1) DEFAULT 1,
                `booking_types` text DEFAULT NULL,
                `minimum_duration` int(11) DEFAULT 1,
                `pricing_rules` text DEFAULT NULL,
                `availability_rules` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `space_id` (`space_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        echo "<li>Table <b>$name</b> verified/created.</li>";
    }

    // Columns
    $columns = [
        'spaces' => ['is_bookable' => "TINYINT(1) DEFAULT 0", 'facility_id' => "INT(11) DEFAULT NULL", 'hourly_rate' => "DECIMAL(15,2) DEFAULT 0.00"],
        'facilities' => ['half_day_rate' => "DECIMAL(15,2) DEFAULT 0.00", 'is_bookable' => "TINYINT(1) DEFAULT 1"]
    ];

    foreach ($columns as $table => $cols) {
        foreach ($cols as $col => $type) {
            $fullTable = $prefix . $table;
            $check = $pdo->query("SHOW COLUMNS FROM `$fullTable` LIKE '$col'");
            if ($check->rowCount() == 0) {
                $pdo->exec("ALTER TABLE `$fullTable` ADD COLUMN `$col` $type");
                echo "<li>Added <b>$col</b> to <b>$table</b>.</li>";
            } else {
                echo "<li>Column <b>$col</b> already exists in <b>$table</b>.</li>";
            }
        }
    }

    echo "<h3>Repair Complete</h3>";
    echo "<p>Please delete this file (repair.php) after use for security.</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
