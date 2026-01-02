<?php
define('BASEPATH', __DIR__ . '/application/');
require_once __DIR__ . '/application/core/Database.php';

try {
    $db = Database::getInstance();
    $tables = ['facilities', 'spaces', 'bookable_config', 'properties', 'bookings'];
    
    foreach ($tables as $table) {
        echo "<h3>Table: $table</h3><pre>";
        try {
            $cols = $db->fetchAll("DESCRIBE `" . $db->getPrefix() . "$table` ");
            print_r($cols);
        } catch (Exception $e) {
            echo "Error describing $table: " . $e->getMessage();
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "DB Connection failed: " . $e->getMessage();
}
