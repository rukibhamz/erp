<?php
define('BASEPATH', 'system');
define('ENVIRONMENT', 'development');
// Suppress notices
error_reporting(E_ALL & ~E_NOTICE);

$files = [
    'application/config/config.installed.php',
    'application/config/database.php',
    'application/config/development/database.php'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        echo "Including $f...\n";
        include $f;
    }
}

// Check constants
print_r(get_defined_constants(true)['user']);

// List root files
echo "\nRoot files:\n";
$rootFiles = scandir('.');
print_r($rootFiles);

