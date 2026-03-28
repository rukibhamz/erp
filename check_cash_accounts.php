<?php
define('BASEPATH', __DIR__ . '/application/');
require_once 'application/core/Database.php';
$db = Database::getInstance();
$prefix = $db->getPrefix();
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `{$prefix}cash_accounts`");
    echo "Columns in cash_accounts:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
