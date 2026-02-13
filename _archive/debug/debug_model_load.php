<?php
// Debug script to check Model Loading mechanics
define('BASEPATH', __DIR__ . '/application/');
$config = require 'application/config/config.installed.php';

echo "<h1>Model Load Debug</h1>";
echo "BASEPATH: " . BASEPATH . "<br>";

$modelName = 'Customer_model';
$path = BASEPATH . 'models/' . $modelName . '.php';

echo "Target Path: " . $path . "<br>";
echo "File Exists? " . (file_exists($path) ? "YES" : "NO") . "<br>";

if (file_exists($path)) {
    require_once $path;
    echo "Class Exists? " . (class_exists($modelName) ? "YES" : "NO") . "<br>";
    
    if (class_exists($modelName)) {
        try {
            // Need to mock Database for Base_Model
            require_once BASEPATH . 'core/Database.php';
            require_once BASEPATH . 'core/Base_Model.php';
            
            $obj = new $modelName();
            echo "Instantiation: SUCCESS <br>";
        } catch (Exception $e) {
            echo "Instantiation ERROR: " . $e->getMessage() . "<br>";
        }
    }
} else {
    // List files in directory
    echo "<h3>Files in application/models:</h3>";
    $files = scandir(BASEPATH . 'models');
    echo "<pre>" . print_r($files, true) . "</pre>";
}
