<?php
define('BASEPATH', true);
$c = require 'application/config/config.php';
$p = $c['db']['dbprefix'];
$db = $c['db'];
$pdo = new PDO("mysql:host={$db['hostname']};dbname={$db['database']}", $db['username'], $db['password']);
$res = $pdo->query("SHOW COLUMNS FROM `{$p}erp_spaces`")->fetchAll(PDO::FETCH_COLUMN);
// Wait, is it erp_erp_spaces? No, prefix is erp_. 
// Let's try erp_spaces
try {
    $res = $pdo->query("SHOW COLUMNS FROM `{$p}spaces`")->fetchAll(PDO::FETCH_COLUMN);
    echo "COLUMNS FOR {$p}spaces:\n";
    foreach($res as $col) { echo "- $col\n"; }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo "DONE\n";
