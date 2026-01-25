<?php
// Debug script to check recent bookings
define('BASEPATH', __DIR__ . '/application/');

// Manually read config
$configFile = 'application/config/config.installed.php';
if (!file_exists($configFile)) {
    die("Config file not found: $configFile");
}

$config = require $configFile;
if (!is_array($config) || !isset($config['db'])) {
    die("Config is invalid or missing 'db' key");
}

$dbConfig = $config['db'];

try {
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("DB Connection Error: " . $e->getMessage());
}

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
        // Check if table missing
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
             echo "<span style='color:red'>Table does not exist</span>";
        } else {
             echo "<span style='color:red'>Error: " . $e->getMessage() . "</span>";
        }
    }
    echo "<hr>";
}

// Check probable tables
dumpTable($pdo, 'erp_space_bookings');
dumpTable($pdo, 'erp_bookings');

// Check MAX ID
echo "<h3>Check IDs</h3>";
try {
    $stmt = $pdo->query("SELECT MAX(id) FROM erp_space_bookings");
    echo "Max ID in erp_space_bookings: " . ($stmt->fetchColumn() ?: '0') . "<br>";
} catch(Exception $e) { echo "Error checking max ID: " . $e->getMessage(); }
