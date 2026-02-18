<?php
// Try to connect to DB
$host = '127.0.0.1'; 
$db   = 'erp';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Check facility columns
    echo "--- FACILITY COLUMNS ---\n";
    $stm = $pdo->query("SHOW COLUMNS FROM erp_facilities");
    $columns = $stm->fetchAll();
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
    
    // Check space columns
    echo "\n--- SPACE COLUMNS ---\n";
    $stm = $pdo->query("SHOW COLUMNS FROM erp_spaces");
    $columns = $stm->fetchAll();
    foreach ($columns as $col) {
        echo $col['Field'] . "\n";
    }
    
} catch (\PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
