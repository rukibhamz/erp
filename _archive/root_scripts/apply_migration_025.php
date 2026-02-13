<?php
// Script to apply migration 025 via web browser
define('BASEPATH', __DIR__ . '/application/');

// Load config
if (!file_exists('application/config/config.installed.php')) {
    die("Config file not found!");
}

$config = require 'application/config/config.installed.php';
$db = $config['db'];

echo "<h1>Applying Migration 025 (Add Customer ID)</h1>";
echo "<p>Connecting to database '{$db['database']}'...</p>";

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database/migrations/025_add_customer_id_to_bookings.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>Migration file 025 not found!</p>");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute
    // Note: Prepared statements usage in SQL might require special handling or direct exec might work 
    // depending on driver emulation.
    // The SQL uses user variables and PREPARE/EXECUTE which works in raw SQL but PDO might have issues 
    // if emulation is OFF.
    // Let's force emulation ON for this script just in case
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    
    echo "<p>Executing SQL...</p>";
    $pdo->exec($sql);
    
    echo "<h2 style='color:green'>Success! Migration applied.</h2>";
    
    // Check column
    echo "<h3>Verifying Column:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM erp_bookings LIKE 'customer_id'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>Column 'customer_id' exists!</p>";
    } else {
        echo "<p style='color:red'>Column 'customer_id' MISSING!</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
