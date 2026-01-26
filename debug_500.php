<?php
// Emergency debug script - identifies 500 error cause
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Mode</h1>";
echo "<pre>";

try {
    echo "Step 1: Loading constants...\n";
    define('BASEPATH', __DIR__ . '/application/');
    define('ROOTPATH', __DIR__ . '/');
    
    echo "Step 2: Testing config load...\n";
    $configFile = BASEPATH . 'config/config.installed.php';
    if (file_exists($configFile)) {
        echo "Config file exists: $configFile\n";
        $config = require $configFile;
        echo "Config loaded successfully\n";
    } else {
        echo "No installed config, checking default...\n";
        $configFile = BASEPATH . 'config/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            echo "Default config loaded\n";
        }
    }
    
    echo "Step 3: Testing URL helper...\n";
    require_once BASEPATH . 'helpers/url_helper.php';
    echo "URL helper loaded\n";
    echo "base_url() = " . base_url() . "\n";
    
    echo "Step 4: Testing Database...\n";
    require_once BASEPATH . 'core/Database.php';
    $db = Database::getInstance();
    echo "Database connected\n";
    
    echo "Step 5: Testing AutoMigration...\n";
    require_once BASEPATH . 'core/AutoMigration.php';
    new AutoMigration();
    echo "AutoMigration complete\n";
    
    echo "\n<b style='color:green'>All steps passed! No error found.</b>\n";
    
} catch (Exception $e) {
    echo "\n<b style='color:red'>ERROR: " . $e->getMessage() . "</b>\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "\n<b style='color:red'>FATAL ERROR: " . $e->getMessage() . "</b>\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
