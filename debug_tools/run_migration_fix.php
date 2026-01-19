<?php
// Define constants
define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');

// Load DB config
$configFile = 'application/config/config.installed.php';
if (!file_exists($configFile)) {
    die("Config file not found: $configFile\n");
}

$config = require $configFile;
$db = $config['db'] ?? null;

if (!$db) {
    die("Database configuration not found\n");
}

try {
    // Remove charset to avoid connection error if unknown
    $dsn = 'mysql:host='.$db['hostname'].';dbname='.$db['database'];
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Include migration files
    require_once 'install/migrations_facilities_rates.php';
    require_once 'install/migrations_bookable_config.php';
    
    echo "Running migrations...\n";
    
    // Run facilities rates migration
    if (runFacilitiesRatesMigrations($pdo, $db['dbprefix'])) {
        echo "Facilities rates migration executed successfully!\n";
    } else {
        echo "Facilities rates migration failed!\n";
    }
    
    // Run bookable config migration
    if (runBookableConfigMigration($pdo, $db['dbprefix'])) {
        echo "Bookable Config migration executed successfully!\n";
    } else {
        echo "Bookable Config migration failed!\n";
    }
    
    echo "\n--- BOOKABLE_CONFIG Table Schema ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM " . $db['dbprefix'] . "bookable_config");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Table 'bookable_config' now exists with " . count($columns) . " columns\n";

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
