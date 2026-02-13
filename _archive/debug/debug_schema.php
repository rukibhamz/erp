<?php
define('BASEPATH', __DIR__ . '/application/');
require_once 'application/core/Database.php';
$db = Database::getInstance();
$prefix = $db->getPrefix();

echo "--- SPACES TABLE ---\n";
try {
    $cols = $db->fetchAll("DESCRIBE `{$prefix}spaces`");
    foreach ($cols as $col) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- BOOKABLE_CONFIG TABLE ---\n";
try {
    $cols = $db->fetchAll("DESCRIBE `{$prefix}bookable_config`");
    foreach ($cols as $col) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
