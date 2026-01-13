<?php
define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');

require_once 'application/config/config.installed.php';
$config = require 'application/config/config.installed.php';
$db = $config['db'];

try {
    $dsn = 'mysql:host='.$db['hostname'].';dbname='.$db['database'];
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    
    // Check Spaces
    $stmt = $pdo->query("SELECT id, space_name, is_bookable FROM " . $db['dbprefix'] . "spaces LIMIT 5");
    echo "--- SPACES ---\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Name: {$row['space_name']}, Bookable: {$row['is_bookable']}\n";
    }
    
    // Check Config
    $stmt = $pdo->query("SELECT * FROM " . $db['dbprefix'] . "bookable_config");
    echo "\n--- BOOKABLE CONFIG ---\n";
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "Table is EMPTY.\n";
    } else {
        foreach ($rows as $row) {
            echo "ID: {$row['id']}, SpaceID: {$row['space_id']}, Pricing: {$row['pricing_rules']}\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
