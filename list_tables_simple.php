<?php
$config = require 'application/config/config.php';
$db = $config['db'];
$dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $stmt = $pdo->query('SHOW TABLES');
    while($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
