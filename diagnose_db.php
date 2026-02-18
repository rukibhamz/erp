<?php
$hostnames = ['127.0.0.1', 'localhost'];
$user = 'root';
$pass = '';
$db = 'erp';

foreach ($hostnames as $host) {
    echo "Testing connection to $host...\n";
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        echo "SUCCESS: Connected to $host\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM erp_transactions");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Transactions count: " . $row['count'] . "\n";
        
        $stmt = $pdo->query("SELECT * FROM erp_transactions ORDER BY id DESC LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Recent Transactions:\n";
        print_r($rows);
        
        exit(0);
    } catch (PDOException $e) {
        echo "FAILED: $host - " . $e->getMessage() . "\n";
    }
}

echo "All connection attempts failed.\n";
exit(1);
