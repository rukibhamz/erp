<?php
require_once 'application/config/database.php';

// Minimally mock the DB/loading
$db_config = $db['default'];

$dsn = 'mysql:host='.$db_config['hostname'].';dbname='.$db_config['database'].';charset='.$db_config['char_set'];
$username = $db_config['username'];
$password = $db_config['password'];

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("DESCRIBE " . $db_config['dbprefix'] . "bookings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in bookings table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
