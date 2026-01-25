<?php
// Inspect schema of bookings table
define('BASEPATH', __DIR__ . '/application/');
require_once 'application/core/Database.php';

try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    
    echo "<h1>Schema Check: erp_bookings</h1>";
    
    // Use PDO directly from the database config if possible, or just use fetchAll with raw SQL
    // Assuming fetchAll wrapper works fine.
    
    $query = "SHOW COLUMNS FROM `{$prefix}bookings`";
    echo "<p>Running: $query</p>";
    
    $cols = $db->fetchAll($query);
    
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($cols as $col) {
        echo "<tr>";
        foreach ($col as $val) {
            echo "<td>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>" . $e->getMessage();
}
