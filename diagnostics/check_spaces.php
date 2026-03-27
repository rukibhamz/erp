<?php
define('BASEPATH', __DIR__ . '/system/');
$config = require 'application/config/config.php';
$db = $config['db'];
$pdo = new PDO('mysql:host=' . $db['hostname'] . ';dbname=' . $db['database'], $db['username'], $db['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SHOW COLUMNS FROM `" . $db['dbprefix'] . "spaces`");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Columns in spaces table:\n";
print_r($columns);

// try to add video_url
try {
    $pdo->exec('ALTER TABLE `' . $db['dbprefix'] . 'spaces` ADD COLUMN `video_url` VARCHAR(500) DEFAULT NULL');
    echo "\nSuccessfully added video_url without AFTER clause.\n";
} catch (Exception $e) {
    echo "\nFailed to add video_url: " . $e->getMessage() . "\n";
}
