<?php
/**
 * Run Inventory Migrations
 * Use this script to create inventory tables if they weren't created during installation
 */

// Define BASEPATH for migration files
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/application/');
}
if (!defined('ROOTPATH')) {
    define('ROOTPATH', dirname(__DIR__) . '/');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Check if config exists
$config_file = BASEPATH . 'config/config.installed.php';
if (!file_exists($config_file)) {
    $config_file = BASEPATH . 'config/config.php';
}

if (!file_exists($config_file)) {
    die('Configuration file not found. Please run the installer first.');
}

$config = require $config_file;

if (!isset($config['installed']) || $config['installed'] !== true) {
    die('Application is not installed. Please run the installer first.');
}

// Get database configuration
$db_config = $config['db'] ?? [];
if (empty($db_config['hostname']) || empty($db_config['database'])) {
    die('Database configuration is incomplete.');
}

// Connect to database
try {
    $dsn = "mysql:host={$db_config['hostname']};dbname={$db_config['database']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
    
    // Get table prefix
    $prefix = $db_config['prefix'] ?? 'erp_';
    
    // Load and run inventory migrations
    require_once __DIR__ . '/migrations_inventory.php';
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Run Inventory Migrations</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
        h1 { color: #333; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Running Inventory Migrations</h1>";
    
    try {
        runInventoryMigrations($pdo, $prefix);
        echo "<div class='success'><strong>Success!</strong> Inventory migrations completed successfully.</div>";
        echo "<div class='info'>The following tables should now exist:</div>";
        echo "<ul>";
        echo "<li>{$prefix}items</li>";
        echo "<li>{$prefix}stock_levels</li>";
        echo "<li>{$prefix}locations</li>";
        echo "<li>And other inventory-related tables</li>";
        echo "</ul>";
        echo "<p><a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME'])) . "/inventory'>Go to Inventory Module</a></p>";
    } catch (Exception $e) {
        echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
    echo "</div></body></html>";
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

