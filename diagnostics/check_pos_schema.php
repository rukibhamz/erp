<?php
define('BASEPATH', __DIR__ . '/../application/');
require BASEPATH . 'core/Database.php';

try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    
    echo "--- pos_sales ---\n";
    $stmt = $db->query("DESCRIBE `{$prefix}pos_sales`");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n--- pos_sale_items ---\n";
    $stmt = $db->query("DESCRIBE `{$prefix}pos_sale_items`");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n--- pos_payments ---\n";
    $stmt = $db->query("DESCRIBE `{$prefix}pos_payments`");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
