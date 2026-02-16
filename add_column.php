<?php
$host = '127.0.0.1';
$db   = 'erp';
$user = 'root';
$pass = ''; // Try empty password as per config.installed.php
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Add space_id column
    echo "Adding space_id column to erp_bookings...\n";
    $sql = "ALTER TABLE erp_bookings ADD COLUMN space_id INT NULL AFTER booking_number";
    $pdo->exec($sql);
    echo "Column added successfully.\n";
    
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
         echo "Column space_id already exists.\n";
    } else {
         echo "Error: " . $e->getMessage() . "\n";
    }
}
