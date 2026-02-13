<?php
// Standalone script to check schema

$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'rukibhamz_erp'; // Checking common DB names from previous contexts, actually, user provided rukibhamz/erp corpus, but usually DB is erp or rukibhamz_erp. Let's try 'erp' first or check config.

// Let's read the config file first to get DB name
$config_content = file_get_contents('application/config/database.php');
if (preg_match("/'database'\s*=>\s*'([^']+)'/", $config_content, $matches)) {
    $database = $matches[1];
    echo "Found database name in config: $database\n";
} else {
    echo "Could not find database name in config, assuming 'erp'\n";
    $database = 'erp';
}

$dsn = "mysql:host=$hostname;dbname=$database;charset=utf8";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(", ", $tables) . "\n\n";
    
    // Check bookings columns
    $table_name = 'erp_bookings'; // Assuming prefix is erp_
    // Check prefix in config
    if (preg_match("/'dbprefix'\s*=>\s*'([^']+)'/", $config_content, $matches_prefix)) {
        $prefix = $matches_prefix[1];
        $table_name = $prefix . 'bookings';
        echo "Found prefix: $prefix, checking table $table_name\n";
    }

    $stmt = $pdo->query("DESCRIBE $table_name");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in $table_name:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
