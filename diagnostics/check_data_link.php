<?php
define('BASEPATH', true);
$c = require 'application/config/config.php';
$p = $c['db']['dbprefix'];
$db = $c['db'];
$pdo = new PDO("mysql:host={$db['hostname']};dbname={$db['database']}", $db['username'], $db['password']);

echo "### LOCATIONS ###\n";
$stmt = $pdo->query("SELECT id, property_name, location_name FROM `{$p}locations` LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | PROPERTY: " . $row['property_name'] . " | LOCATION: " . $row['location_name'] . "\n";
}

echo "\n### SPACES (linked by property_id) ###\n";
$stmt = $pdo->query("SELECT id, property_id, space_name FROM `{$p}spaces` WHERE property_id IS NOT NULL LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | PROPERTY_ID: " . $row['property_id'] . " | SPACE: " . $row['space_name'] . "\n";
}
