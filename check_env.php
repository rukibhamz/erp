<?php
/**
 * Diagnostic Script to check environment state
 */
define('BASEPATH', __DIR__ . '/application/');
require_once __DIR__ . '/application/core/Database.php';

header('Content-Type: text/plain');

echo "--- ENVIRONMENT DIAGNOSTICS ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "get_instance() exists: " . (function_exists('get_instance') ? 'YES' : 'NO') . "\n";
echo "APPPATH defined: " . (defined('APPPATH') ? 'YES (' . APPPATH . ')' : 'NO') . "\n";
echo "BASEPATH defined: " . (defined('BASEPATH') ? 'YES (' . BASEPATH . ')' : 'NO') . "\n";

echo "\n--- LOADED MODELS ---\n";
$models = ['Space_model', 'Facility_model', 'Bookable_config_model', 'Resource_availability_model'];
foreach ($models as $model) {
    echo "Class {$model} exists: " . (class_exists($model) ? 'YES' : 'NO') . "\n";
}

echo "\n--- DATABASE CHECK ---\n";
try {
    $db = Database::getInstance();
    echo "Database instance: OK\n";
    $conn = $db->getConnection();
    echo "Database connection: " . ($conn ? 'OK' : 'FAILED') . "\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n--- FILE PATHS ---\n";
$files = [
    'application/controllers/Spaces.php',
    'application/models/Space_model.php',
    'application/models/Bookable_config_model.php',
];
foreach ($files as $file) {
    echo "File {$file} exists: " . (file_exists(__DIR__ . '/' . $file) ? 'YES' : 'NO') . "\n";
}
