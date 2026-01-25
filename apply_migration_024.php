<?php
// Script to apply migration 024 via web browser
define('BASEPATH', __DIR__ . '/application/');

// Load config
if (!file_exists('application/config/config.installed.php')) {
    die("Config file not found!");
}

$config = require 'application/config/config.installed.php';
$db = $config['db'];

echo "<h1>Applying Migration 024</h1>";
echo "<p>Connecting to database '{$db['database']}' on '{$db['hostname']}'...</p>";

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read SQL file
    $sqlFile = __DIR__ . '/database/migrations/024_fix_missing_primary_tables.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color:red'>Migration file 024 not found!</p>");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split into statements? Or just exec?
    // Since we have multiple CREATE TABLE statements, PDO::exec might handle it if emulation is enabled or supported.
    // Let's try to split by semicolon for safety if simple exec fails, but basic CREATEs usually work in bulk.
    // Actually, let's treat it as a single block first.
    
    echo "<p>Executing SQL...</p>";
    $pdo->exec($sql);
    
    echo "<h2 style='color:green'>Success! Migration applied.</h2>";
    
    // Check tables
    $tables = ['erp_bookings', 'erp_facilities', 'erp_customers', 'erp_invoices', 'erp_accounts', 'erp_cash_accounts', 'erp_companies'];
    echo "<h3>Verifying Tables:</h3><ul>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<li style='color:green'>$table EXISTS</li>";
        } else {
            echo "<li style='color:red'>$table MISSING</li>";
        }
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
