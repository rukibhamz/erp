<?php
if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/application/');
require_once __DIR__ . '/application/core/Database.php';

try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    $table = $prefix . "space_photos";
    
    echo "Checking table: $table\n";
    $result = $db->fetchAll("SHOW TABLES LIKE '$table'");
    if ($result) {
        echo "Table exists.\n";
        $cols = $db->fetchAll("DESCRIBE `$table` ");
        print_r($cols);
    } else {
        echo "Table does NOT exist.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
