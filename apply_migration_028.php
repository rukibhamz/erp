<?php
// Script to apply migration 028 via web browser
define('BASEPATH', __DIR__ . '/application/');

// Load config
if (!file_exists('application/config/config.installed.php')) {
    die("Config file not found!");
}

$config = require 'application/config/config.installed.php';
$db = $config['db'];

echo "<h1>Applying Migration 028 (Create space_bookings table)</h1>";
echo "<p>Connecting to database '{$db['database']}'...</p>";

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database/migrations/028_create_space_bookings_table.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>Migration file 028 not found!</p>");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Ensure table name uses correct prefix if not already in SQL
    // My tool wrote 'erp_space_bookings', let's assume prefix is 'erp_'
    // But checking config just in case
    $prefix = $db['dbprefix']; // 'erp_'
    // The SQL file I wrote has 'erp_space_bookings' hardcoded.
    // If prefix is different, we might have issues, but user confirmed 'erp_' tables exist.
    
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    
    echo "<p>Executing SQL...</p>";
    $pdo->exec($sql);
    
    echo "<h2 style='color:green'>Success! Migration applied.</h2>";
    
    // Check table
    echo "<h3>Verifying Table:</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'erp_space_bookings'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>Table 'erp_space_bookings' created successfully!</p>";
        
        // Check end_date column
        $stmtCol = $pdo->query("SHOW COLUMNS FROM erp_space_bookings LIKE 'end_date'");
        if ($stmtCol->rowCount() > 0) {
            echo "<p style='color:green'>Column 'end_date' verified.</p>";
        }
    } else {
        echo "<p style='color:red'>Table 'erp_space_bookings' FAILED to create!</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
