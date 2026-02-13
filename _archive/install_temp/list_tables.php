<?php
define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
require_once BASEPATH . 'core/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$prefix = $db->getPrefix();

echo "Listing tables:\n";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($tables as $table) {
        echo "- " . $table . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
