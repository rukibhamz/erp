<?php
// Run manual migration
define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');

require_once BASEPATH . 'core/Database.php';

// Load config
$configFile = BASEPATH . 'config/config.installed.php';
if (!file_exists($configFile)) {
    $configFile = BASEPATH . 'config/config.php';
}
if (!file_exists($configFile)) {
    die("Config not found.");
}
$config = require $configFile;

try {
    $dbConfig = $config['db'];
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sqlFile = __DIR__ . '/database/migrations/026_update_booking_type_enum.sql';
    if (!file_exists($sqlFile)) {
        die("Migration file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    
    echo "Executing migration...\n";
    $pdo->exec($sql);
    echo "Migration 026 executed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
