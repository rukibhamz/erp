<?php
// Debug Collation
define('BASEPATH', __DIR__ . '/application/');
require_once 'application/core/Database.php';

try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    
    echo "<h1>Collation Check</h1>";
    
    $tables = ['erp_bookings', 'erp_customers'];
    
    foreach ($tables as $table) {
        $t = $prefix . str_replace('erp_', '', $table); // simplified handling
        // actually prefix is likely erp_ so just check what's in DB
        
        echo "<h2>Table: $table</h2>";
        $sql = "SHOW FULL COLUMNS FROM `$table`";
        $cols = $db->fetchAll($sql);
        
        echo "<table border='1'><tr><th>Field</th><th>Collation</th></tr>";
        foreach ($cols as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Collation']}</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
