<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'erp';
$outFile = 'db_tables.txt';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $out = "SUCCESS: Connected to $host\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $out .= "Tables in database '$db':\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        $out .= "$table: $count\n";
    }
    
    file_put_contents($outFile, $out);
    echo "Output written to $outFile\n";
    
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
