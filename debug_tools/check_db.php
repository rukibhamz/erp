<?php
define('BASEPATH', __DIR__ . '/application/');
require_once BASEPATH . 'core/Database.php';

try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    
    echo "Checking '{$prefix}bookable_config' columns:\n";
    $columns = $db->fetchAll("DESCRIBE {$prefix}bookable_config");
    foreach ($columns as $row) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\nChecking '{$prefix}spaces' columns:\n";
    $columns = $db->fetchAll("DESCRIBE {$prefix}spaces");
    foreach ($columns as $row) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
