<?php
define('BASEPATH', 'C:/xampp/htdocs/newerp/application/');
require_once 'application/core/Database.php';
$config = require 'application/config/config.installed.php';

try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    $sql = "SELECT * FROM {$prefix}transactions ORDER BY id DESC LIMIT 5";
    $results = $db->fetchAll($sql);
    echo "Recent Transactions:\n";
    print_r($results);
    
    $today = date('Y-m-d');
    $sqlToday = "SELECT COUNT(*) as count FROM {$prefix}transactions WHERE transaction_date = '$today'";
    $countToday = $db->fetchOne($sqlToday);
    echo "\nTransactions today ($today): " . $countToday['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
