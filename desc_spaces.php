<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('BASEPATH', true);
$c = require 'application/config/config.php';
$p = $c['db']['dbprefix'];
$db = $c['db'];
$pdo = new PDO("mysql:host={$db['hostname']};dbname={$db['database']}", $db['username'], $db['password']);
$stmt = $pdo->query("DESCRIBE `{$p}spaces`");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "COL: " . $row['Field'] . "\n";
}
echo "---END---";
