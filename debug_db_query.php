<?php
define('BASEPATH', 'system');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_config = include 'application/config/config.installed.php';
$db = $db_config['db'];

echo "Attempting connection to: " . $db['hostname'] . " (forcing 127.0.0.1)\n";
echo "User: " . $db['username'] . "\n";
echo "DB: " . $db['database'] . "\n";

// Force TCP
$host = '127.0.0.1';
$mysqli = new mysqli($host, $db['username'], $db['password'], $db['database']);


if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$queries = [
    "Facilities" => "SELECT id, facility_name, status, space_id FROM facilities LIMIT 5",
    "Bookable Config" => "SELECT space_id, availability_rules FROM bookable_config LIMIT 5",
    "Resource Availability" => "SELECT * FROM resource_availability LIMIT 10"
];

foreach ($queries as $name => $sql) {
    echo "\n=== $name ===\n";
    $result = $mysqli->query($sql);
    if ($result) {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode($rows, JSON_PRETTY_PRINT);
    } else {
        echo "Error: " . $mysqli->error;
    }
}

$mysqli->close();
