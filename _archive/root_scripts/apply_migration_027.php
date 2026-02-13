<?php
// Script to apply migration 027 via web browser
define('BASEPATH', __DIR__ . '/application/');

// Load config
if (!file_exists('application/config/config.installed.php')) {
    die("Config file not found!");
}

$config = require 'application/config/config.installed.php';
$db = $config['db'];

echo "<h1>Applying Migration 027 (Add end_date to space_bookings)</h1>";
echo "<p>Connecting to database '{$db['database']}'...</p>";

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database/migrations/027_add_end_date_to_space_bookings.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>Migration file 027 not found!</p>");
    }
    
    $sql = file_get_contents($sqlFile);
    
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    
    echo "<p>Executing SQL...</p>";
    $pdo->exec($sql);
    
    echo "<h2 style='color:green'>Success! Migration applied.</h2>";
    
    // Check column
    echo "<h3>Verifying Column:</h3>";
    // Check table prefix if any, assuming standard erp_ prefix or derived from config
    // Actually the SQL used dynamic table name in code but query needs exact name.
    // Let's assume the prefix is 'erp_' based on previous output or try to guess.
    // Previous output showed erp_bookings.
    $stmt = $pdo->query("SHOW COLUMNS FROM erp_space_bookings LIKE 'end_date'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>Column 'end_date' exists!</p>";
    } else {
        // Fallback check without prefix just in case
        $stmt = $pdo->query("SHOW COLUMNS FROM space_bookings LIKE 'end_date'");
         if ($stmt->rowCount() > 0) {
             echo "<p style='color:green'>Column 'end_date' exists (no prefix)!</p>";
         } else {
             echo "<p style='color:red'>Column 'end_date' MISSING!</p>";
         }
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
