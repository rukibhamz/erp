<?php
// DB Config
$host = 'localhost';
$db   = 'erps';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$prefix = 'erp_';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected successfully to database '$db'\n\n";
    
    // Check erp_customers columns
    echo "COLUMNS IN {$prefix}customers:\n";
    $stmt = $pdo->query("DESCRIBE {$prefix}customers");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n-----------------------------------\n";
    
    // Check erp_users for the customer email
    $email = 'omaruconsults@gmail.com';
    echo "CHECKING USER: $email\n";
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User FOUND: ID " . $user['id'] . "\n";
    } else {
        echo "User NOT FOUND.\n";
    }
    
} catch (\PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
