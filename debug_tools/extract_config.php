<?php
define('BASEPATH', 'system');
error_reporting(0);
$config = include 'application/config/config.installed.php';
if (isset($config['db'])) {
    echo "JSON_START\n";
    echo json_encode($config['db']);
    echo "\nJSON_END";
} else {
    echo "DB key not found in config array.\n";
}





