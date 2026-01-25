<?php
// Script to list all tables in the database
define('BASEPATH', __DIR__ . '/application/');

// Load config
if (!file_exists('application/config/config.installed.php')) {
    die("Config file not found!");
}

$config = require 'application/config/config.installed.php';
$db = $config['db'];

echo "<h1>Listing All Tables</h1>";
echo "<p>Connecting to config database '{$db['database']}'...</p>";

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables in '{$db['database']}':</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
