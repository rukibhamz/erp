<?php
define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
require_once BASEPATH . 'core/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$prefix = $db->getPrefix();

echo "Checking bookings table columns...\n";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$prefix}bookings`");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($cols as $col) {
        echo "- " . $col . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
