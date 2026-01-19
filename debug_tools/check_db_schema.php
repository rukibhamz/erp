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
    
    echo "\n--- BOOKABLE_CONFIG Table Schema ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM " . $db['dbprefix'] . "bookable_config");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Table 'bookable_config' exists with " . count($columns) . " columns\n";
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['pricing_rules', 'availability_rules', 'minimum_duration'])) {
            echo "Column: {$col['Field']}, Type: {$col['Type']}\n";
        }
    }

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
