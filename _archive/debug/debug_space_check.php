<?php
// Debug script to check if Space ID 9 exists
define('BASEPATH', __DIR__ . '/application/');
$config = require 'application/config/config.installed.php';
$dbConfig = $config['db'];

try {
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Space Check</h1>";
    
    // Check Space 9
    $stmt = $pdo->query("SELECT * FROM erp_spaces WHERE id = 9");
    $space = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($space) {
        echo "<h3 style='color:green'>Space ID 9 Found</h3>";
        echo "<pre>" . print_r($space, true) . "</pre>";
    } else {
        echo "<h3 style='color:red'>Space ID 9 NOT FOUND</h3>";
        echo "<p>This explains why the booking is hidden! The booking is linked to a non-existent space.</p>";
    }
    
    // Check all spaces
    echo "<h3>All Spaces:</h3>";
    $stmt = $pdo->query("SELECT id, space_name FROM erp_spaces");
    $spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($spaces, true) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
