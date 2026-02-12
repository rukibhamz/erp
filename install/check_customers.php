<?php
define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
require_once BASEPATH . 'core/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$prefix = $db->getPrefix();

// Check customers columns
$stmt = $pdo->query("SHOW COLUMNS FROM `{$prefix}customers`");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
echo "customers columns: " . implode(', ', $cols) . "\n\n";

// Check if current_balance exists
if (in_array('current_balance', $cols)) {
    echo "current_balance: EXISTS\n";
} else {
    echo "current_balance: MISSING — adding now...\n";
    try {
        $pdo->exec("ALTER TABLE `{$prefix}customers` ADD COLUMN `current_balance` DECIMAL(15,2) DEFAULT 0.00");
        echo "  ADDED current_balance\n";
    } catch (PDOException $e) {
        echo "  FAILED: " . $e->getMessage() . "\n";
    }
}
