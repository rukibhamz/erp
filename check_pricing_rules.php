<?php
define('BASEPATH', __DIR__ . '/application/');
$config = require 'application/config/config.php';
$db = $config['db'];
$out = "";
try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $prefix = $db['dbprefix'];
    
    $stmt = $pdo->query("SELECT bc.*, s.space_name FROM `{$prefix}bookable_config` bc JOIN `{$prefix}spaces` s ON bc.space_id = s.id");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($configs as $c) {
        $out .= "Space: {$c['space_name']} (ID: {$c['space_id']})\n";
        $out .= "Pricing Rules: " . $c['pricing_rules'] . "\n";
        $out .= "-------------------\n";
    }
} catch (Exception $e) {
    $out .= "Error: " . $e->getMessage() . "\n";
}
file_put_contents('db_check_config_pricing.txt', $out);
echo "Check complete. See db_check_config_pricing.txt";
