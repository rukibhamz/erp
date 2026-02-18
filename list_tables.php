<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'erp';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "SUCCESS: Connected to $host\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database '$db':\n";
    print_r($tables);
    
    foreach ($tables as $table) {
        if (strpos($table, 'transactions') !== false) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "Count for table '$table': $count\n";
        }
    }
    
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
