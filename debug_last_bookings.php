<?php
// Debug script to check recent bookings
define('BASEPATH', __DIR__ . '/application/');

require 'application/config/config.installed.php';
$dbConfig = $config['db'];

$dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h1>Recent Bookings Debug</h1>";

function dumpTable($pdo, $tableName) {
    echo "<h3>Table: $tableName</h3>";
    try {
        // Try getting count
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$tableName`");
        $count = $stmt->fetchColumn();
        echo "Total Records: $count<br>";
        
        if ($count > 0) {
            $stmt = $pdo->query("SELECT * FROM `$tableName` ORDER BY id DESC LIMIT 5");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' cellspacing='0' cellpadding='5'>";
            // Headers
            if (!empty($rows)) {
                echo "<tr>";
                foreach (array_keys($rows[0]) as $key) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
            }
            // Rows
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
             echo "<i>Table is empty</i>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red'>Error: " . $e->getMessage() . "</span>";
    }
    echo "<hr>";
}

// Check both probable tables
dumpTable($pdo, 'erp_space_bookings');
dumpTable($pdo, 'erp_bookings');
dumpTable($pdo, 'space_bookings'); // Just in case
dumpTable($pdo, 'bookings');       // Just in case

echo "<h3>Check IDs</h3>";
try {
    // Check next ID for space_bookings
    $stmt = $pdo->query("SELECT MAX(id) FROM erp_space_bookings");
    echo "Max ID in erp_space_bookings: " . $stmt->fetchColumn() . "<br>";
} catch(Exception $e) {}
