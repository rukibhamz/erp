<?php
// Define constants
define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');
define('ROOTPATH', __DIR__ . '/');

// Setup logging
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
$logFile = ROOTPATH . 'logs/error.log';
ini_set('error_log', $logFile);

error_log("--- DEBUG SCRIPT STARTED ---");

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
    $dsn = 'mysql:host='.$db['hostname'].';dbname='.$db['database'];
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connected.\n";
    
    // Check table
    $stmt = $pdo->query("SHOW TABLES LIKE '" . $db['dbprefix'] . "bookable_config'");
    if ($stmt->rowCount() == 0) {
        die("CRITICAL: Table bookable_config DOES NOT EXIST.\n");
    }
    echo "Table bookable_config exists.\n";
    
    // Try INSERT
    $testSpaceId = 999999;
    $sql = "INSERT INTO " . $db['dbprefix'] . "bookable_config 
            (space_id, is_bookable, pricing_rules) 
            VALUES (:space_id, 1, :pricing)
            ON DUPLICATE KEY UPDATE pricing_rules = :pricing_update";
    
    $pricing = json_encode(['debug_test' => true, 'timestamp' => time()]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':space_id' => $testSpaceId,
        ':pricing' => $pricing,
        ':pricing_update' => $pricing
    ]);
    
    echo "Insert/Update successful.\n";
    
    // Read back
    $stmt = $pdo->prepare("SELECT * FROM " . $db['dbprefix'] . "bookable_config WHERE space_id = ?");
    $stmt->execute([$testSpaceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "Read successful: " . $row['pricing_rules'] . "\n";
    } else {
        echo "Read FAILED.\n";
    }
    
    // CLEANUP
    $pdo->prepare("DELETE FROM " . $db['dbprefix'] . "bookable_config WHERE space_id = ?")->execute([$testSpaceId]);
    echo "Cleanup done.\n";
    
    // Check log file
    if (file_exists($logFile)) {
        echo "Log file exists and is " . (is_writable($logFile) ? "writable" : "NOT writable") . ".\n";
    } else {
        echo "Log file does not exist.\n";
    }

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
